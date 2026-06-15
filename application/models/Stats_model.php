<?php
// Запрещаем прямой доступ к скрипту, если он вызван в обход ядра CodeIgniter
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Модель Stats_model
 * Специализированный класс для сбора, фильтрации и расчета статистики затраченного времени.
 * Реализует логику каскадного суммирования времени и исключения архивных задач.
 */
class Stats_model extends CI_Model {

    /**
     * Конструктор класса. Вызывает родительский конструктор CI_Model.
     */
    public function __construct() {
        // Инициализируем родительский класс для корректного доступа к ресурсам CI
        parent::__construct();
    }

    /**
     * Возвращает временной срез (Динамику за выбранный период).
     * Группирует все логи времени за период по их корневым проектам.
     * 
     * @param int $user_id ID авторизованного пользователя
     * @param string $start_date Начальная дата диапазона (ГГГГ-ММ-ДД)
     * @param string $end_date Конечная дата диапазона (ГГГГ-ММ-ДД)
     * @param bool $show_archived Флаг отображения архивных (завершенных) задач
     * @return array Массив со статистикой: общее время и список проектов с долей времени
     */
    // Метод возвращает временной срез статистики с поддержкой сортировки и направления
    public function get_time_slice($user_id, $start_date, $end_date, $show_archived, $customer_filters = [], $calculation_filters = [], $spec_filters = [], $sort_by = 'time', $sort_dir = 'desc') {
        // Формируем дату и время начала первого рабочего дня (с 05:00:00)
        $start_date_full = $start_date . ' 05:00:00';
        
        // Вычисляем календарный день, следующий за конечной датой периода
        $next_day = date('Y-m-d', strtotime('+1 day', strtotime($end_date)));
        
        // Формируем дату и время окончания последнего рабочего дня (до 04:59:59 следующего дня)
        $end_date_full = $next_day . ' 04:59:59';

        // Инициализируем массив для хранения связей родительских задач
        $parent_map = [];
        
        // Инициализируем массив для хранения детальных данных о задачах
        $task_map = [];

        // Выбираем поля задачи, включая ID, связь с ТЗ, имя заказчика и дату создания
        $this->db->select('tasks.id, tasks.parent_id, tasks.title, tasks.status, tasks.color, tasks.customer_id, tasks.spec_id, tasks.created_at, customers.name as customer_name');
        
        // Указываем источник данных - таблицу tasks
        $this->db->from('tasks');
        
        // Присоединяем таблицу заказчиков для получения имени заказчика
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        
        // Фильтруем задачи по текущему пользователю в целях изоляции данных
        $this->db->where('tasks.user_id', $user_id);
        
        // Исключаем задачи, помещенные пользователем в корзину (deleted_at не NULL)
        $this->db->where('tasks.deleted_at IS NULL', null, false);
        
        // Выполняем SQL-запрос к базе данных
        $query_tasks = $this->db->get();
        
        // Получаем массив всех активных и завершенных задач в виде ассоциативного массива
        $all_tasks = $query_tasks->result_array();

        // Проходим циклом по всем извлеченным задачам для заполнения карт в памяти
        foreach ($all_tasks as $task) {
            // Запоминаем связь: ID задачи -> ID её родителя (или null для корня)
            $parent_map[$task['id']] = $task['parent_id'];
            
            // Сохраняем полную информацию о задаче по её уникальному идентификатору
            $task_map[$task['id']] = $task;
        }

        // Загружаем маппинг задач по калькуляциям пользователя для фильтрации
        $task_packages_map = [];
        // Выбираем ID задачи и ID пакета калькуляции
        $this->db->select('calculation_package_tasks.task_id, calculation_package_tasks.package_id');
        // Из таблицы связей пакетов и задач
        $this->db->from('calculation_package_tasks');
        // Соединяем с таблицей пакетов калькуляций
        $this->db->join('calculation_packages', 'calculation_packages.id = calculation_package_tasks.package_id');
        // Фильтруем по ID пользователя
        $this->db->where('calculation_packages.user_id', $user_id);
        // Выполняем запрос к БД
        $package_mappings = $this->db->get()->result_array();
        // Заполняем массив связей в памяти
        foreach ($package_mappings as $mapping) {
            // Записываем ID пакета в массив для конкретной задачи
            $task_packages_map[$mapping['task_id']][] = (int)$mapping['package_id'];
        }

        // Запрашиваем из базы данных время сессий и максимальное время окончания сессии за указанный период
        $this->db->select('task_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time) - pause_duration) as duration_sec, MAX(end_time) as max_end_time', false);
        
        // Указываем источник - таблицу сессий времени time_sessions
        $this->db->from('time_sessions');
        
        // Фильтруем сессии по текущему пользователю
        $this->db->where('user_id', $user_id);
        
        // Берем только завершенные сессии времени (у которых есть время окончания)
        $this->db->where('end_time IS NOT NULL', null, false);
        
        // Ограничиваем выборку датой начала (больше или равно началу дня)
        $this->db->where('start_time >=', $start_date_full);
        
        // Ограничиваем выборку датой окончания (меньше или равно концу дня)
        $this->db->where('start_time <=', $end_date_full);
        
        // Группируем результаты по идентификаторам задач для суммирования
        $this->db->group_by('task_id');
        
        // Отправляем запрос в СУБД
        $query_sessions = $this->db->get();
        
        // Получаем плоский список суммарного времени по каждой задаче за период
        $sessions = $query_sessions->result_array();

        // Инициализируем массив для накопления секунд по корневым проектам
        $root_seconds = [];
        // Накопление максимального времени сессии для корневых проектов
        $root_max_end_time = [];
        
        // Инициализируем переменную для расчета суммарного времени за весь период
        $grand_total_seconds = 0;

        // Пробегаем по каждой задаче, по которой было зафиксировано время в периоде
        foreach ($sessions as $session) {
            // Извлекаем ID задачи из текущей сессии
            $tid = $session['task_id'];
            
            // Извлекаем накопленное время сессии в секундах
            $duration = (int)$session['duration_sec'];

            // Если задачи нет в нашей карте (она была удалена из дерева), пропускаем её
            if (!isset($task_map[$tid])) {
                // Прерываем текущую итерацию цикла
                continue;
            }

            // Вычисляем флаг завершенности (архивности) текущей задачи
            $is_task_completed = ($task_map[$tid]['status'] === 'completed');
            // Если включен режим фильтра "Без архивных" (active) или передан старый булевый флаг отключения
            if ($show_archived === 'active' || $show_archived === '0' || $show_archived === 0 || $show_archived === false) {
                // Если сама задача завершена, то исключаем её из выборки времени
                if ($is_task_completed) {
                    // Переходим к следующей записи времени
                    continue;
                }
            } 
            // Если же включен режим фильтра "Только архивные" (archived)
            elseif ($show_archived === 'archived') {
                // Если задача не завершена, то исключаем её из выборки
                if (!$is_task_completed) {
                    // Переходим к следующей записи времени
                    continue;
                }
            }

            // Проверяем фильтрацию по заказчикам (множественный выбор)
            if (!empty($customer_filters)) {
                // Получаем эффективный ID заказчика (с подъемом по иерархии)
                $effective_customer_id = $this->_get_effective_customer_id($tid, $parent_map, $task_map);
                // Флаг совпадения фильтра
                $match_customer = false;
                // Проверяем, есть ли 'none' (без заказчика) и эффективный ID равен null
                if (in_array('none', $customer_filters) && $effective_customer_id === null) {
                    // Устанавливаем флаг совпадения в true
                    $match_customer = true;
                }
                // Проверяем, если эффективный ID не null и присутствует в массиве выбранных заказчиков
                if (!$match_customer && $effective_customer_id !== null && in_array((string)$effective_customer_id, $customer_filters)) {
                    // Устанавливаем флаг совпадения в true
                    $match_customer = true;
                }
                // Если совпадение не найдено, исключаем задачу из результатов
                if (!$match_customer) {
                    // Переходим к следующей итерации цикла
                    continue;
                }
            }

            // Проверяем фильтрацию по пакетам калькуляции (множественный выбор)
            if (!empty($calculation_filters)) {
                // Получаем эффективные ID пакетов калькуляции (с подъемом по иерархии)
                $effective_pkgs = $this->_get_effective_package_ids($tid, $parent_map, $task_packages_map);
                // Флаг совпадения фильтра калькуляций
                $match_calc = false;
                // Проверяем, есть ли 'none' (вне калькуляций) и массив пакетов пуст
                if (in_array('none', $calculation_filters) && empty($effective_pkgs)) {
                    // Устанавливаем флаг совпадения в true
                    $match_calc = true;
                }
                // Проверяем пересечение эффективных пакетов с выбранными фильтрами
                if (!$match_calc && !empty($effective_pkgs)) {
                    // Ищем общие элементы между массивами
                    $intersect = array_intersect($effective_pkgs, array_map('intval', $calculation_filters));
                    // Если пересечение не пустое, то задача подходит
                    if (!empty($intersect)) {
                        // Устанавливаем флаг совпадения в true
                        $match_calc = true;
                    }
                }
                // Если совпадение не найдено, исключаем задачу из результатов
                if (!$match_calc) {
                    // Переходим к следующей итерации цикла
                    continue;
                }
            }

            // Проверяем фильтрацию по ТЗ (техническим заданиям)
            if (!empty($spec_filters)) {
                // Получаем эффективный ID ТЗ (с подъемом по иерархии)
                $effective_spec_id = $this->_get_effective_spec_id($tid, $parent_map, $task_map);
                // Флаг совпадения фильтра ТЗ
                $match_spec = false;
                // Проверяем, есть ли 'none' (вне ТЗ) и эффективный ID ТЗ равен null
                if (in_array('none', $spec_filters) && $effective_spec_id === null) {
                    // Устанавливаем флаг совпадения в true
                    $match_spec = true;
                }
                // Проверяем, если эффективный ID ТЗ не null и присутствует в массиве выбранных ТЗ
                if (!$match_spec && $effective_spec_id !== null && in_array((string)$effective_spec_id, $spec_filters)) {
                    // Устанавливаем флаг совпадения в true
                    $match_spec = true;
                }
                // Если совпадение не найдено, исключаем задачу из результатов
                if (!$match_spec) {
                    // Переходим к следующей итерации цикла
                    continue;
                }
            }

            // Начинаем подъем по иерархии вверх от текущей задачи до её корня
            $curr_id = $tid;
            
            // Переменная для предотвращения зависания при потенциальных циклических ссылках
            $loop_guard = 0;
            
            // Цикл подъема: крутимся, пока у текущей ноды есть родитель в нашей карте
            while (isset($parent_map[$curr_id]) && $parent_map[$curr_id] !== null && $loop_guard < 100) {
                // Сдвигаем указатель текущей задачи на уровень выше к родителю
                $curr_id = $parent_map[$curr_id];
                
                // Инкрементируем счетчик циклов для безопасности
                $loop_guard++;
            }

            // Итоговый $curr_id на выходе из цикла является ID корневого проекта.
            // Если фильтр архива отключен, а корневой проект завершен - исключаем всё его дерево из статистики
            if (!$show_archived && isset($task_map[$curr_id]) && $task_map[$curr_id]['status'] === 'completed') {
                // Пропускаем время этой сессии
                continue;
            }

            // Если корневой проект прошел все валидации, суммируем время к его ID
            if (!isset($root_seconds[$curr_id])) {
                // Инициализируем ячейку нулем, если это первое добавление времени для проекта
                $root_seconds[$curr_id] = 0;
            }
            
            // Добавляем секунды текущей сессии к балансу корневого проекта
            $root_seconds[$curr_id] += $duration;

            // Накапливаем максимальную дату активности для корневого проекта
            $max_end = $session['max_end_time'] ?? null;
            if ($max_end !== null) {
                if (!isset($root_max_end_time[$curr_id]) || strcmp($max_end, $root_max_end_time[$curr_id]) > 0) {
                    $root_max_end_time[$curr_id] = $max_end;
                }
            }
            
            // Увеличиваем общую сумму затраченного времени за период на СУБД-уровне
            $grand_total_seconds += $duration;
        }

        // Инициализируем массив для формирования итогового ответа
        $projects_result = [];

        // Проходим циклом по всем корневым проектам, получившим время в периоде
        foreach ($root_seconds as $root_id => $seconds) {
            // Если проект не найден в карте задач, игнорируем его
            if (!isset($task_map[$root_id])) {
                // Переходим к следующему проекту
                continue;
            }

            // Считаем долю времени проекта в процентах от общей суммы времени
            $pct = $grand_total_seconds > 0 ? ($seconds / $grand_total_seconds) * 100 : 0;

            // Вычисляем количество полных часов
            $h = floor($seconds / 3600);
            
            // Вычисляем количество оставшихся минут
            $m = floor(($seconds % 3600) / 60);

            // Формируем структуру с данными для отображения в представлении
            $projects_result[] = [
                // Идентификатор корневого проекта
                'id' => $root_id,
                // Название корневого проекта
                'title' => $task_map[$root_id]['title'],
                // Дата создания проекта
                'created_at' => $task_map[$root_id]['created_at'],
                // Последняя активность
                'last_activity' => $root_max_end_time[$root_id] ?? null,
                // Установленный цвет проекта для графики и плашек
                'color' => $task_map[$root_id]['color'] ? $task_map[$root_id]['color'] : '#e5e7eb',
                // Время в секундах для сортировки
                'total_seconds' => $seconds,
                // Округленный процент времени до одного знака после запятой
                'percentage' => round($pct, 1),
                // Локализованная строка формата времени (например: "05 ч. 24 мин.")
                'formatted_time' => sprintf($this->lang->line('time_format_hours_mins') ?: '%02d ч. %02d мин.', $h, $m),
                // Имя заказчика (если задано)
                'customer_name' => $task_map[$root_id]['customer_name'] ?? ''
            ];
        }

        // Сортируем полученный список проектов в соответствии с выбранным критерием и направлением
        usort($projects_result, function($a, $b) use ($sort_by, $sort_dir) {
            // Сортировка по дате добавления проекта
            if ($sort_by === 'created') {
                $cmp = strtotime($a['created_at']) <=> strtotime($b['created_at']);
            // Сортировка по последней активности
            } elseif ($sort_by === 'activity') {
                $act_a = $a['last_activity'] ?? '';
                $act_b = $b['last_activity'] ?? '';
                $cmp = strcmp($act_a, $act_b);
            // Если выбран тип сортировки по алфавиту названия
            } elseif ($sort_by === 'title') {
                // Сравниваем названия проектов без учета регистра букв
                $cmp = strcasecmp($a['title'], $b['title']);
            // Если выбран тип сортировки по имени заказчика
            } elseif ($sort_by === 'customer') {
                // Извлекаем имена заказчиков проектов
                $cust_a = $a['customer_name'] ?? '';
                $cust_b = $b['customer_name'] ?? '';
                // Сравниваем имена заказчиков проектов без учета регистра
                $cmp = strcasecmp($cust_a, $cust_b);
            // По умолчанию сортируем по общему количеству времени (обратная совместимость с 'time')
            } else {
                // Сравниваем общее время проектов по возрастанию
                $cmp = $a['total_seconds'] <=> $b['total_seconds'];
            }
            // Если направление сортировки по возрастанию (asc), возвращаем результат сравнения, иначе инвертируем его
            return ($sort_dir === 'asc') ? $cmp : -$cmp;
        });

        // Считаем общие часы для всего периода
        $total_h = floor($grand_total_seconds / 3600);
        
        // Считаем общие минуты для всего периода
        $total_m = floor(($grand_total_seconds % 3600) / 60);

        // Возвращаем итоговый ассоциативный массив для отдачи в контроллер
        return [
            // Итоговая сумма в секундах
            'total_seconds' => $grand_total_seconds,
            // Локализованное общее время периода
            'formatted_total_time' => sprintf($this->lang->line('time_format_hours_mins') ?: '%02d ч. %02d мин.', $total_h, $total_m),
            // Отсортированный массив корневых проектов с расчитанными долями
            'projects' => $projects_result
        ];
    }

    /**
     * Возвращает проектный срез (Абсолютный учёт долгостроев за всё время).
     * Строит иерархическое дерево задач с каскадным суммированием секунд снизу вверх.
     * 
     * @param int $user_id ID авторизованного пользователя
     * @param bool $show_archived Флаг отображения архивных (завершенных) задач
     * @return array Иерархическое дерево проектов и подзадач со временем
     */
    // Метод возвращает дерево проектов (проектный срез) со временем, фильтрами и направлением сортировки
    public function get_project_slice($user_id, $show_archived, $customer_filters = [], $calculation_filters = [], $spec_filters = [], $sort_by = 'time', $sort_dir = 'desc') {
        // Выбираем поля задачи, включая ID, связь с ТЗ, имя заказчика и дату создания
        $this->db->select('tasks.id, tasks.parent_id, tasks.title, tasks.status, tasks.color, tasks.customer_id, tasks.spec_id, tasks.created_at, customers.name as customer_name');
        
        // Указываем таблицу задач
        $this->db->from('tasks');
        
        // Присоединяем таблицу заказчиков
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        
        // Изолируем выборку по пользователю
        $this->db->where('tasks.user_id', $user_id);
        
        // Только те, которые не удалены
        $this->db->where('tasks.deleted_at IS NULL', null, false);
        
        // Запускаем SQL-запрос
        $query_tasks = $this->db->get();
        
        // Сохраняем в плоский массив
        $all_tasks = $query_tasks->result_array();

        // Превращаем массив в индексированную по ID карту задач для быстрого поиска
        $all_tasks_map = [];
        
        // Проходим циклом для переиндексации
        foreach ($all_tasks as $t) {
            // Ключом массива делаем ID задачи
            $all_tasks_map[$t['id']] = $t;
        }

        // Фильтруем задачи на основе активности, статуса архивации и выбранного заказчика
        $valid_tasks = [];
        
        // Инициализируем карту родителей для поиска эффективного заказчика
        $parent_map = [];
        foreach ($all_tasks as $t) {
            $parent_map[$t['id']] = $t['parent_id'];
        }

        // Загружаем маппинг задач по калькуляциям пользователя
        $task_packages_map = [];
        // Выбираем ID задачи и ID пакета калькуляции
        $this->db->select('calculation_package_tasks.task_id, calculation_package_tasks.package_id');
        // Из таблицы связей пакетов и задач
        $this->db->from('calculation_package_tasks');
        // Соединяем с таблицей пакетов калькуляций
        $this->db->join('calculation_packages', 'calculation_packages.id = calculation_package_tasks.package_id');
        // Фильтруем по ID пользователя
        $this->db->where('calculation_packages.user_id', $user_id);
        // Выполняем запрос к БД
        $package_mappings = $this->db->get()->result_array();
        // Заполняем массив связей в памяти
        foreach ($package_mappings as $mapping) {
            // Записываем ID пакета в массив для конкретной задачи
            $task_packages_map[$mapping['task_id']][] = (int)$mapping['package_id'];
        }

        // Цикл проверки каждой задачи на пригодность к показу
        foreach ($all_tasks_map as $id => $task) {
            // Вычисляем, активна ли вся цепочка задачи (нет ли completed элементов среди родителей)
            $is_active_chain = $this->_is_active_chain($id, $all_tasks_map);
            // Если включен режим фильтра "Без архивных" (active) или передан старый булевый флаг отключения
            if ($show_archived === 'active' || $show_archived === '0' || $show_archived === 0 || $show_archived === false) {
                // Если цепочка неактивна (есть завершенные задачи в цепочке), исключаем её
                if (!$is_active_chain) {
                    // Переходим к следующей задаче
                    continue;
                }
            } 
            // Если же включен режим фильтра "Только архивные" (archived)
            elseif ($show_archived === 'archived') {
                // Если вся цепочка активна (нет ни одной завершенной задачи), исключаем её
                if ($is_active_chain) {
                    // Переходим к следующей задаче
                    continue;
                }
            }

            // 2. Проверяем фильтрацию по заказчикам (множественный выбор)
            if (!empty($customer_filters)) {
                // Получаем эффективный ID заказчика (с подъемом по иерархии)
                $effective_customer_id = $this->_get_effective_customer_id($id, $parent_map, $all_tasks_map);
                // Флаг совпадения фильтра
                $match_customer = false;
                // Проверяем, есть ли 'none' (без заказчика) и эффективный ID равен null
                if (in_array('none', $customer_filters) && $effective_customer_id === null) {
                    // Устанавливаем флаг совпадения в true
                    $match_customer = true;
                }
                // Проверяем, если эффективный ID не null и присутствует в массиве выбранных заказчиков
                if (!$match_customer && $effective_customer_id !== null && in_array((string)$effective_customer_id, $customer_filters)) {
                    // Устанавливаем флаг совпадения в true
                    $match_customer = true;
                }
                // Если совпадение не найдено, исключаем задачу из результатов
                if (!$match_customer) {
                    // Переходим к следующему элементу
                    continue;
                }
            }

            // 3. Проверяем фильтрацию по калькуляциям
            if (!empty($calculation_filters)) {
                // Получаем эффективные ID пакетов калькуляции (с подъемом по иерархии)
                $effective_pkgs = $this->_get_effective_package_ids($id, $parent_map, $task_packages_map);
                // Флаг совпадения фильтра калькуляций
                $match_calc = false;
                // Проверяем, есть ли 'none' (вне калькуляций) и массив пакетов пуст
                if (in_array('none', $calculation_filters) && empty($effective_pkgs)) {
                    // Устанавливаем флаг совпадения в true
                    $match_calc = true;
                }
                // Проверяем пересечение эффективных пакетов с выбранными фильтрами
                if (!$match_calc && !empty($effective_pkgs)) {
                    // Ищем общие элементы между массивами
                    $intersect = array_intersect($effective_pkgs, array_map('intval', $calculation_filters));
                    // Если пересечение не пустое, то задача подходит
                    if (!empty($intersect)) {
                        // Устанавливаем флаг совпадения в true
                        $match_calc = true;
                    }
                }
                // Если совпадение не найдено, исключаем задачу из результатов
                if (!$match_calc) {
                    // Переходим к следующему элементу
                    continue;
                }
            }

            // 4. Проверяем фильтрацию по ТЗ
            if (!empty($spec_filters)) {
                // Получаем эффективный ID ТЗ (с подъемом по иерархии)
                $effective_spec_id = $this->_get_effective_spec_id($id, $parent_map, $all_tasks_map);
                // Флаг совпадения фильтра ТЗ
                $match_spec = false;
                // Проверяем, есть ли 'none' (вне ТЗ) и эффективный ID ТЗ равен null
                if (in_array('none', $spec_filters) && $effective_spec_id === null) {
                    // Устанавливаем флаг совпадения в true
                    $match_spec = true;
                }
                // Проверяем, если эффективный ID ТЗ не null и присутствует в массиве выбранных ТЗ
                if (!$match_spec && $effective_spec_id !== null && in_array((string)$effective_spec_id, $spec_filters)) {
                    // Устанавливаем флаг совпадения в true
                    $match_spec = true;
                }
                // Если совпадение не найдено, исключаем задачу из результатов
                if (!$match_spec) {
                    // Переходим к следующему элементу
                    continue;
                }
            }

            // Если все проверки пройдены, добавляем задачу в список валидных
            $valid_tasks[$id] = $task;
        }

        // Получаем суммарное время и максимальную дату активности по всем сессиям за всё время для каждой задачи
        $this->db->select('task_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time) - pause_duration) as duration_sec, MAX(end_time) as max_end_time', false);
        
        // Таблица сессий
        $this->db->from('time_sessions');
        
        // Фильтрация по владельцу
        $this->db->where('user_id', $user_id);
        
        // Только завершенные сессии с установленным временем окончания
        $this->db->where('end_time IS NOT NULL', null, false);
        
        // Группируем по ID задач
        $this->db->group_by('task_id');
        
        // Выполняем запрос
        $query_sessions = $this->db->get();
        
        // Сохраняем результаты
        $raw_sessions = $query_sessions->result_array();

        // Формируем карту direct_times: ID задачи -> секунды, записанные напрямую на неё
        $direct_times = [];
        // Карта максимального времени активности для каждой задачи
        $direct_max_end_times = [];
        
        // Цикл заполнения карт
        foreach ($raw_sessions as $s) {
            // Привязываем сумму секунд к идентификатору задачи
            $direct_times[$s['task_id']] = (int)$s['duration_sec'];
            $direct_max_end_times[$s['task_id']] = $s['max_end_time'];
        }

        // Группируем валидные задачи по родителям и находим корни дерева
        $tasks_by_parent = [];
        
        // Массив для хранения идентификаторов корневых элементов
        $root_ids = [];

        // Проходим по списку прошедших фильтрацию задач
        foreach ($valid_tasks as $id => $task) {
            // Считываем ID родителя
            $pid = $task['parent_id'];

            // Если у задачи есть родитель и этот родитель также находится в списке валидных задач
            if ($pid !== null && isset($valid_tasks[$pid])) {
                // Кладем задачу в массив детей этого родителя
                $tasks_by_parent[$pid][] = $task;
            } else {
                // Если родителя нет или он невалиден (архивирован/удален), задача становится корнем дерева
                $root_ids[] = $id;
            }
        }

        // Инициализируем массив для сборки результирующего дерева
        $tree = [];

        // Проходим по всем определенным корневым элементам
        foreach ($root_ids as $rid) {
            // Рекурсивно собираем дерево с каскадным суммированием времени с нижних уровней, передавая сортировку и направление
            $tree[] = $this->_build_tree_node($rid, $valid_tasks, $tasks_by_parent, $direct_times, $direct_max_end_times, $sort_by, $sort_dir);
        }

        // Сортируем корневые проекты на Уровне 1 в соответствии с выбранным критерием и направлением
        usort($tree, function($a, $b) use ($sort_by, $sort_dir) {
            // Сортировка по дате добавления проекта
            if ($sort_by === 'created') {
                $cmp = strtotime($a['created_at']) <=> strtotime($b['created_at']);
            // Сортировка по последней активности
            } elseif ($sort_by === 'activity') {
                $act_a = $a['last_activity'] ?? '';
                $act_b = $b['last_activity'] ?? '';
                $cmp = strcmp($act_a, $act_b);
            // Если выбран тип сортировки по алфавиту названия
            } elseif ($sort_by === 'title') {
                // Сравниваем названия проектов без учета регистра букв
                $cmp = strcasecmp($a['title'], $b['title']);
            // Если выбран тип сортировки по имени заказчика
            } elseif ($sort_by === 'customer') {
                // Извлекаем имена заказчиков проектов
                $cust_a = $a['customer_name'] ?? '';
                $cust_b = $b['customer_name'] ?? '';
                // Сравниваем имена заказчиков проектов без учета регистра
                $cmp = strcasecmp($cust_a, $cust_b);
            // По умолчанию сортируем по общему количеству времени (обратная совместимость с 'time')
            } else {
                // Сравниваем общее время проектов по возрастанию
                $cmp = $a['total_seconds'] <=> $b['total_seconds'];
            }
            // Если направление сортировки по возрастанию (asc), возвращаем результат сравнения, иначе инвертируем его
            return ($sort_dir === 'asc') ? $cmp : -$cmp;
        });

        // Возвращаем собранное и отсортированное дерево проектов
        return $tree;
    }

    /**
     * Вспомогательный рекурсивный метод для сборки узла дерева и подсчета каскадного времени.
     * 
     * @param int $id ID текущей задачи
     * @param array $valid_tasks Карта валидных задач
     * @param array $tasks_by_parent Связи родитель-дети
     * @param array $direct_times Карта прямого времени задач
     * @param array $direct_max_end_times Карта максимального времени активности
     * @param string $sort_by Тип сортировки
     * @return array Собранный узел со вложенными детьми и просуммированным временем
     */
    private function _build_tree_node($id, $valid_tasks, $tasks_by_parent, $direct_times, $direct_max_end_times, $sort_by, $sort_dir = 'desc') {
        // Берём исходные данные текущей задачи
        $node = $valid_tasks[$id];

        // Инициализируем массив для хранения вложенных дочерних узлов
        $children_nodes = [];
        
        // Сумма секунд, переданная снизу от дочерних подзадач
        $child_seconds_sum = 0;

        // Прямое максимальное время окончания сессии для этой задачи
        $my_max_end = $direct_max_end_times[$id] ?? null;
        $max_activity = $my_max_end;

        // Если у этой задачи есть зарегистрированные валидные дети
        if (isset($tasks_by_parent[$id])) {
            // Проходим по каждому ребенку в цикле
            foreach ($tasks_by_parent[$id] as $child_task) {
                // Рекурсивно строим дочерний узел, передавая параметры
                $child_node = $this->_build_tree_node($child_task['id'], $valid_tasks, $tasks_by_parent, $direct_times, $direct_max_end_times, $sort_by, $sort_dir);
                
                // Добавляем построенного ребенка в массив детей текущей задачи
                $children_nodes[] = $child_node;
                
                // Суммируем каскадное время ребенка к общему балансу текущей задачи
                $child_seconds_sum += $child_node['total_seconds'];

                // Вычисляем максимальную активность среди детей
                $child_act = $child_node['last_activity'] ?? null;
                if ($child_act !== null && ($max_activity === null || strcmp($child_act, $max_activity) > 0)) {
                    $max_activity = $child_act;
                }
            }
        }

        // Сортируем детей в соответствии с выбранным типом сортировки и направлением
        usort($children_nodes, function($a, $b) use ($sort_by, $sort_dir) {
            // Сортировка по дате добавления
            if ($sort_by === 'created') {
                $cmp = strtotime($a['created_at']) <=> strtotime($b['created_at']);
            // Сортировка по последней активности
            } elseif ($sort_by === 'activity') {
                $act_a = $a['last_activity'] ?? '';
                $act_b = $b['last_activity'] ?? '';
                $cmp = strcmp($act_a, $act_b);
            // Если сортировка по алфавиту названия задачи
            } elseif ($sort_by === 'title') {
                // Сравниваем названия проектов без учета регистра букв
                $cmp = strcasecmp($a['title'], $b['title']);
            // Если сортировка по имени заказчика
            } elseif ($sort_by === 'customer') {
                // Сравниваем имена заказчиков проектов без учета регистра
                $cust_a = $a['customer_name'] ?? '';
                $cust_b = $b['customer_name'] ?? '';
                $cmp = strcasecmp($cust_a, $cust_b);
            // По умолчанию по общему времени (обратная совместимость с 'time')
            } else {
                // Сравниваем по общему времени по возрастанию
                $cmp = $a['total_seconds'] <=> $b['total_seconds'];
            }
            // Если направление сортировки по возрастанию (asc), возвращаем результат сравнения, иначе инвертируем его
            return ($sort_dir === 'asc') ? $cmp : -$cmp;
        });

        // Прямое время, записанное на саму эту задачу (без детей)
        $direct_seconds = isset($direct_times[$id]) ? $direct_times[$id] : 0;

        // Полное каскадное время: прямое время задачи + сумма всех её подзадач
        $total_seconds = $direct_seconds + $child_seconds_sum;

        // Рассчитываем полные часы
        $hours = floor($total_seconds / 3600);
        
        // Рассчитываем минуты
        $minutes = floor(($total_seconds % 3600) / 60);

        // Добавляем к узлу рассчитанное каскадное время в секундах
        $node['total_seconds'] = $total_seconds;
        
        // Добавляем дату последней активности
        $node['last_activity'] = $max_activity;
        
        // Добавляем красивый локализованный формат времени
        $node['formatted_time'] = sprintf($this->lang->line('time_format_hours_mins') ?: '%02d ч. %02d мин.', $hours, $minutes);
        
        // Прикрепляем отсортированный массив детей
        $node['children'] = $children_nodes;

        // Возвращаем сформированный узел дерева вверх по стеку рекурсии
        return $node;
    }

    /**
     * Вспомогательный метод для рекурсивной проверки цепочки активности задачи.
     * Проверяет, что сама задача и все её родительские задачи имеют статус 'active'.
     * 
     * @param int $id ID проверяемой задачи
     * @param array $all_tasks_map Карта всех задач пользователя в памяти
     * @return bool True если вся цепочка активна, false если есть хотя бы одна завершенная задача
     */
    private function _is_active_chain($id, $all_tasks_map) {
        // Устанавливаем текущий ID для цикла проверки
        $curr_id = $id;
        
        // Защита от бесконечного цикла при аномалиях дерева
        $loop_guard = 0;

        // Проходим вверх по дереву до самого корня
        while ($curr_id !== null && $loop_guard < 100) {
            // Если текущей ноды нет в карте, значит дерево повреждено, прерываем с ошибкой
            if (!isset($all_tasks_map[$curr_id])) {
                // Считаем цепочку невалидной
                return false;
            }

            // Если статус текущей задачи (или любого родителя выше) равен completed - цепочка неактивна
            if ($all_tasks_map[$curr_id]['status'] === 'completed') {
                // Возвращаем ложь
                return false;
            }

            // Переходим к родителю текущей задачи
            $curr_id = $all_tasks_map[$curr_id]['parent_id'];
            
            // Инкрементируем счетчик циклов
            $loop_guard++;
        }

        // Если все проверки пройдены и completed статус не встретился, цепочка активна
        return true;
    }

    /**
     * Вспомогательный метод для определения эффективного заказчика задачи.
     * Ищет заказчика у самой задачи или поднимается по дереву к родительским задачам.
     * 
     * @param int $task_id ID задачи
     * @param array $parent_map Карта связей родитель-ребенок
     * @param array $task_map Карта всех задач
     * @return int|null ID заказчика или null, если заказчик не найден
     */
    private function _get_effective_customer_id($task_id, $parent_map, $task_map) {
        // Устанавливаем текущий ID для подъема по иерархии
        $curr_id = $task_id;
        
        // Защитный счетчик от бесконечных циклов
        $loop_guard = 0;
        
        // Поднимаемся по дереву задач вверх
        while ($curr_id !== null && $loop_guard < 100) {
            // Проверяем наличие задачи в карте
            if (isset($task_map[$curr_id])) {
                // Если у задачи заполнен ID заказчика, возвращаем его
                if (!empty($task_map[$curr_id]['customer_id'])) {
                    return (int)$task_map[$curr_id]['customer_id'];
                }
                // Переходим к родителю
                $curr_id = $parent_map[$curr_id] ?? null;
            } else {
                // Прерываем поиск при выходе за пределы карты
                break;
            }
            // Инкрементируем счетчик шагов
            $loop_guard++;
        }
        
        // Возвращаем null, если заказчик не задан во всей цепочке
        return null;
    }

    /**
     * Вспомогательный метод для определения эффективного ТЗ (технического задания) задачи.
     * Ищет привязанное ТЗ у самой задачи или поднимается по дереву к родительским задачам.
     */
    private function _get_effective_spec_id($task_id, $parent_map, $task_map) {
        // Устанавливаем текущий ID для подъема по иерархии
        $curr_id = $task_id;
        
        // Защитный счетчик от бесконечных циклов
        $loop_guard = 0;
        
        // Поднимаемся по дереву задач вверх
        while ($curr_id !== null && $loop_guard < 100) {
            // Проверяем наличие задачи в карте
            if (isset($task_map[$curr_id])) {
                // Если у задачи заполнен ID ТЗ, возвращаем его
                if (!empty($task_map[$curr_id]['spec_id'])) {
                    return (int)$task_map[$curr_id]['spec_id'];
                }
                // Переходим к родителю
                $curr_id = $parent_map[$curr_id] ?? null;
            } else {
                // Прерываем поиск при выходе за пределы карты
                break;
            }
            // Инкрементируем счетчик шагов
            $loop_guard++;
        }
        
        // Возвращаем null, если ТЗ не задано во всей цепочке
        return null;
    }

    /**
     * Вспомогательный метод для определения эффективных ID пакетов калькуляций задачи.
     * Собирает все пакеты калькуляций, к которым привязана сама задача или её родительские задачи.
     */
    private function _get_effective_package_ids($task_id, $parent_map, $task_packages_map) {
        // Устанавливаем текущий ID для подъема по иерархии
        $curr_id = $task_id;
        
        // Защитный счетчик от бесконечных циклов
        $loop_guard = 0;
        
        // Массив для хранения всех найденных ID пакетов
        $pkg_ids = [];
        
        // Поднимаемся по дереву задач вверх
        while ($curr_id !== null && $loop_guard < 100) {
            // Если для текущей задачи есть привязанные пакеты калькуляций
            if (isset($task_packages_map[$curr_id])) {
                // Слияние найденных пакетов с результирующим массивом
                $pkg_ids = array_merge($pkg_ids, $task_packages_map[$curr_id]);
            }
            // Переходим к родителю
            $curr_id = $parent_map[$curr_id] ?? null;
            
            // Инкрементируем счетчик шагов
            $loop_guard++;
        }
        
        // Возвращаем массив уникальных ID пакетов калькуляций
        return array_unique($pkg_ids);
    }
}
