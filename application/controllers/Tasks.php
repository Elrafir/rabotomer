<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Главный контроллер для работы с задачами и таймером (Дашборд).
 * Наследуется от MY_Controller для автоматической проверки авторизации.
 */
class Tasks extends MY_Controller {

    public function __construct() {
        parent::__construct();
        // Загружаем модель для работы с БД
        $this->load->model('Task_model');
        $this->load->model('Customer_model');
    }

    /**
     * Главная страница (Дашборд)
     */
    public function index() {
        $user_id = $this->session->userdata('user_id');

        // Получаем плоский массив всех задач пользователя
        $raw_tasks = $this->Task_model->get_user_tasks($user_id);
        
        // Преобразуем плоский список в иерархическое дерево
        $tasks_tree = $this->_build_tree($raw_tasks);

        // Получаем активный таймер (сессию без end_time), если он есть
        $active_session = $this->Task_model->get_active_session($user_id);
        
        if ($active_session) {
            // Вычисляем общее накопленное время для этой задачи (только завершенные сессии)
            $active_session['total_accumulated'] = $this->Task_model->get_task_time_recursive($active_session['task_id'], $user_id);
            // Вычисляем сколько секунд прошло с момента старта текущей сессии
            $active_session['current_elapsed'] = time() - strtotime($active_session['start_time']);
        }

        // Подготавливаем данные для передачи во view
        $data = [
            'tasks_tree' => $tasks_tree,
            'active_session' => $active_session,
            'customers' => $this->Customer_model->get_all($user_id)
        ];

        // Рендерим страницу (header + body + контент + footer)
        $this->render_page('dashboard', $data);
    }

    /**
     * Добавление новой задачи или подзадачи (Обработчик формы)
     */
    public function add() {
        $user_id = $this->session->userdata('user_id');
        
        // Включаем валидацию формы (title обязателен, пробелы отсекаются)
        $this->form_validation->set_rules('title', 'Название задачи', 'required|trim');

        if ($this->form_validation->run() !== FALSE) {
            $title = $this->input->post('title');
            $parent_id = $this->input->post('parent_id');
            $customer_id = $this->input->post('customer_id');
            $is_fixed_price = $this->input->post('is_fixed_price') ? 1 : 0;
            $price = $this->input->post('price');
            
            // Сохраняем задачу в базу
            $this->Task_model->add_task($user_id, $parent_id, $title, $customer_id, $is_fixed_price, $price);
        }

        // После создания задачи возвращаемся на главную страницу
        redirect('/');
    }

    /**
     * AJAX-обработчик запуска таймера
     */
    public function start_timer_ajax() {
        $user_id = $this->session->userdata('user_id');
        $task_id = $this->input->post('task_id');

        if (!empty($task_id)) {
            $session_id = $this->Task_model->start_timer($user_id, $task_id);
            if ($session_id) {
                // Возвращаем новую активную сессию для обновления UI
                $active_session = $this->Task_model->get_active_session($user_id);
                echo json_encode(['status' => 'success', 'data' => $active_session]);
                return;
            }
        }

        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_start')]);
    }

    /**
     * AJAX-обработчик остановки таймера
     */
    public function stop_timer_ajax() {
        $user_id = $this->session->userdata('user_id');
        $note = $this->input->post('note');

        $result = $this->Task_model->stop_timer($user_id, $note);
        
        if ($result === 'spam') {
            echo json_encode(['status' => 'spam', 'message' => lang('ajax_error_spam')]);
        } elseif ($result) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => lang('ajax_error_stop')]);
        }
    }

    /**
     * AJAX-обработчик завершения задачи
     */
    public function complete_ajax() {
        $user_id = $this->session->userdata('user_id');
        $task_id = $this->input->post('task_id');

        if (!empty($task_id)) {
            if ($this->Task_model->complete_task_recursive($task_id, $user_id)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }

        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_complete')]);
    }

    /**
     * AJAX-обработчик восстановления задачи
     */
    public function restore_task_ajax() {
        $user_id = $this->session->userdata('user_id');
        $task_id = $this->input->post('task_id');

        if (!empty($task_id)) {
            if ($this->Task_model->restore_task_recursive($task_id, $user_id)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }

        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_restore')]);
    }

    /**
     * AJAX-обработчик для получения всех сессий конкретной задачи
     */
    public function get_sessions_ajax() {
        $user_id = $this->session->userdata('user_id');
        $task_id = $this->input->post('task_id');

        if (!empty($task_id)) {
            $sessions = $this->Task_model->get_task_sessions($task_id, $user_id);
            // Форматируем данные для красивого вывода
            foreach ($sessions as &$s) {
                $s['start_formatted'] = date('d.m.Y H:i:s', strtotime($s['start_time']));
                $s['end_formatted'] = date('d.m.Y H:i:s', strtotime($s['end_time']));
                $diff = strtotime($s['end_time']) - strtotime($s['start_time']);
                $s['duration'] = sprintf(lang('time_format_hours_mins'), floor($diff / 3600), floor(($diff % 3600) / 60));
                // Защита от XSS для поля результата
                $s['note_safe'] = !empty($s['note']) ? htmlspecialchars($s['note']) : '';
            }
            echo json_encode(['status' => 'success', 'data' => $sessions]);
            return;
        }
    }

    /**
     * AJAX-обработчик получения каскадной истории (для задачи и всех её подзадач)
     */
    public function get_cascading_history_ajax() {
        $user_id = $this->session->userdata('user_id');
        $task_id = $this->input->post('task_id');

        if (!empty($task_id)) {
            // Рекурсивно собираем все ID (этой задачи и всех её потомков)
            $task_ids = $this->Task_model->get_task_and_children_ids($task_id);
            
            // Получаем историю
            $sessions = $this->Task_model->get_cascading_history($task_ids, $user_id);
            
            // Форматируем для UI
            foreach ($sessions as &$s) {
                $s['start_formatted'] = date('d.m.Y H:i', strtotime($s['start_time']));
                $s['end_formatted'] = date('H:i', strtotime($s['end_time']));
                $diff = $s['duration_seconds'];
                $s['duration'] = sprintf(lang('time_format_hours_mins'), floor($diff / 3600), floor(($diff % 3600) / 60));
                $s['note_safe'] = !empty($s['note']) ? htmlspecialchars($s['note']) : '';
            }
            
            echo json_encode(['status' => 'success', 'data' => $sessions]);
            return;
        }
        
        echo json_encode(['status' => 'error']);
    }

    /**
     * AJAX-обработчик для добавления сессии вручную
     */
    public function add_manual_ajax() {
        $user_id = $this->session->userdata('user_id');

        // Включаем валидацию
        $this->form_validation->set_rules('task_id', 'ID задачи', 'required|numeric');
        $this->form_validation->set_rules('start_time', 'Время начала', 'required');
        $this->form_validation->set_rules('end_time', 'Время конца', 'required');

        if ($this->form_validation->run() !== FALSE) {
            $task_id = $this->input->post('task_id');
            $start_time = $this->input->post('start_time');
            $end_time = $this->input->post('end_time');
            $note = $this->input->post('note');

            // Преобразуем формат, если он пришел с "T" из HTML5-инпута (datetime-local)
            // HTML5 отправляет: 2023-10-25T14:30
            // MySQL нужно: 2023-10-25 14:30:00
            $start_time = str_replace('T', ' ', $start_time) . ':00';
            $end_time = str_replace('T', ' ', $end_time) . ':00';

            // Простая валидация: конец должен быть больше начала
            if (strtotime($end_time) > strtotime($start_time)) {
                if ($this->Task_model->add_manual_session($user_id, $task_id, $start_time, $end_time, $note)) {
                    echo json_encode(['status' => 'success']);
                    return;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => lang('ajax_error_end_less_start')]);
                return;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
            return;
        }

        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_system_save')]);
    }

    /**
     * AJAX-обработчик для полного удаления сессии
     */
    public function delete_session_ajax() {
        $user_id = $this->session->userdata('user_id');
        $session_id = $this->input->post('session_id');

        if (!empty($session_id)) {
            if ($this->Task_model->delete_session($session_id, $user_id)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_delete')]);
    }

    /**
     * AJAX-обработчик редактирования названия задачи
     */
    public function edit_title_ajax() {
        $user_id = $this->session->userdata('user_id');

        $this->form_validation->set_rules('task_id', 'ID задачи', 'required|numeric');
        $this->form_validation->set_rules('title', 'Название задачи', 'required|trim');

        if ($this->form_validation->run() !== FALSE) {
            $task_id = $this->input->post('task_id');
            $title = $this->input->post('title');
            $customer_id = $this->input->post('customer_id');
            $is_fixed_price = $this->input->post('is_fixed_price') ? 1 : 0;
            $price = $this->input->post('price');

            if ($this->Task_model->update_task_details($task_id, $user_id, $title, $customer_id, $is_fixed_price, $price)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_edit_task')]);
    }

    /**
     * AJAX-обработчик полного удаления задачи
     */
    public function delete_task_ajax() {
        $user_id = $this->session->userdata('user_id');
        $task_id = $this->input->post('task_id');

        if (!empty($task_id)) {
            if ($this->Task_model->delete_task_cascade($task_id, $user_id)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_delete_task')]);
    }

    /**
     * AJAX-обработчик сохранения цвета задачи
     */
    public function set_color_ajax() {
        $task_id = $this->input->post('task_id');
        $color = $this->input->post('color');
        $user_id = $this->session->userdata('user_id');

        if (empty($color)) {
            $color = NULL; // Сбрасываем цвет
        } else {
            // Простейшая валидация HEX-цвета
            if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid color format']);
                return;
            }
        }

        if (!empty($task_id)) {
            if ($this->Task_model->set_task_color($task_id, $user_id, $color)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        
        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_set_color')]);
    }

    /**
     * Вспомогательный метод для построения дерева задач из плоского списка.
     * Здесь же мы рассчитываем время и сортируем (сначала активные, потом завершенные).
     */
    private function _build_tree(array $elements, $parentId = null) {
        $branch = array();
        $user_id = $this->session->userdata('user_id');

        // Сначала собираем все элементы текущего уровня
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                // Ищем детей (рекурсия)
                $children = $this->_build_tree($elements, $element['id']);
                $element['children'] = $children ? $children : [];

                // Считаем общее время задачи вместе со всеми её детьми
                $total_seconds = $this->Task_model->get_task_time_recursive($element['id'], $user_id);
                
                // Переводим секунды в часы и минуты
                $hours = floor($total_seconds / 3600);
                $minutes = floor(($total_seconds % 3600) / 60);
                
                // Формируем красивую строку, используя локализацию
                $element['formatted_time'] = sprintf(lang('time_format_hours_mins'), $hours, $minutes);

                // Если цвет не задан, используем дефолтный
                if (empty($element['color'])) {
                    $element['color'] = ''; 
                }

                $branch[] = $element;
            }
        }

        // Сортируем ветку: сначала задачи status == 'active', затем status == 'completed'
        usort($branch, function($a, $b) {
            if ($a['status'] === $b['status']) {
                // Если статусы равны, сортируем по ID или по времени создания (оставляем как было)
                return $a['id'] <=> $b['id'];
            }
            // Активные задачи всегда выше (возвращаем -1, если $a активна)
            return ($a['status'] === 'active') ? -1 : 1;
        });

        return $branch;
    }
}
