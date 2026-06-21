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

        // Получаем лимит пагинации из настроек
        $this->load->model('Settings_model');
        $per_page = (int)$this->Settings_model->get_setting('per_page', 25);

        // Получаем первую порцию задач пользователя с пагинацией
        $raw_tasks = $this->Task_model->get_user_tasks_paginated($user_id, $per_page, 0);
        
        // Преобразуем плоский список в иерархическое дерево
        $tasks_tree = $this->_build_tree($raw_tasks);

        // Полный список всех задач пользователя для выпадающих списков ручной корректировки
        $all_flat_tasks = $this->Task_model->get_user_tasks($user_id);

        // Получаем активный таймер (сессию без end_time), если он есть
        $active_session = $this->Task_model->get_active_session($user_id);
        
        if ($active_session) {
            // Вычисляем общее накопленное время для этой задачи (только завершенные сессии)
            $active_session['total_accumulated'] = $this->Task_model->get_task_time_recursive($active_session['task_id'], $user_id);
            // Вычисляем сколько секунд прошло с момента старта текущей сессии
            $active_session['current_elapsed'] = time() - strtotime($active_session['start_time']);
        }

        // Определяем права администратора для отображения дополнительных инструментов управления
        $is_admin = ($this->session->userdata('group_id') == 1 || $this->session->userdata('username') === 'root');

        // Подготавливаем данные для передачи во view
        $data = [
            'tasks_tree' => $tasks_tree,
            'active_session' => $active_session,
            'customers' => $this->Customer_model->get_all($user_id),
            'is_admin' => $is_admin,
            'flat_tasks' => $all_flat_tasks, // Полный плоский список задач для формы редактирования сессий
            'per_page' => $per_page
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
            
            // Если выбран "Добавить нового клиента"
            if ($customer_id === 'new') {
                $new_customer_name = trim($this->input->post('new_customer_name') ?? '');
                $new_customer_notes = trim($this->input->post('new_customer_notes') ?? '');
                if (!empty($new_customer_name)) {
                    $this->load->model('Customer_model');
                    $customer_id = $this->Customer_model->add($user_id, $new_customer_name, $new_customer_notes);
                } else {
                    $customer_id = null; // Если имя не ввели
                }
            }
            
            $is_fixed_price = $this->input->post('is_fixed_price') ? 1 : 0;
            $price = $this->input->post('price');
            $spec_id = $this->input->post('spec_id') ?: null;
            $description = $this->input->post('description') ?: null;
            
            // Сохраняем задачу в базу
            if ($this->Task_model->add_task($user_id, $parent_id, $title, $customer_id, $is_fixed_price, $price, $spec_id, $description)) {
                $this->session->set_flashdata('success', 'Задача успешно добавлена!');
            } else {
                $db_error = $this->db->error();
                $this->session->set_flashdata('error', 'Ошибка БД: ' . ($db_error['message'] ?? 'Неизвестная ошибка'));
            }
        } else {
            $this->session->set_flashdata('error', 'Ошибка валидации: ' . validation_errors('', ' '));
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
            $active = $this->Task_model->get_active_session($user_id);
            $is_resume = ($active && $active['task_id'] == $task_id && $active['is_paused']);
            
            $session_id = $this->Task_model->start_timer($user_id, $task_id);
            if ($session_id) {
                if ($is_resume) {
                    $this->session->set_flashdata('success', 'Таймер возобновлен');
                } else {
                    $this->session->set_flashdata('success', 'Таймер запущен');
                }
                
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
            $this->session->set_flashdata('error', lang('ajax_error_spam'));
            echo json_encode(['status' => 'spam']);
        } elseif ($result) {
            $this->session->set_flashdata('success', 'Задача сохранена');
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => lang('ajax_error_stop')]);
        }
    }

    /**
     * AJAX-обработчик паузы таймера
     */
    public function pause_timer_ajax() {
        $user_id = $this->session->userdata('user_id');
        
        if ($this->Task_model->pause_timer($user_id)) {
            $this->session->set_flashdata('warning', 'Таймер поставлен на паузу');
            $active_session = $this->Task_model->get_active_session($user_id);
            echo json_encode(['status' => 'success', 'data' => $active_session]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Ошибка при паузе таймера']);
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
                $this->session->set_flashdata('success', 'Задача успешно завершена');
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
                $this->session->set_flashdata('success', 'Задача успешно восстановлена');
                echo json_encode(['status' => 'success']);
                return;
            }
        }

        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_restore')]);
    }

    /**
     * AJAX-обработчик для получения всех сессий конкретной задачи
     * Использует хелпер session_formatting для очистки контроллера от логики преобразования.
     *
     * @return void Выводит JSON-ответ
     */
    public function get_sessions_ajax() {
        // Получаем идентификатор текущего пользователя из сессии
        $user_id = $this->session->userdata('user_id');
        
        // Получаем ID задачи из POST-параметров запроса
        $task_id = $this->input->post('task_id');

        // Проверяем, передан ли ID задачи
        if (!empty($task_id)) {
            // Вызываем модель для получения списка сессий этой конкретной задачи
            $sessions = $this->Task_model->get_task_sessions($task_id, $user_id);
            
            // Подключаем хелпер форматирования сессий по правилам манифеста
            $this->load->helper('session_formatting');
            
            // Загружаем формат вывода длительности из языкового файла
            $duration_format = lang('time_format_hours_mins');
            
            // Проходим по каждой сессии и форматируем её через функцию хелпера
            foreach ($sessions as &$s) {
                // Преобразуем данные в полный формат для ручного редактора одной задачи
                $s = format_session_full($s, $duration_format);
            }
            
            // Выводим успешный ответ в JSON-формате
            echo json_encode(['status' => 'success', 'data' => $sessions]);
            return;
        }
    }

    /**
     * AJAX-обработчик получения каскадной истории (для задачи и всех её подзадач)
     * С поддержкой постраничной загрузки (бесконечный скролл) и вынесением логики в хелпер.
     *
     * @return void Выводит JSON-ответ
     */
    public function get_cascading_history_ajax() {
        // Получаем идентификатор текущего авторизованного пользователя
        $user_id = $this->session->userdata('user_id');
        
        // Получаем ID задачи из POST-параметров
        $task_id = $this->input->post('task_id');
        
        // Получаем параметры пагинации: лимит записей и смещение
        $limit = $this->input->post('limit');
        $offset = $this->input->post('offset');
        
        // Задаем лимит по умолчанию 40, если параметр не передан или пуст
        $limit = !empty($limit) ? (int)$limit : 40;
        
        // Задаем смещение по умолчанию 0, если параметр отсутствует
        $offset = !empty($offset) ? (int)$offset : 0;

        // Проверяем наличие переданного идентификатора задачи
        if (!empty($task_id)) {
            // Рекурсивно собираем все ID (этой задачи и всех её подзадач любого уровня вложенности)
            $task_ids = $this->Task_model->get_task_and_children_ids($task_id);
            
            // Получаем историю сессий из базы данных с учетом пагинации (лимит и оффсет)
            $sessions = $this->Task_model->get_cascading_history($task_ids, $user_id, $limit, $offset);
            
            // Загружаем хелпер форматирования сессий по правилам манифеста
            $this->load->helper('session_formatting');
            
            // Получаем языковую строку формата вывода времени
            $duration_format = lang('time_format_hours_mins');
            
            // Перебираем и форматируем каждую запись истории через функцию хелпера
            foreach ($sessions as &$s) {
                // Применяем краткий формат вывода дат и времени для каскадной истории
                $s = format_session_short($s, $duration_format);
            }
            
            // Возвращаем результат в формате JSON
            echo json_encode(['status' => 'success', 'data' => $sessions]);
            return;
        }
        
        // Возвращаем ошибку в формате JSON, если ID задачи не передан
        echo json_encode(['status' => 'error', 'message' => 'Не указан ID задачи']);
    }

    /**
     * AJAX-обработчик для добавления сессии вручную
     */
    public function add_manual_ajax() {
        // Проверяем права администратора
        $is_admin = ($this->session->userdata('group_id') == 1 || $this->session->userdata('username') === 'root');
        if (!$is_admin) {
            echo json_encode(['status' => 'error', 'message' => 'Доступ запрещен']);
            return;
        }

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
            $start_time = str_replace('T', ' ', $start_time);
            if (strlen($start_time) == 16) {
                $start_time .= ':00';
            }
            $end_time = str_replace('T', ' ', $end_time);
            if (strlen($end_time) == 16) {
                $end_time .= ':00';
            }

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
     * AJAX-обработчик для редактирования сессии (корректировка времени)
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
     * AJAX-обработчик для полного удаления сессии
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
            if ($this->Task_model->delete_session($session_id, $user_id)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        echo json_encode(['status' => 'error', 'message' => lang('ajax_error_delete')]);
    }

    /**
     * AJAX-обработчик для получения деталей конкретной задачи
     */
    public function get_task_ajax() {
        $user_id = $this->session->userdata('user_id');
        $task_id = $this->input->post('task_id');

        if (!empty($task_id)) {
            $task = $this->Task_model->get_task($task_id, $user_id);
            if ($task) {
                echo json_encode(['status' => 'success', 'data' => $task]);
                return;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Задача не найдена']);
    }

    /**
     * AJAX-обработчик редактирования свойств задачи
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
            $spec_id = $this->input->post('spec_id') ?: null;
            $description = $this->input->post('description'); // Описание (Quill HTML)
            $color = $this->input->post('color'); // Цвет HEX

            // Валидация формата цвета
            if (!empty($color) && !preg_match('/^#[a-f0-9]{6}$/i', $color)) {
                $color = null;
            }

            if ($this->Task_model->update_task_details($task_id, $user_id, $title, $customer_id, $is_fixed_price, $price, $spec_id, $description, $color)) {
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
    private function _build_tree(array $elements, $parentId = null, $all_ids = null) {
        $branch = array();
        $user_id = $this->session->userdata('user_id');

        // При первом вызове собираем массив всех ID, которые есть в $elements
        if ($all_ids === null) {
            $all_ids = [];
            foreach ($elements as $el) {
                $all_ids[] = $el['id'];
            }
        }

        foreach ($elements as $element) {
            // Элемент считается "на этом уровне", если его parent_id совпадает с запрашиваемым
            // ИЛИ если запрашивается корень ($parentId == null), а реальный родитель этой задачи отсутствует в списке $elements (сирота)
            $is_match = ($element['parent_id'] == $parentId);
            
            if (!$is_match && $parentId === null && !empty($element['parent_id'])) {
                if (!in_array($element['parent_id'], $all_ids)) {
                    $is_match = true;
                }
            }

            if ($is_match) {
                // Ищем детей (рекурсия)
                $children = $this->_build_tree($elements, $element['id'], $all_ids);
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

    /**
     * Страница корзины (Trash)
     * Рендерит представление с удаленными задачами.
     */
    public function trash() {
        $user_id = $this->session->userdata('user_id');
        
        // Получаем плоский список всех удаленных задач
        $flat_tasks = $this->Task_model->get_trashed_tasks($user_id);
        
        // Преобразуем плоский список в иерархическое дерево (как на дашборде)
        $data['tasks_tree'] = $this->_build_tree($flat_tasks);
        
        // Задаем заголовок страницы
        $data['title'] = 'Корзина';
        
        // Рендерим страницу через базовый метод, который сам решит AJAX это или нет и подгрузит шапку с футером
        $this->render_page('trash', $data);
    }

    /**
     * AJAX-обработчик восстановления задачи из корзины
     */
    public function restore_from_trash_ajax() {
        $user_id = $this->session->userdata('user_id');
        $task_id = $this->input->post('task_id');

        // Проверяем, передан ли ID задачи
        if (!empty($task_id)) {
            // Вызываем модель для каскадного восстановления
            if ($this->Task_model->restore_from_trash($task_id, $user_id)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        // В случае ошибки возвращаем JSON с описанием
        echo json_encode(['status' => 'error', 'message' => 'Не удалось восстановить задачу.']);
    }

    /**
     * AJAX-обработчик окончательного удаления задачи из базы
     */
    public function hard_delete_ajax() {
        $user_id = $this->session->userdata('user_id');
        $task_id = $this->input->post('task_id');

        // Проверяем, передан ли ID задачи
        if (!empty($task_id)) {
            // Вызываем модель для физического удаления записи и её детей
            if ($this->Task_model->hard_delete_task($task_id, $user_id)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        // В случае ошибки возвращаем JSON
        echo json_encode(['status' => 'error', 'message' => 'Не удалось окончательно удалить задачу.']);
    }

    /**
     * AJAX-обработчик бесконечного скролла на дашборде
     */
    public function load_more_tasks_ajax() {
        $user_id = $this->session->userdata('user_id');
        $offset = (int)$this->input->post('offset');
        
        $this->load->model('Settings_model');
        $limit = (int)$this->Settings_model->get_setting('per_page', 25);
        
        $raw_tasks = $this->Task_model->get_user_tasks_paginated($user_id, $limit, $offset);
        if (empty($raw_tasks)) {
            echo json_encode(['status' => 'success', 'html' => '', 'has_more' => false]);
            return;
        }
        
        $tasks_tree = $this->_build_tree($raw_tasks);
        
        // Получаем активный таймер
        $active_session = $this->Task_model->get_active_session($user_id);
        
        // Рендерим HTML через буфер вывода
        ob_start();
        $this->load->view('templates/task_list_loop');
        render_task_tree($tasks_tree, 1, $active_session);
        $html = ob_get_clean();
        
        // Убираем скрытие завершенных проектов на первом уровне, если они отрендерились
        $html = str_replace('hidden task-children', 'block', $html);
        
        // Проверяем, есть ли еще записи
        $next_raw = $this->Task_model->get_user_tasks_paginated($user_id, 1, $offset + $limit);
        $has_more = !empty($next_raw);
        
        echo json_encode([
            'status' => 'success',
            'html' => $html,
            'has_more' => $has_more
        ]);
    }
}
