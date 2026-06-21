<?php
// Запрещаем прямой доступ к файлу минуя фреймворк
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Библиотека построения иерархического дерева задач заказчика
 *
 * Формирует древовидную структуру задач с пагинацией по корневым элементам.
 * Используется одновременно в index() и load_tasks_ajax() контроллера Customers,
 * устраняя дублирование ~60 строк одинаковой логики.
 *
 * Входные данные: плоский массив всех задач пользователя (из Task_model->get_user_tasks)
 * Выходные данные: дерево с рекурсивными children и formatted_time для каждого узла
 */
class Task_tree_builder {

    /**
     * Ссылка на экземпляр CodeIgniter для доступа к моделям
     * @var CI_Controller
     */
    protected $CI;

    /**
     * Конструктор библиотеки.
     * Загружает Task_model для вычисления времени по задачам.
     */
    public function __construct()
    {
        // Получаем ссылку на главный экземпляр CI
        $this->CI =& get_instance();

        // Загружаем модель задач для подсчёта рекурсивного времени
        $this->CI->load->model('Task_model');
    }

    /**
     * Строит иерархическое дерево задач заказчика с пагинацией.
     *
     * Алгоритм:
     * 1. Из плоского списка отбираем корневые задачи (parent_id === NULL) нужного заказчика
     * 2. Фильтруем по статусу (если не запрошены закрытые)
     * 3. Сортируем: active первые, затем по created_at DESC
     * 4. Берём срез [$offset, $limit] для пагинации
     * 5. Для каждого корневого узла рекурсивно строим children
     * 6. Вычисляем formatted_time через Task_model->get_task_time_recursive()
     *
     * @param array  $raw_tasks    Плоский массив ВСЕХ задач пользователя
     * @param int    $customer_id  ID заказчика для фильтрации
     * @param int    $user_id      ID пользователя (для подсчёта времени)
     * @param int    $offset       Смещение для пагинации корневых задач
     * @param int    $limit        Лимит корневых задач на страницу
     * @param bool   $show_closed  Показывать ли завершённые задачи
     * @return array ['tree' => [...], 'has_more' => bool]
     */
    public function build($raw_tasks, $customer_id, $user_id, $offset = 0, $limit = 25, $show_closed = false)
    {
        // Шаг 1: Фильтруем корневые задачи — только те, у которых нет родителя
        // и которые принадлежат нужному заказчику
        $root_tasks = array_filter($raw_tasks, function ($task) use ($customer_id) {
            // parent_id === NULL означает корневую задачу (не подзадачу)
            // customer_id должен совпадать с запрошенным заказчиком
            return $task['parent_id'] === NULL && $task['customer_id'] == $customer_id;
        });

        // Шаг 2: Если не нужно показывать закрытые — оставляем только активные
        if (!$show_closed) {
            $root_tasks = array_filter($root_tasks, function ($task) {
                // Пропускаем задачи со статусом, отличным от 'active'
                return $task['status'] === 'active';
            });
        }

        // Шаг 3: Сортируем корневые задачи — активные первые, внутри по дате DESC
        usort($root_tasks, function ($a, $b) {
            // Если статусы разные — active идёт первым
            if ($a['status'] !== $b['status']) {
                return $a['status'] === 'active' ? -1 : 1;
            }
            // При одинаковом статусе — новые задачи выше
            return strcmp($b['created_at'], $a['created_at']);
        });

        // Шаг 4: Берём срез пагинации для отображения
        $sliced_root_tasks = array_slice($root_tasks, $offset, $limit);

        // Шаг 5: Строим дерево для выбранного среза корневых задач
        $tree = [];
        foreach ($sliced_root_tasks as $root_task) {
            // Рекурсивно строим дочерние узлы для текущей корневой задачи
            $children = $this->_build_children($raw_tasks, $customer_id, $root_task['id'], $user_id);

            // Подставляем дочерние задачи (пустой массив если нет)
            $root_task['children'] = $children ? $children : [];

            // Вычисляем суммарное время задачи и всех её подзадач
            $total_seconds = $this->CI->Task_model->get_task_time_recursive($root_task['id'], $user_id);

            // Форматируем время в человекочитаемый вид (ч. мин.)
            $hours = floor($total_seconds / 3600);
            $minutes = floor(($total_seconds % 3600) / 60);
            $root_task['formatted_time'] = sprintf(lang('time_format_hours_mins'), $hours, $minutes);

            // Добавляем задачу с деревом в итоговый массив
            $tree[] = $root_task;
        }

        // Шаг 6: Определяем, есть ли ещё задачи за пределами текущей страницы
        $has_more = (count($root_tasks) > ($offset + $limit));

        // Возвращаем структуру дерева и флаг наличия следующей страницы
        return [
            'tree'     => $tree,
            'has_more' => $has_more,
        ];
    }

    /**
     * Рекурсивно строит дочерние узлы дерева задач.
     *
     * Для каждого элемента из $elements с parent_id == $parent_id:
     * - Проверяем принадлежность к заказчику (для корневых)
     * - Рекурсивно ищем вложенные дочерние задачи
     * - Вычисляем formatted_time
     *
     * @param array  $elements     Плоский массив всех задач
     * @param int    $customer_id  ID заказчика (для проверки корневых)
     * @param int    $parent_id    ID родительской задачи для текущего уровня рекурсии
     * @param int    $user_id      ID пользователя (для подсчёта времени)
     * @return array Ветка дерева — массив дочерних задач с вложенными children
     */
    protected function _build_children(array $elements, $customer_id, $parent_id, $user_id)
    {
        // Массив для сбора дочерних узлов текущего уровня
        $branch = [];

        // Перебираем все задачи пользователя в поисках дочерних
        foreach ($elements as $element) {
            // Проверяем, что задача является дочерней для текущего родителя
            if ($element['parent_id'] == $parent_id) {
                // Для корневых задач (parent_id === null) проверяем принадлежность к заказчику
                // Для подзадач — наследуем принадлежность от родителя (не проверяем customer_id)
                if ($parent_id === null && $element['customer_id'] != $customer_id) {
                    // Пропускаем корневую задачу другого заказчика
                    continue;
                }

                // Рекурсивно ищем дочерние задачи для текущего элемента
                $children = $this->_build_children($elements, $customer_id, $element['id'], $user_id);

                // Подставляем дочерние задачи (пустой массив если нет)
                $element['children'] = $children ? $children : [];

                // Вычисляем суммарное время задачи и всех её подзадач
                $total_seconds = $this->CI->Task_model->get_task_time_recursive($element['id'], $user_id);

                // Форматируем время в человекочитаемый вид
                $hours = floor($total_seconds / 3600);
                $minutes = floor(($total_seconds % 3600) / 60);
                $element['formatted_time'] = sprintf(lang('time_format_hours_mins'), $hours, $minutes);

                // Добавляем элемент с деревом в текущую ветку
                $branch[] = $element;
            }
        }

        // Возвращаем собранную ветку дочерних задач
        return $branch;
    }
}
