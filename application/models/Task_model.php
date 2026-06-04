<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Модель для работы с задачами и сессиями времени.
 * Все методы жестко фильтруют данные по $user_id (Изоляция).
 */
class Task_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Загрузка базы данных происходит автоматически (autoload)
    }

    /**
     * Получить все задачи пользователя.
     * Возвращает плоский массив, который потом можно преобразовать в дерево в контроллере.
     */
    public function get_user_tasks($user_id) {
        $this->db->select('tasks.*, customers.name as customer_name');
        $this->db->from('tasks');
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        // Жесткая фильтрация по пользователю
        $this->db->where('tasks.user_id', $user_id);
        // Сортировка по времени создания по убыванию
        $this->db->order_by('tasks.created_at', 'ASC');
        $query = $this->db->get();

        // Возвращаем результат в виде ассоциативного массива
        return $query->result_array();
    }

    /**
     * Создать новую задачу или подзадачу.
     */
    public function add_task($user_id, $parent_id, $title, $customer_id = NULL, $is_fixed_price = 0, $price = 0) {
        // Подготавливаем данные для вставки
        $data = [
            'user_id' => $user_id,
            'title' => $title,
            'parent_id' => empty($parent_id) ? NULL : $parent_id,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'customer_id' => empty($customer_id) ? NULL : $customer_id,
            'is_fixed_price' => $is_fixed_price ? 1 : 0,
            'price' => (float)$price
        ];

        // Выполняем вставку через Query Builder
        return $this->db->insert('tasks', $data);
    }

    /**
     * Получить текущую активную сессию (где end_time IS NULL).
     * Возвращает данные сессии + название задачи.
     */
    public function get_active_session($user_id) {
        // Выбираем все поля сессии и только название/цвет из задач
        $this->db->select('time_sessions.*, tasks.title as task_title, tasks.color');
        $this->db->from('time_sessions');
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        
        // Фильтруем по пользователю и ищем ту сессию, которая не остановлена
        $this->db->where('time_sessions.user_id', $user_id);
        $this->db->where('time_sessions.end_time IS NULL', null, false);
        
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * Остановить активный таймер пользователя.
     * Возвращает true (успешно), 'spam' (сессия меньше минуты и была удалена), или false (ошибка).
     */
    public function stop_timer($user_id, $note = '') {
        // Сначала получаем активную сессию, чтобы проверить её длительность
        $active_session = $this->get_active_session($user_id);
        if (!$active_session) {
            return false;
        }

        $end_time = date('Y-m-d H:i:s');
        $duration = strtotime($end_time) - strtotime($active_session['start_time']);

        // Анти-спам: если меньше 60 секунд, удаляем сессию физически
        if ($duration < 60) {
            $this->db->where('id', $active_session['id']);
            $this->db->delete('time_sessions');
            return 'spam';
        }

        // Иначе обычное сохранение
        $this->db->set('end_time', $end_time);
        if (!empty($note)) {
            $this->db->set('note', $note);
        }
        
        $this->db->where('id', $active_session['id']);
        $this->db->update('time_sessions');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Запустить новый таймер для конкретной задачи.
     */
    public function start_timer($user_id, $task_id) {
        // Шаг 1: Проверяем, принадлежит ли задача этому пользователю
        $this->db->where('id', $task_id);
        $this->db->where('user_id', $user_id);
        $task = $this->db->get('tasks')->row_array();

        if (!$task) {
            return false; // Задачи нет или она чужая
        }

        // Шаг 2: Останавливаем любой другой активный таймер (чтобы не плодить параллельные)
        $this->stop_timer($user_id);

        // Шаг 3: Создаем новую запись о сессии (старт - сейчас, стоп - NULL)
        $data = [
            'user_id' => $user_id,
            'task_id' => $task_id,
            'start_time' => date('Y-m-d H:i:s'),
            'end_time' => NULL
        ];
        
        $this->db->insert('time_sessions', $data);
        return $this->db->insert_id();
    }

    /**
     * Рекурсивный подсчет времени задачи.
     * Считает время самой задачи, затем ищет все подзадачи и прибавляет их время тоже.
     * Возвращает общее количество секунд.
     */
    public function get_task_time_recursive($task_id, $user_id) {
        $total_seconds = 0;

        // 1. Считаем время для самой этой задачи (только завершенные сессии)
        // Используем встроенную функцию MySQL TIMESTAMPDIFF для подсчета разницы в секундах
        $this->db->select('SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as time_sum');
        $this->db->from('time_sessions');
        $this->db->where('task_id', $task_id);
        $this->db->where('user_id', $user_id);
        $this->db->where('end_time IS NOT NULL', null, false);
        $query = $this->db->get();
        $row = $query->row_array();

        // Прибавляем найденное время (если сессий не было, MySQL вернет NULL, поэтому приводим к числу)
        if (!empty($row['time_sum'])) {
            $total_seconds += (int)$row['time_sum'];
        }

        // 2. Ищем все дочерние задачи (подзадачи)
        $this->db->where('parent_id', $task_id);
        $this->db->where('user_id', $user_id);
        $children = $this->db->get('tasks')->result_array();

        // 3. Запускаем цикл по всем подзадачам и рекурсивно вызываем этот же метод
        foreach ($children as $child) {
            // Результат выполнения функции для подзадачи прибавляем к нашему общему времени
            $total_seconds += $this->get_task_time_recursive($child['id'], $user_id);
        }

        // Возвращаем итоговую сумму в секундах
        return $total_seconds;
    }

    /**
     * Массовое завершение задачи (Кнопка "Готово").
     * Проставляет статус completed текущей задаче и всем её подзадачам.
     * Принудительно останавливает таймер, если он был запущен на какой-то из них.
     */
    public function complete_task_recursive($task_id, $user_id) {
        // 1. Проставляем статус 'completed' для самой задачи
        $this->db->set('status', 'completed');
        $this->db->where('id', $task_id);
        $this->db->where('user_id', $user_id);
        $this->db->update('tasks');

        // 2. Принудительно останавливаем таймер для этой задачи, если он был запущен
        $this->db->set('end_time', date('Y-m-d H:i:s'));
        $this->db->where('task_id', $task_id);
        $this->db->where('user_id', $user_id);
        $this->db->where('end_time IS NULL', null, false);
        $this->db->update('time_sessions');

        // 3. Ищем все дочерние задачи (подзадачи)
        $this->db->where('parent_id', $task_id);
        $this->db->where('user_id', $user_id);
        $children = $this->db->get('tasks')->result_array();

        // 4. Запускаем цикл по подзадачам и рекурсивно завершаем каждую из них
        foreach ($children as $child) {
            // Вызываем этот же метод для ребенка (он завершит его и остановит его таймер)
            $this->complete_task_recursive($child['id'], $user_id);
        }

        // Возвращаем true как признак успешного выполнения
        return true;
    }

    /**
     * Восстановление задачи (смена статуса на active).
     * Восстанавливает задачу и все её подзадачи рекурсивно.
     */
    public function restore_task_recursive($task_id, $user_id) {
        $this->db->set('status', 'active');
        $this->db->where('id', $task_id);
        $this->db->where('user_id', $user_id);
        $this->db->update('tasks');

        $this->db->where('parent_id', $task_id);
        $this->db->where('user_id', $user_id);
        $children = $this->db->get('tasks')->result_array();

        foreach ($children as $child) {
            $this->restore_task_recursive($child['id'], $user_id);
        }

        // Восстанавливаем родителя, если он есть, чтобы задача не висела в скрытом родительском проекте
        $this->db->select('parent_id');
        $this->db->where('id', $task_id);
        $this->db->where('user_id', $user_id);
        $task = $this->db->get('tasks')->row_array();
        if (!empty($task['parent_id'])) {
            $this->db->set('status', 'active');
            $this->db->where('id', $task['parent_id']);
            $this->db->where('user_id', $user_id);
            $this->db->update('tasks');
        }

        return true;
    }

    /**
     * Получить глобальную историю сессий (Журнал активности)
     */
    public function get_global_history($user_id) {
        $this->db->select('
            time_sessions.*, 
            tasks.title as task_title, 
            tasks.color,
            TIMESTAMPDIFF(SECOND, time_sessions.start_time, time_sessions.end_time) as duration_seconds
        ');
        $this->db->from('time_sessions');
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        
        $this->db->where('time_sessions.user_id', $user_id);
        $this->db->where('time_sessions.end_time IS NOT NULL', null, false);
        
        // Сортируем от самых новых к старым (по дате завершения)
        $this->db->order_by('time_sessions.end_time', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Рекурсивно получить ID задачи и всех её подзадач
     */
    public function get_task_and_children_ids($task_id) {
        $ids = [$task_id];
        
        // Получаем прямых детей
        $this->db->select('id');
        $this->db->from('tasks');
        $this->db->where('parent_id', $task_id);
        $children = $this->db->get()->result_array();
        
        foreach ($children as $child) {
            $child_ids = $this->get_task_and_children_ids($child['id']);
            $ids = array_merge($ids, $child_ids);
        }
        
        return $ids;
    }

    /**
     * Получить каскадную историю для массива ID задач
     */
    public function get_cascading_history($task_ids_array, $user_id) {
        if (empty($task_ids_array)) {
            return [];
        }

        $this->db->select('
            time_sessions.*, 
            tasks.title as task_title, 
            tasks.color,
            TIMESTAMPDIFF(SECOND, time_sessions.start_time, time_sessions.end_time) as duration_seconds
        ');
        $this->db->from('time_sessions');
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        
        $this->db->where('time_sessions.user_id', $user_id);
        $this->db->where_in('time_sessions.task_id', $task_ids_array);
        $this->db->where('time_sessions.end_time IS NOT NULL', null, false);
        
        $this->db->order_by('time_sessions.end_time', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Добавление сессии вручную (задним числом).
     */
    public function add_manual_session($user_id, $task_id, $start_time, $end_time, $note = '') {
        $data = [
            'user_id' => $user_id,
            'task_id' => $task_id,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'note' => $note
        ];
        return $this->db->insert('time_sessions', $data);
    }

    /**
     * Получить список сессий конкретной задачи (для вывода в модальном окне).
     * Берем все завершенные сессии и сортируем по убыванию даты.
     */
    public function get_task_sessions($user_id, $task_id) {
        $this->db->where('task_id', $task_id);
        $this->db->where('user_id', $user_id);
        $this->db->where('end_time IS NOT NULL', null, false);
        $this->db->order_by('start_time', 'DESC');
        // Для простоты и KISS выводим последние 50 сессий (обычно этого хватает)
        $this->db->limit(50);
        return $this->db->get('time_sessions')->result_array();
    }

    /**
     * Полное удаление ошибочной сессии времени.
     * Обязательно проверяем user_id для безопасности.
     */
    public function delete_session($session_id, $user_id) {
        $this->db->where('id', $session_id);
        $this->db->where('user_id', $user_id);
        $this->db->delete('time_sessions');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Обновление деталей задачи (Название, Клиент, Финансы)
     */
    public function update_task_details($task_id, $user_id, $new_title, $customer_id, $is_fixed_price, $price) {
        $this->db->set('title', $new_title);
        $this->db->set('customer_id', empty($customer_id) ? NULL : $customer_id);
        $this->db->set('is_fixed_price', $is_fixed_price ? 1 : 0);
        $this->db->set('price', (float)$price);
        
        $this->db->where('id', $task_id);
        $this->db->where('user_id', $user_id);
        $this->db->update('tasks');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Удаление задачи (каскадное).
     * Благодаря ON DELETE CASCADE в БД, удаление корневой задачи повлечет удаление подзадач и сессий.
     */
    public function delete_task_cascade($task_id, $user_id) {
        $this->db->where('id', $task_id);
        $this->db->where('user_id', $user_id);
        $this->db->delete('tasks');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Получение отчета по времени за выбранный период
     */
    public function get_time_report($user_id, $start_date, $end_date) {
        // Добавляем к конечной дате 23:59:59, чтобы захватить весь день
        $end_date_full = $end_date . ' 23:59:59';
        $start_date_full = $start_date . ' 00:00:00';

        $this->db->select('tasks.id, tasks.title, tasks.color, SUM(TIMESTAMPDIFF(SECOND, time_sessions.start_time, time_sessions.end_time)) as total_seconds');
        $this->db->from('time_sessions');
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        
        // Фильтруем по пользователю
        $this->db->where('time_sessions.user_id', $user_id);
        
        // Только завершенные сессии
        $this->db->where('time_sessions.end_time IS NOT NULL', null, false);
        
        // Временной диапазон
        $this->db->where('time_sessions.start_time >=', $start_date_full);
        $this->db->where('time_sessions.start_time <=', $end_date_full);
        
        // Группируем по задаче
        $this->db->group_by('tasks.id');
        
        // Сортируем по убыванию затраченного времени (самые долгие сверху)
        $this->db->order_by('total_seconds', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Получение отчета по времени с группировкой по дням
     */
    public function get_time_report_grouped($user_id, $start_date, $end_date) {
        $end_date_full = $end_date . ' 23:59:59';
        $start_date_full = $start_date . ' 00:00:00';
        // Группируем по DATE(start_time) и task_id
        $this->db->select('DATE(time_sessions.start_time) as report_date, tasks.id, tasks.title, tasks.color, tasks.is_fixed_price, tasks.price, customers.name as customer_name, SUM(TIMESTAMPDIFF(SECOND, time_sessions.start_time, time_sessions.end_time)) as total_seconds');
        $this->db->from('time_sessions');
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        
        $this->db->where('time_sessions.user_id', $user_id);
        $this->db->where('time_sessions.end_time IS NOT NULL', null, false);
        $this->db->where('time_sessions.start_time >=', $start_date_full);
        $this->db->where('time_sessions.start_time <=', $end_date_full);
        
        $this->db->group_by('DATE(time_sessions.start_time), tasks.id');
        $this->db->order_by('report_date', 'DESC');
        $this->db->order_by('total_seconds', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Получить архивные проекты (статус completed, корневые)
     */
    public function get_archived_projects($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('status', 'completed');
        $this->db->where('parent_id IS NULL', null, false);
        $this->db->order_by('created_at', 'DESC');
        $projects = $this->db->get('tasks')->result_array();

        // Считаем общее время для каждого проекта
        foreach ($projects as &$project) {
            $project['total_seconds'] = $this->get_task_time_recursive($project['id'], $user_id);
        }

        return $projects;
    }

    /**
     * Установить цвет задачи
     */
    public function set_task_color($task_id, $user_id, $color) {
        $this->db->set('color', $color);
        $this->db->where('id', $task_id);
        $this->db->where('user_id', $user_id);
        $this->db->update('tasks');
        return $this->db->affected_rows() > 0;
    }
}
