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

        // Автоматическая миграция: поле подробного описания задачи
        if (!$this->db->field_exists('description', 'tasks')) {
            $this->db->query("ALTER TABLE tasks ADD COLUMN description TEXT NULL DEFAULT NULL AFTER spec_id");
        }

        // Автоматическая миграция: поле для проверки отвала фронтенда (пульс)
        if (!$this->db->field_exists('last_heartbeat', 'time_sessions')) {
            $this->db->query("ALTER TABLE time_sessions ADD COLUMN last_heartbeat DATETIME NULL DEFAULT NULL AFTER end_time");
        }
    }

    /**
     * Получить все задачи пользователя.
     * Возвращает плоский массив, который потом можно преобразовать в дерево в контроллере.
     */
    public function get_user_tasks($user_id) {
        $this->db->select('tasks.*, customers.name as customer_name, customer_specs.title as spec_title');
        $this->db->from('tasks');
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        $this->db->join('customer_specs', 'customer_specs.id = tasks.spec_id', 'left');
        // Жесткая фильтрация по пользователю
        $this->db->where('tasks.user_id', $user_id);
        // Исключаем задачи в корзине
        $this->db->where('tasks.deleted_at IS NULL', null, false);
        // Сортировка по времени создания по убыванию
        $this->db->order_by('tasks.created_at', 'ASC');
        $query = $this->db->get();

        // Возвращаем результат в виде ассоциативного массива
        return $query->result_array();
    }

    /**
     * Получить одну задачу по её ID и ID пользователя.
     */
    public function get_task($task_id, $user_id) {
        $this->db->where('id', $task_id);
        $this->db->where('user_id', $user_id);
        return $this->db->get('tasks')->row_array();
    }

    public function add_task($user_id, $parent_id, $title, $customer_id = NULL, $is_fixed_price = 0, $price = 0, $spec_id = NULL, $description = NULL) {
        // Если при создании подзадачи заказчик не передан, наследуем его от родительской задачи
        if (empty($customer_id) && !empty($parent_id)) {
            $parent = $this->db->select('customer_id')->where('id', $parent_id)->get('tasks')->row_array();
            if ($parent && !empty($parent['customer_id'])) {
                $customer_id = $parent['customer_id'];
            }
        }

        // Подготавливаем данные для вставки
        $data = [
            'user_id' => $user_id,
            'title' => $title,
            'parent_id' => empty($parent_id) ? NULL : $parent_id,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'customer_id' => empty($customer_id) ? NULL : $customer_id,
            'is_fixed_price' => $is_fixed_price ? 1 : 0,
            'price' => (float)$price,
            'spec_id' => empty($spec_id) ? NULL : $spec_id,
            'description' => empty($description) ? NULL : $description
        ];

        // Выполняем вставку через Query Builder
        return $this->db->insert('tasks', $data);
    }

    /**
     * Получить текущую активную сессию (где end_time IS NULL).
     * Возвращает данные сессии + название задачи.
     */
    public function get_active_session($user_id, $skip_limit_check = false) {
        // Выбираем все поля сессии и только название/цвет из задач
        $this->db->select('time_sessions.*, tasks.title as task_title, tasks.color');
        $this->db->from('time_sessions');
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        
        // Фильтруем по пользователю и ищем ту сессию, которая не остановлена
        $this->db->where('time_sessions.user_id', $user_id);
        $this->db->where('time_sessions.end_time IS NULL', null, false);
        
        $query = $this->db->get();
        $session = $query->row_array();
        
        if ($session) {
            // Проверка на превышение лимита паузы
            if (!$skip_limit_check && $session['is_paused']) {
                $this->load->model('Settings_model');
                $limit_minutes = (int)$this->Settings_model->get_setting('pause_limit_minutes', 10);
                $pause_seconds = time() - strtotime($session['last_paused_at']);
                
                if ($limit_minutes > 0 && $pause_seconds > ($limit_minutes * 60)) {
                    // Лимит превышен! Автоматически останавливаем сессию
                    $this->stop_timer($user_id, 'Авто-стоп по лимиту паузы');
                    return null; // Сессия больше не активна
                }
            }

            // Проверка на обрыв связи (отсутствие пульса более 3 минут)
            if (!$skip_limit_check && !$session['is_paused'] && !empty($session['last_heartbeat'])) {
                $heartbeat_age = time() - strtotime($session['last_heartbeat']);
                if ($heartbeat_age > 180) {
                    $session['gap_detected'] = true;
                    $session['gap_seconds'] = $heartbeat_age;
                }
            }

            // Рассчитываем время текущей сессии
            if ($session['is_paused']) {
                $session['current_elapsed'] = strtotime($session['last_paused_at']) - strtotime($session['start_time']) - $session['pause_duration'];
            } else {
                $session['current_elapsed'] = time() - strtotime($session['start_time']) - $session['pause_duration'];
            }
            
            // Рассчитываем накопленное время по предыдущим (завершенным) сессиям для этой задачи
            $this->db->select('SUM(UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(start_time) - pause_duration) as total_sec', false);
            $this->db->where('task_id', $session['task_id']);
            $this->db->where('end_time IS NOT NULL', null, false);
            $sum_query = $this->db->get('time_sessions');
            $sum_result = $sum_query->row_array();
            
            $session['total_accumulated'] = (int)($sum_result['total_sec'] ?? 0);
        }
        
        return $session;
    }

    /**
     * Остановить активный таймер пользователя.
     * Возвращает true (успешно), 'spam' (сессия меньше минуты и была удалена), или false (ошибка).
     */
    public function stop_timer($user_id, $note = '') {
        // Сначала получаем активную сессию, чтобы проверить её длительность (без проверки лимита)
        $active_session = $this->get_active_session($user_id, true);
        if (!$active_session) {
            return false;
        }

        if ($active_session['is_paused']) {
            $end_time = $active_session['last_paused_at'];
            $duration = strtotime($end_time) - strtotime($active_session['start_time']) - $active_session['pause_duration'];
        } else {
            $end_time = date('Y-m-d H:i:s');
            $duration = strtotime($end_time) - strtotime($active_session['start_time']) - $active_session['pause_duration'];
        }

        // Анти-спам: если меньше 60 секунд, удаляем сессию физически
        if ($duration < 60) {
            $this->db->where('id', $active_session['id']);
            $this->db->delete('time_sessions');
            return 'spam';
        }

        // Иначе обычное сохранение
        $this->db->set('end_time', $end_time);
        $this->db->set('is_paused', 0);
        $this->db->set('last_paused_at', NULL);
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

        // Шаг 2: Проверяем наличие активной сессии
        $active = $this->get_active_session($user_id);
        if ($active) {
            if ($active['task_id'] == $task_id && $active['is_paused']) {
                // Возвращаемся с паузы
                $this->resume_timer($user_id, $active['id']);
                return $active['id'];
            }
            $this->stop_timer($user_id);
        }

        // Шаг 3: Создаем новую запись о сессии (старт - сейчас, стоп - NULL)
        $data = [
            'user_id' => $user_id,
            'task_id' => $task_id,
            'start_time' => date('Y-m-d H:i:s'),
            'end_time' => NULL,
            'is_paused' => 0,
            'pause_duration' => 0,
            'last_heartbeat' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('time_sessions', $data);
        return $this->db->insert_id();
    }
    
    public function pause_timer($user_id) {
        $active = $this->get_active_session($user_id);
        if ($active && !$active['is_paused']) {
            $this->db->set('is_paused', 1);
            $this->db->set('last_paused_at', date('Y-m-d H:i:s'));
            $this->db->where('id', $active['id']);
            $this->db->update('time_sessions');
            return true;
        }
        return false;
    }
    
    public function resume_timer($user_id, $session_id) {
        $this->db->where('id', $session_id);
        $this->db->where('user_id', $user_id);
        $session = $this->db->get('time_sessions')->row_array();
        
        if ($session && $session['is_paused']) {
            $pause_seconds = time() - strtotime($session['last_paused_at']);
            
            $this->db->set('is_paused', 0);
            $this->db->set('last_paused_at', NULL);
            $this->db->set('pause_duration', 'pause_duration + ' . $pause_seconds, FALSE);
            $this->db->set('last_heartbeat', date('Y-m-d H:i:s'));
            $this->db->where('id', $session_id);
            $this->db->update('time_sessions');
            return true;
        }
        return false;
    }

    /**
     * Пакетная синхронизация офлайн-действий пользователя из IndexedDB
     */
    public function sync_offline_actions($user_id, $actions) {
        // Сортируем действия по времени на всякий случай
        usort($actions, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        foreach ($actions as $action) {
            $type = $action['type'];
            $timestamp = $action['timestamp'];

            if ($type === 'START') {
                $task_id = $action['task_id'];
                
                // Закрываем активную сессию, если она есть
                $this->db->where('user_id', $user_id);
                $this->db->where('end_time IS NULL');
                $this->db->set('end_time', $timestamp);
                $this->db->update('time_sessions');
                
                // Создаем новую
                $data = [
                    'user_id' => $user_id,
                    'task_id' => $task_id,
                    'start_time' => $timestamp,
                    'end_time' => NULL,
                    'is_paused' => 0,
                    'pause_duration' => 0,
                    'last_heartbeat' => $timestamp
                ];
                $this->db->insert('time_sessions', $data);

            } elseif ($type === 'STOP') {
                $this->db->where('user_id', $user_id);
                $this->db->where('end_time IS NULL');
                $open_session = $this->db->get('time_sessions')->row_array();
                
                if ($open_session) {
                    $pause_dur = isset($open_session['pause_duration']) ? $open_session['pause_duration'] : 0;
                    if ($open_session['is_paused'] && !empty($open_session['last_paused_at'])) {
                        $timestamp = $open_session['last_paused_at'];
                    }
                    $duration = strtotime($timestamp) - strtotime($open_session['start_time']) - $pause_dur;
                    
                    if ($duration < 60) {
                        $this->db->where('id', $open_session['id']);
                        $this->db->delete('time_sessions');
                    } else {
                        $this->db->where('id', $open_session['id']);
                        $this->db->set('end_time', $timestamp);
                        $this->db->set('is_paused', 0);
                        $this->db->set('last_paused_at', NULL);
                        if (!empty($action['note'])) {
                            $this->db->set('note', $action['note']);
                        }
                        $this->db->update('time_sessions');
                    }
                }
            } elseif ($type === 'PAUSE') {
                $this->db->where('user_id', $user_id);
                $this->db->where('end_time IS NULL');
                $this->db->where('is_paused', 0);
                $open_session = $this->db->get('time_sessions')->row_array();
                
                if ($open_session) {
                    $this->db->where('id', $open_session['id']);
                    $this->db->set('is_paused', 1);
                    $this->db->set('last_paused_at', $timestamp);
                    $this->db->update('time_sessions');
                }
            } elseif ($type === 'RESUME') {
                $this->db->where('user_id', $user_id);
                $this->db->where('end_time IS NULL');
                $this->db->where('is_paused', 1);
                $open_session = $this->db->get('time_sessions')->row_array();
                
                if ($open_session) {
                    $pause_seconds = strtotime($timestamp) - strtotime($open_session['last_paused_at']);
                    if ($pause_seconds < 0) $pause_seconds = 0;
                    
                    $this->db->where('id', $open_session['id']);
                    $this->db->set('is_paused', 0);
                    $this->db->set('last_paused_at', NULL);
                    $this->db->set('pause_duration', 'pause_duration + ' . (int)$pause_seconds, FALSE);
                    $this->db->set('last_heartbeat', $timestamp);
                    $this->db->update('time_sessions');
                }
            }
        }
    }

    /**
     * Обновление времени последнего пульса (heartbeat)
     */
    public function update_heartbeat($session_id) {
        $this->db->set('last_heartbeat', date('Y-m-d H:i:s'));
        $this->db->where('id', $session_id);
        $this->db->update('time_sessions');
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
        $this->db->select('SUM(TIMESTAMPDIFF(SECOND, start_time, end_time) - pause_duration) as time_sum');
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

        // Восстанавливаем всех родителей вверх по иерархии, чтобы задача не висела в воздухе
        $parent_ids = $this->get_all_parent_ids($task_id, $user_id);
        if (!empty($parent_ids)) {
            $this->db->set('status', 'active');
            $this->db->where_in('id', $parent_ids);
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
     * Получить цвет задачи (если у самой нет, ищет у родителей вверх по дереву)
     */
    public function get_task_color_recursive($task_id, $user_id) {
        $current_id = $task_id;
        while ($current_id) {
            $this->db->select('parent_id, color');
            $this->db->where('id', $current_id);
            $this->db->where('user_id', $user_id);
            $task = $this->db->get('tasks')->row_array();
            
            if (empty($task)) {
                return null;
            }
            
            if (!empty($task['color'])) {
                return $task['color'];
            }
            
            if (!empty($task['parent_id'])) {
                $current_id = $task['parent_id'];
            } else {
                break;
            }
        }
        return null;
    }

    /**
     * Получить все ID родительских задач (всю иерархию вверх до корня)
     */
    public function get_all_parent_ids($task_id, $user_id) {
        $parent_ids = [];
        $current_id = $task_id;
        while ($current_id) {
            $this->db->select('parent_id');
            $this->db->where('id', $current_id);
            $this->db->where('user_id', $user_id);
            $task = $this->db->get('tasks')->row_array();
            
            if (!empty($task['parent_id'])) {
                $parent_ids[] = $task['parent_id'];
                $current_id = $task['parent_id'];
            } else {
                break;
            }
        }
        return $parent_ids;
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
     * Получить каскадную историю (список сессий времени) для массива идентификаторов задач
     * с поддержкой постраничной выборки (пагинации)
     *
     * @param array $task_ids_array Массив идентификаторов задач (включая подзадачи)
     * @param int $user_id Идентификатор текущего пользователя
     * @param int|null $limit Количество выбираемых записей (лимит)
     * @param int $offset Смещение относительно начала списка (оффсет)
     * @return array Массив сессий времени
     */
    public function get_cascading_history($task_ids_array, $user_id, $limit = null, $offset = 0) {
        // Проверяем, передан ли непустой массив идентификаторов задач
        if (empty($task_ids_array)) {
            // Если массив пуст, сразу возвращаем пустой результат
            return [];
        }

        // Выбираем все поля из таблицы time_sessions, а также название задачи, цвет и вычисляем разницу времени
        $this->db->select('
            time_sessions.*, 
            tasks.title as task_title, 
            tasks.color,
            TIMESTAMPDIFF(SECOND, time_sessions.start_time, time_sessions.end_time) as duration_seconds
        ');
        
        // Задаем базовую таблицу запроса - сессии времени
        $this->db->from('time_sessions');
        
        // Связываем сессии с таблицей задач по ID задачи
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        
        // Устанавливаем фильтрацию по ID пользователя
        $this->db->where('time_sessions.user_id', $user_id);
        
        // Фильтруем сессии по списку переданных ID задач
        $this->db->where_in('time_sessions.task_id', $task_ids_array);
        
        // Выбираем только завершенные сессии (где время окончания не пустое)
        $this->db->where('time_sessions.end_time IS NOT NULL', null, false);
        
        // Сортируем сессии в обратном хронологическом порядке (сначала новые)
        $this->db->order_by('time_sessions.end_time', 'DESC');
        
        // Если передан лимит записей, применяем его к запросу Active Record вместе со смещением
        if ($limit !== null) {
            // Применяем LIMIT и OFFSET к SQL-запросу
            $this->db->limit($limit, $offset);
        }
        
        // Выполняем SQL-запрос и возвращаем результат в виде ассоциативного массива
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
     * Обновление существующей сессии времени.
     * Обязательно проверяется принадлежность сессии пользователю ($user_id) в целях безопасности.
     * 
     * @param int $session_id ID изменяемой сессии
     * @param int $user_id ID владельца сессии
     * @param array $data Массив обновляемых полей (start_time, end_time, task_id, note)
     * @return bool Результат выполнения операции
     */
    public function update_session($session_id, $user_id, $data) {
        $this->db->where('id', $session_id);
        $this->db->where('user_id', $user_id);
        return $this->db->update('time_sessions', $data);
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
    public function update_task_details($task_id, $user_id, $new_title, $customer_id, $is_fixed_price, $price, $spec_id = NULL, $description = NULL, $color = NULL) {
        // Получаем текущего заказчика у задачи для проверки изменений
        $current_task = $this->db->select('customer_id')->where('id', $task_id)->where('user_id', $user_id)->get('tasks')->row_array();
        $old_customer_id = $current_task ? $current_task['customer_id'] : null;

        $new_customer_db = empty($customer_id) ? null : $customer_id;
        $old_customer_db = empty($old_customer_id) ? null : $old_customer_id;

        $customer_changed = ($new_customer_db !== $old_customer_db);

        $this->db->set('title', $new_title);
        $this->db->set('customer_id', $new_customer_db);
        $this->db->set('is_fixed_price', $is_fixed_price ? 1 : 0);
        $this->db->set('price', (float)$price);
        $this->db->set('spec_id', empty($spec_id) ? NULL : $spec_id);
        $this->db->set('description', empty($description) ? NULL : $description);
        if ($color !== NULL) {
            $this->db->set('color', empty($color) ? NULL : $color);
        }
        
        $this->db->where('id', $task_id);
        $this->db->where('user_id', $user_id);
        $success = $this->db->update('tasks');

        if ($success) {
            // Если заказчик был изменен, каскадно обновляем его у всех дочерних задач
            if ($customer_changed) {
                $this->update_customer_cascade($task_id, $new_customer_db, $user_id);
            }
            return true;
        }
        return false;
    }

    /**
     * Рекурсивно обновляет заказчика для всех подзадач указанной задачи.
     */
    public function update_customer_cascade($task_id, $customer_id, $user_id) {
        $this->db->select('id');
        $this->db->where('parent_id', $task_id);
        $this->db->where('user_id', $user_id);
        $children = $this->db->get('tasks')->result_array();
        
        foreach ($children as $child) {
            $this->db->set('customer_id', empty($customer_id) ? NULL : $customer_id);
            $this->db->where('id', $child['id']);
            $this->db->where('user_id', $user_id);
            $this->db->update('tasks');
            
            // Рекурсивный вызов для следующего уровня подзадач
            $this->update_customer_cascade($child['id'], $customer_id, $user_id);
        }
    }

    /**
     * Мягкое удаление задачи (каскадное).
     * Перемещает задачу и все её подзадачи в корзину (устанавливает deleted_at = NOW()).
     */
    public function delete_task_cascade($task_id, $user_id) {
        // Получаем массив ID самой задачи и всех её подзадач
        $ids_to_delete = $this->get_task_and_children_ids($task_id);
        
        if (empty($ids_to_delete)) return false;

        // Помечаем все найденные задачи как удаленные
        $this->db->where_in('id', $ids_to_delete);
        $this->db->where('user_id', $user_id);
        $this->db->set('deleted_at', date('Y-m-d H:i:s'));
        $this->db->update('tasks');
        
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
        $this->db->select('DATE(time_sessions.start_time) as report_date, tasks.id, tasks.title, tasks.parent_id, parent_tasks.title as parent_title, tasks.color, tasks.is_fixed_price, tasks.price, customers.name as customer_name, SUM(TIMESTAMPDIFF(SECOND, time_sessions.start_time, time_sessions.end_time)) as total_seconds');
        $this->db->from('time_sessions');
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        $this->db->join('tasks as parent_tasks', 'parent_tasks.id = tasks.parent_id', 'left');
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

    /**
     * Получить все удаленные задачи (в корзине) для пользователя.
     * Возвращает плоский список, контроллер построит дерево.
     */
    public function get_trashed_tasks($user_id) {
        $this->db->select('tasks.*, customers.name as customer_name');
        $this->db->from('tasks');
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        $this->db->where('tasks.user_id', $user_id);
        // Только удаленные
        $this->db->where('tasks.deleted_at IS NOT NULL', null, false);
        $this->db->order_by('tasks.deleted_at', 'DESC'); // Сортируем по дате удаления
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * Восстановить задачу из корзины (каскадно).
     * Снимает отметку deleted_at у самой задачи и всех её подзадач, а также восстанавливает всех родителей по цепочке.
     */
    public function restore_from_trash($task_id, $user_id) {
        // Получаем массив ID (самой задачи и её детей)
        $ids_to_restore = $this->get_task_and_children_ids($task_id);
        
        if (empty($ids_to_restore)) return false;

        // Получаем всех родителей, чтобы восстановить цепочку и избежать подвешивания в воздухе
        $parent_ids = $this->get_all_parent_ids($task_id, $user_id);
        if (!empty($parent_ids)) {
            $ids_to_restore = array_unique(array_merge($ids_to_restore, $parent_ids));
        }

        $this->db->where_in('id', $ids_to_restore);
        $this->db->where('user_id', $user_id);
        $this->db->set('deleted_at', NULL); // Сбрасываем флаг
        $this->db->update('tasks');
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Окончательное удаление (каскадно).
     * Безвозвратно удаляет задачу и все подзадачи из БД.
     */
    public function hard_delete_task($task_id, $user_id) {
        // Получаем массив ID
        $ids_to_delete = $this->get_task_and_children_ids($task_id);
        
        if (empty($ids_to_delete)) return false;

        $this->db->where_in('id', $ids_to_delete);
        $this->db->where('user_id', $user_id);
        $this->db->delete('tasks');
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Получить корневые задачи пользователя с пагинацией и все их подзадачи.
     */
    public function get_user_tasks_paginated($user_id, $limit, $offset) {
        // Выбираем только корневые задачи (parent_id IS NULL)
        $this->db->select('id');
        $this->db->from('tasks');
        $this->db->where('user_id', $user_id);
        $this->db->where('parent_id IS NULL', null, false);
        $this->db->where('deleted_at IS NULL', null, false);
        $this->db->order_by('created_at', 'ASC');
        $this->db->limit($limit, $offset);
        $root_query = $this->db->get();
        $root_ids = [];
        foreach ($root_query->result_array() as $row) {
            $root_ids[] = $row['id'];
        }

        if (empty($root_ids)) {
            return [];
        }

        // Сначала получим детей 2 уровня:
        $this->db->select('id');
        $this->db->from('tasks');
        $this->db->where_in('parent_id', $root_ids);
        $this->db->where('user_id', $user_id);
        $this->db->where('deleted_at IS NULL', null, false);
        $level2_query = $this->db->get();
        $level2_ids = [];
        foreach ($level2_query->result_array() as $row) {
            $level2_ids[] = $row['id'];
        }

        $all_needed_ids = array_merge($root_ids, $level2_ids);

        // Получим детей 3 уровня:
        if (!empty($level2_ids)) {
            $this->db->select('id');
            $this->db->from('tasks');
            $this->db->where_in('parent_id', $level2_ids);
            $this->db->where('user_id', $user_id);
            $this->db->where('deleted_at IS NULL', null, false);
            $level3_query = $this->db->get();
            $level3_ids = [];
            foreach ($level3_query->result_array() as $row) {
                $level3_ids[] = $row['id'];
            }
            $all_needed_ids = array_merge($all_needed_ids, $level3_ids);
        }

        // Теперь выбираем все эти задачи
        $this->db->select('tasks.*, customers.name as customer_name, customer_specs.title as spec_title');
        $this->db->from('tasks');
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        $this->db->join('customer_specs', 'customer_specs.id = tasks.spec_id', 'left');
        $this->db->where_in('tasks.id', $all_needed_ids);
        $this->db->where('tasks.user_id', $user_id);
        $this->db->where('tasks.deleted_at IS NULL', null, false);
        $this->db->order_by('tasks.created_at', 'ASC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Получить общее число завершенных сессий пользователя для пагинации журнала.
     */
    public function get_global_history_count($user_id) {
        $this->db->from('time_sessions');
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        $this->db->where('time_sessions.user_id', $user_id);
        $this->db->where('time_sessions.end_time IS NOT NULL', null, false);
        return $this->db->count_all_results();
    }

    /**
     * Получить сессии за текущую страницу журнала с пагинацией.
     */
    public function get_global_history_paginated($user_id, $limit, $offset) {
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
        
        $this->db->order_by('time_sessions.end_time', 'DESC');
        $this->db->limit($limit, $offset);
        
        return $this->db->get()->result_array();
    }
}
