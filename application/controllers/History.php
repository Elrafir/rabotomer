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

        // Загружаем библиотеку пагинации и настройки
        $this->load->library('pagination');
        $this->load->model('Settings_model');
        $per_page = (int)$this->Settings_model->get_setting('per_page', 25);

        // Получаем текущую страницу (offset)
        $offset = $this->uri->segment(3) ? (int)$this->uri->segment(3) : 0;

        $total_rows = $this->Task_model->get_global_history_count($user_id);

        $config['base_url'] = site_url('history/index');
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $per_page;
        $config['uri_segment'] = 3;

        // Красивое оформление пагинации в стиле Tailwind CSS
        $config['full_tag_open'] = '<div class="flex items-center justify-center gap-2 mt-8">';
        $config['full_tag_close'] = '</div>';
        
        $config['first_link'] = '«';
        $config['first_tag_open'] = '<div class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">';
        $config['first_tag_close'] = '</div>';
        
        $config['last_link'] = '»';
        $config['last_tag_open'] = '<div class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">';
        $config['last_tag_close'] = '</div>';
        
        $config['next_link'] = '›';
        $config['next_tag_open'] = '<div class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">';
        $config['next_tag_close'] = '</div>';
        
        $config['prev_link'] = '‹';
        $config['prev_tag_open'] = '<div class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">';
        $config['prev_tag_close'] = '</div>';
        
        $config['cur_tag_open'] = '<span class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-bold shadow-sm">';
        $config['cur_tag_close'] = '</span>';
        
        $config['num_tag_open'] = '<div class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50">';
        $config['num_tag_close'] = '</div>';

        $config['attributes'] = array('class' => 'text-gray-600');

        $this->pagination->initialize($config);

        // Получаем завершенные сессии текущего пользователя для текущей страницы
        $sessions = $this->Task_model->get_global_history_paginated($user_id, $per_page, $offset);
        
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
        $data = [
            'sessions' => $sessions,
            'left_sidebar_view' => 'sidebars/statistics', // Подключение левой панели со статистикой и ссылками
            'is_admin' => $is_admin,
            'pagination_links' => $this->pagination->create_links()
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
