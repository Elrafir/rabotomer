<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Контроллер для вывода глобального журнала активности.
 */

class History extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Task_model');
    }

    /**
     * Отображение журнала активности.
     * Для администраторов загружает список задач текущего пользователя для CRUD форм.
     */
    public function index() {
        $user_id = $this->session->userdata('user_id');

        // Проверяем, является ли пользователь администратором (group_id = 1 или root)
        $is_admin = ($this->session->userdata('group_id') == 1 || $this->session->userdata('username') === 'root');

        // Получаем все завершенные сессии текущего пользователя
        $sessions = $this->Task_model->get_global_history($user_id);
        
        // Форматируем данные для вывода
        foreach ($sessions as &$s) {
            $s['start_formatted'] = date('d.m.Y H:i', strtotime($s['start_time']));
            $s['end_formatted'] = date('H:i', strtotime($s['end_time'])); // Только время для компактности, если тот же день
            
            $diff = $s['duration_seconds'];
            $s['duration_formatted'] = sprintf(lang('time_format_hours_mins'), floor($diff / 3600), floor(($diff % 3600) / 60));
            
            // Защита от XSS
            $s['note_safe'] = !empty($s['note']) ? htmlspecialchars($s['note']) : '';
        }

        // Подготавливаем результирующий массив данных для рендеринга страницы.
        // Добавляем параметр left_sidebar_view с файлом 'sidebars/statistics',
        // чтобы отобразить левое меню навигации по разделу статистики и калькуляции.
        $data = [
            'sessions' => $sessions,
            'left_sidebar_view' => 'sidebars/statistics', // Подключение левой панели со статистикой и ссылками
            'is_admin' => $is_admin
        ];

        // Если администратор, подгружаем его задачи для выбора в формах CRUD
        if ($is_admin) {
            $data['tasks'] = $this->Task_model->get_user_tasks($user_id);
        }

        // Вызываем рендеринг страницы с передачей отформатированных сессий и настроек меню
        $this->render_page('history', $data);
    }

    /**
     * AJAX-обработчик ручного добавления сессии для администраторов.
     * Доступен только пользователям из группы Администраторы.
     */
    public function add_session_ajax() {
        // Проверяем права администратора
        $is_admin = ($this->session->userdata('group_id') == 1 || $this->session->userdata('username') === 'root');
        if (!$is_admin) {
            echo json_encode(['status' => 'error', 'message' => 'Доступ запрещен']);
            return;
        }

        $user_id = $this->session->userdata('user_id');

        // Валидация входных данных
        $this->form_validation->set_rules('task_id', 'Задача', 'required|numeric');
        $this->form_validation->set_rules('start_time', 'Время начала', 'required');
        $this->form_validation->set_rules('end_time', 'Время окончания', 'required');

        if ($this->form_validation->run() !== FALSE) {
            $task_id = $this->input->post('task_id');
            $start_time = $this->input->post('start_time');
            $end_time = $this->input->post('end_time');
            $note = $this->input->post('note');

            // Форматируем время для MySQL
            $start_time = str_replace('T', ' ', $start_time);
            if (strlen($start_time) == 16) {
                $start_time .= ':00';
            }
            $end_time = str_replace('T', ' ', $end_time);
            if (strlen($end_time) == 16) {
                $end_time .= ':00';
            }

            // Проверка хронологии
            if (strtotime($end_time) <= strtotime($start_time)) {
                echo json_encode(['status' => 'error', 'message' => lang('ajax_error_end_less_start')]);
                return;
            }

            // Вставляем сессию в базу данных
            if ($this->Task_model->add_manual_session($user_id, $task_id, $start_time, $end_time, $note)) {
                echo json_encode(['status' => 'success', 'message' => 'Сессия успешно добавлена']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Не удалось добавить сессию']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
        }
    }

    /**
     * AJAX-обработчик редактирования сессии для администраторов.
     * Позволяет редактировать только собственные сессии.
     */
    public function edit_session_ajax() {
        // Проверяем права администратора
        $is_admin = ($this->session->userdata('group_id') == 1 || $this->session->userdata('username') === 'root');
        if (!$is_admin) {
            echo json_encode(['status' => 'error', 'message' => 'Доступ запрещен']);
            return;
        }

        $user_id = $this->session->userdata('user_id');

        // Валидация входных данных
        $this->form_validation->set_rules('session_id', 'ID сессии', 'required|numeric');
        $this->form_validation->set_rules('task_id', 'Задача', 'required|numeric');
        $this->form_validation->set_rules('start_time', 'Время начала', 'required');
        $this->form_validation->set_rules('end_time', 'Время окончания', 'required');

        if ($this->form_validation->run() !== FALSE) {
            $session_id = $this->input->post('session_id');
            $task_id = $this->input->post('task_id');
            $start_time = $this->input->post('start_time');
            $end_time = $this->input->post('end_time');
            $note = $this->input->post('note');

            // Форматируем время для MySQL
            $start_time = str_replace('T', ' ', $start_time);
            if (strlen($start_time) == 16) {
                $start_time .= ':00';
            }
            $end_time = str_replace('T', ' ', $end_time);
            if (strlen($end_time) == 16) {
                $end_time .= ':00';
            }

            // Проверка хронологии
            if (strtotime($end_time) <= strtotime($start_time)) {
                echo json_encode(['status' => 'error', 'message' => lang('ajax_error_end_less_start')]);
                return;
            }

            // Подготавливаем обновляемые данные
            $update_data = [
                'task_id' => $task_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'note' => $note
            ];

            // Обновляем запись в базе с проверкой принадлежности к текущему пользователю
            if ($this->Task_model->update_session($session_id, $user_id, $update_data)) {
                echo json_encode(['status' => 'success', 'message' => 'Сессия успешно сохранена']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Не удалось обновить сессию или права ограничены']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
        }
    }

    /**
     * AJAX-обработчик удаления сессии для администраторов.
     * Позволяет удалять только собственные сессии.
     */
    public function delete_session_ajax() {
        // Проверяем права администратора
        $is_admin = ($this->session->userdata('group_id') == 1 || $this->session->userdata('username') === 'root');
        if (!$is_admin) {
            echo json_encode(['status' => 'error', 'message' => 'Доступ запрещен']);
            return;
        }

        $user_id = $this->session->userdata('user_id');
        $session_id = $this->input->post('session_id');

        if (!empty($session_id)) {
            // Удаляем сессию с жесткой фильтрацией по user_id
            if ($this->Task_model->delete_session($session_id, $user_id)) {
                echo json_encode(['status' => 'success', 'message' => 'Сессия удалена']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Не удалось удалить сессию']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Не передан ID сессии']);
        }
    }
}
