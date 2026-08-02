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
    /**
     * Отображение журнала активности.
     * Загружает только первую порцию сессий для бесконечной прокрутки.
     */
    public function index() {
        // Получаем ID авторизованного пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Проверяем, является ли пользователь администратором (группа 1 или логин root)
        $is_admin = ($this->session->userdata('group_id') == 1 || $this->session->userdata('username') === 'root');

        // Загружаем модель системных настроек для получения лимитов
        $this->load->model('Settings_model');
        // Получаем лимит записей на страницу из настроек пользователя, по умолчанию 25
        $per_page = (int)$this->Settings_model->get_setting('per_page', 25);

        // Получаем завершенные сессии текущего пользователя для первой страницы (offset = 0)
        $sessions = $this->Task_model->get_global_history_paginated($user_id, $per_page, 0);
        
        // Форматируем данные для вывода
        foreach ($sessions as &$s) {
            // Преобразуем дату и время начала сессии в красивый читаемый формат
            $s['start_formatted'] = date('d.m.Y H:i', strtotime($s['start_time']));
            // Преобразуем время окончания сессии для вывода на экран
            $s['end_formatted'] = date('H:i', strtotime($s['end_time']));
            
            // Вычисляем продолжительность сессии в секундах
            $diff = $s['duration_seconds'];
            // Преобразуем секунды в часы и минуты по заданному формату локализации
            $s['duration_formatted'] = sprintf(lang('time_format_hours_mins'), floor($diff / 3600), floor(($diff % 3600) / 60));
            
            // Защищаем текст заметки к задаче от XSS-уязвимостей
            $s['note_safe'] = !empty($s['note']) ? htmlspecialchars($s['note']) : '';
        }

        // Подготавливаем результирующий массив данных для рендеринга страницы
        $data = [
            // Передаем отформатированный массив сессий
            'sessions' => $sessions,
            // Подключаем левую панель со статистикой и ссылками
            'left_sidebar_view' => 'sidebars/statistics',
            // Активная вкладка левой панели
            'active_sub_page' => 'history',
            // Передаем флаг администратора для проверки прав в представлении
            'is_admin' => $is_admin,
            // Передаем размер страницы по умолчанию для JS скрипта
            'per_page' => $per_page,
            'custom_js' => [
                'assets/js/timeline.js'
            ]
        ];

        // Если администратор, подгружаем его задачи для выбора в формах CRUD
        if ($is_admin) {
            // Получаем список задач администратора
            $data['tasks'] = $this->Task_model->get_user_tasks($user_id);
        }

        // Вызываем рендеринг страницы с передачей отформатированных сессий и настроек меню
        $this->render_page('history', $data);
    }

    /**
     * AJAX-метод подгрузки дополнительных записей в журнал активности.
     * Возвращает HTML новых строк таблицы и статус наличия следующих страниц.
     */
    public function load_more_history_ajax() {
        // Получаем ID авторизованного пользователя из сессии
        $user_id = $this->session->userdata('user_id');
        // Считываем смещение (offset) из входящего POST-запроса
        $offset = (int)$this->input->post('offset');
        // Проверяем, является ли пользователь администратором (группа 1 или логин root)
        $is_admin = ($this->session->userdata('group_id') == 1 || $this->session->userdata('username') === 'root');

        // Загружаем модель системных настроек для получения лимитов
        $this->load->model('Settings_model');
        // Получаем лимит записей на страницу из настроек пользователя, по умолчанию 25
        $per_page = (int)$this->Settings_model->get_setting('per_page', 25);

        // Получаем завершенные сессии текущего пользователя для текущего смещения
        $sessions = $this->Task_model->get_global_history_paginated($user_id, $per_page, $offset);
        
        // Форматируем данные для вывода
        foreach ($sessions as &$s) {
            // Преобразуем дату и время начала сессии в красивый читаемый формат
            $s['start_formatted'] = date('d.m.Y H:i', strtotime($s['start_time']));
            // Преобразуем время окончания сессии для вывода на экран
            $s['end_formatted'] = date('H:i', strtotime($s['end_time']));
            
            // Вычисляем продолжительность сессии в секундах
            $diff = $s['duration_seconds'];
            // Преобразуем секунды в часы и минуты по заданному формату локализации
            $s['duration_formatted'] = sprintf(lang('time_format_hours_mins'), floor($diff / 3600), floor(($diff % 3600) / 60));
            
            // Защищаем текст заметки к задаче от XSS-уязвимостей
            $s['note_safe'] = !empty($s['note']) ? htmlspecialchars($s['note']) : '';
        }

        // Инициализируем переменную для накопления HTML строк таблицы
        $html = '';
        // Обходим полученные сессии циклом для генерации HTML-кода
        foreach ($sessions as $s) {
            // Задаем цвет кружка задачи (если цвета нет, ставим дефолтный серый)
            $color_bg = !empty($s['color']) ? "background-color: {$s['color']};" : "background-color: #e5e7eb;";
            // Рендерим HTML для заметки: если она есть, выводим блок, иначе прочерк
            $note_html = !empty($s['note_safe']) 
                ? '<div class="text-gray-600 italic bg-gray-50 border-l-4 border-gray-200 px-4 py-2 rounded-r-lg group-hover:bg-white transition-colors">' . $s['note_safe'] . '</div>' 
                : '<span class="text-gray-300">—</span>';
                
            // Инициализируем HTML действий администратора
            $actions_html = '';
            // Если пользователь администратор, рендерим кнопки редактирования и удаления
            if ($is_admin) {
                // Подготавливаем строковые параметры для JS функции редактирования сессии
                $edit_params = sprintf(
                    "%d, %d, '%s', '%s', '%s'",
                    $s['id'],
                    $s['task_id'],
                    date('Y-m-d\TH:i', strtotime($s['start_time'])),
                    date('Y-m-d\TH:i', strtotime($s['end_time'])),
                    addslashes($s['note_safe'])
                );
                // Формируем HTML колонки действий с кнопками
                $actions_html = '
                    <td class="px-6 py-5 text-right whitespace-nowrap">
                        <div class="flex justify-end gap-2">
                            <button onclick="openEditSessionModal(' . $edit_params . ')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-2 rounded-lg transition-colors" title="' . htmlspecialchars(lang('btn_edit'), ENT_QUOTES) . '">
                                ✏️
                            </button>
                            <button onclick="deleteSession(' . $s['id'] . ')" class="bg-red-50 hover:bg-red-100 text-red-600 p-2 rounded-lg transition-colors" title="' . htmlspecialchars(lang('btn_delete'), ENT_QUOTES) . '">
                                🗑️
                             </button>
                        </div>
                    </td>';
            }
            
            // Накапливаем HTML строки таблицы с подставленными значениями
            $html .= '
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-5">
                        <div class="flex flex-col">
                            <span class="text-gray-900 font-bold text-lg">' . $s['start_formatted'] . '</span>
                            <span class="text-gray-400 text-sm flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                ' . $s['end_formatted'] . '
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-4 h-4 rounded-full flex-shrink-0 shadow-sm" style="' . $color_bg . '"></div>
                            <span class="text-gray-800 font-semibold text-lg">' . htmlspecialchars($s['task_title'] ?? '') . '</span>
                        </div>
                    </td>
                    <td class="px-6 py-5">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold font-mono bg-blue-50 text-blue-600 border border-blue-100">
                            ' . $s['duration_formatted'] . '
                        </span>
                    </td>
                    <td class="px-6 py-5">
                        ' . $note_html . '
                    </td>
                    ' . $actions_html . '
                </tr>';
        }
        
        // Получаем общее количество сессий пользователя из БД
        $total_rows = $this->Task_model->get_global_history_count($user_id);
        // Вычисляем, есть ли еще страницы для подгрузки (флаг has_more)
        $has_more = ($offset + count($sessions)) < $total_rows;
        
        // Отдаем JSON ответ с HTML-строками и флагом продолжения
        echo json_encode([
            'status' => 'success',
            'html' => $html,
            'has_more' => $has_more
        ]);
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
