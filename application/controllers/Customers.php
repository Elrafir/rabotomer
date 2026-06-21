<?php
// Запрещаем прямой доступ к файлу минуя фреймворк
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Контроллер управления заказчиками и связанными техническими заданиями (ТЗ).
 *
 * «Тонкий» контроллер: вся бизнес-логика вынесена в модели, библиотеки и хелперы.
 * - Customer_model      — CRUD заказчиков, ТЗ, файлов, привязка задач, проверка прав
 * - Task_tree_builder   — построение иерархического дерева задач заказчика
 * - file_manager_helper — определение upload_dir, сканирование внешних файлов
 * - spec_helper         — иконки файлов, форматирование типа оплаты
 */
class Customers extends MY_Controller {

    /**
     * Конструктор контроллера.
     * Загружает все необходимые зависимости для работы модуля «Заказчики».
     */
    public function __construct()
    {
        // Вызов конструктора MY_Controller (проверка авторизации)
        parent::__construct();

        // Загружаем модель заказчиков — основная модель модуля
        $this->load->model('Customer_model');

        // Загружаем хелпер иконок и форматирования типа оплаты
        $this->load->helper('spec');

        // Загружаем хелпер файлового менеджера (get_upload_dir, scan_external_files)
        $this->load->helper('file_manager');

        // Загружаем библиотеку построения дерева задач
        $this->load->library('Task_tree_builder');
    }

    // =========================================================================
    // ПРИВАТНЫЕ УТИЛИТЫ
    // =========================================================================

    /**
     * Отправляет JSON-ответ клиенту с указанным HTTP-статусом.
     *
     * Очищает выходной буфер, устанавливает заголовки и выводит данные.
     * Используется во всех AJAX-методах для единообразного формата ответа.
     *
     * @param array $data   Массив данных для отправки
     * @param int   $status HTTP-статус ответа (по умолчанию 200)
     * @return void
     */
    private function _json_response($data, $status = 200)
    {
        // Очищаем выходной буфер, если он был открыт (предотвращаем мусор в JSON)
        if (ob_get_level() > 0) {
            ob_clean();
        }

        // Устанавливаем HTTP-статус ответа
        http_response_code($status);

        // Указываем Content-Type как JSON для корректной обработки на клиенте
        header('Content-Type: application/json');

        // Кодируем и выводим данные
        echo json_encode($data);

        // Полностью прекращаем выполнение скрипта.
        // ВАЖНО: именно exit, а не return — иначе CI3 может дописать
        // HTML-контент (шаблоны, footer) после JSON, сломав ответ.
        exit;
    }

    // =========================================================================
    // ГЛАВНАЯ СТРАНИЦА МОДУЛЯ «ЗАКАЗЧИКИ»
    // =========================================================================

    /**
     * Отображение основной страницы управления заказчиками.
     *
     * Загружает список заказчиков, активного заказчика, его ТЗ с файлами
     * и дерево задач. Все данные передаются во view для рендеринга.
     *
     * @param int|null $active_customer_id ID активного заказчика (из URL)
     * @return void
     */
    public function index($active_customer_id = null)
    {
        // Получаем ID текущего пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Загружаем Settings_model для чтения настроек пагинации
        $this->load->model('Settings_model');

        // Читаем настройку количества элементов на странице (по умолчанию 25)
        $per_page = (int)$this->Settings_model->get_setting('per_page', 25);

        // Получаем первую порцию заказчиков для сайдбара
        $data['customers'] = $this->Customer_model->get_all($user_id, $per_page, 0);

        // Передаём настройку пагинации во view для load_more
        $data['per_page'] = $per_page;

        // Если активный заказчик не передан в URL — выбираем первого из списка
        if ($active_customer_id === null) {
            // Получаем первого заказчика, если список не пуст
            $all_customers = $this->Customer_model->get_all($user_id, 1, 0);
            if (!empty($all_customers)) {
                $active_customer_id = $all_customers[0]['id'];
            }
        }

        // Проверяем, входит ли активный заказчик в первую страницу списка
        if ($active_customer_id !== null) {
            // Ищем активного заказчика в текущем списке
            $found = false;
            foreach ($data['customers'] as $c) {
                if ($c['id'] == $active_customer_id) {
                    $found = true;
                    break;
                }
            }

            // Если активный заказчик не на первой странице — добавляем его
            if (!$found) {
                // Загружаем данные заказчика по ID
                $active_cust = $this->Customer_model->get_by_id($active_customer_id, $user_id);
                if ($active_cust) {
                    // Добавляем в список для отображения в сайдбаре
                    $data['customers'][] = $active_cust;

                    // Пересортируем по алфавиту после добавления
                    usort($data['customers'], function ($a, $b) {
                        return strcasecmp($a['name'], $b['name']);
                    });
                }
            }
        }

        // Инициализируем переменные для view значениями по умолчанию
        $data['active_customer_id'] = $active_customer_id;
        $data['active_customer'] = null;
        $data['customer_tasks_tree'] = [];
        $data['customer_tasks'] = [];
        $data['specs'] = [];

        // Загружаем данные активного заказчика, если он определён
        if ($active_customer_id !== null) {
            // Получаем полные данные выбранного заказчика
            $data['active_customer'] = $this->Customer_model->get_by_id($active_customer_id, $user_id);

            // Продолжаем только если заказчик найден и принадлежит пользователю
            if ($data['active_customer']) {
                // Загружаем ВСЕ ТЗ с файлами и linked_task_ids ОДНИМ вызовом (без N+1)
                $data['specs'] = $this->Customer_model->get_specs_with_files($active_customer_id, $user_id);

                // Получаем плоский список задач заказчика (для привязки к ТЗ в модалке)
                $data['customer_tasks'] = $this->Customer_model->get_customer_tasks($active_customer_id, $user_id);

                // Загружаем модель задач для получения полного плоского списка
                $this->load->model('Task_model');

                // Получаем ВСЕ задачи пользователя (для построения дерева)
                $raw_tasks = $this->Task_model->get_user_tasks($user_id);

                // Строим иерархическое дерево задач через библиотеку
                $result = $this->task_tree_builder->build(
                    $raw_tasks,
                    $active_customer_id,
                    $user_id,
                    0,            // offset — начинаем с первой задачи
                    $per_page,    // limit — количество корневых задач
                    false         // show_closed — по умолчанию скрываем завершённые
                );

                // Передаём дерево задач во view
                $data['customer_tasks_tree'] = $result['tree'];

                // Флаг наличия следующей страницы задач
                $data['customer_tasks_has_more'] = $result['has_more'];
            }
        }

        // Рендерим страницу «Заказчики» с передачей всех данных
        $this->render_page('customers', $data);
    }

    // =========================================================================
    // CRUD ЗАКАЗЧИКОВ
    // =========================================================================

    /**
     * Создание нового заказчика через модальную форму.
     * POST-метод, перенаправляет на страницу нового заказчика.
     *
     * @return void
     */
    public function add()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем данные из POST-запроса формы
        $name = trim($this->input->post('name') ?? '');
        $notes = trim($this->input->post('notes') ?? '');
        $default_price = $this->input->post('default_price') ?: 0.00;
        $default_prepayment = $this->input->post('default_prepayment') ?: 0.00;
        $default_payment_type = $this->input->post('default_payment_type') ?: 'hourly';

        // Проверяем обязательное поле — имя заказчика
        if (!empty($name)) {
            // Создаём заказчика через модель
            $id = $this->Customer_model->add($user_id, $name, $notes, $default_price, $default_prepayment, $default_payment_type);

            // Перенаправляем на страницу вновь созданного заказчика
            redirect('customers/index/' . $id);
        } else {
            // Если имя пустое — возвращаемся на главную без создания
            redirect('customers');
        }
    }

    /**
     * Редактирование существующего заказчика.
     * POST-метод, перенаправляет обратно на страницу заказчика.
     *
     * @return void
     */
    public function edit()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем данные из POST-запроса формы
        $id = $this->input->post('id');
        $name = trim($this->input->post('name') ?? '');
        $notes = trim($this->input->post('notes') ?? '');
        $default_price = $this->input->post('default_price') ?: 0.00;
        $default_prepayment = $this->input->post('default_prepayment') ?: 0.00;
        $default_payment_type = $this->input->post('default_payment_type') ?: 'hourly';

        // Проверяем обязательные поля — ID и имя
        if (!empty($id) && !empty($name)) {
            // Обновляем данные заказчика через модель
            $this->Customer_model->update($id, $user_id, $name, $notes, $default_price, $default_prepayment, $default_payment_type);

            // Перенаправляем обратно на страницу этого заказчика
            redirect('customers/index/' . $id);
        } else {
            // Если данные некорректны — возвращаемся на главную
            redirect('customers');
        }
    }

    /**
     * Удаление заказчика и всех связанных с ним файлов на диске.
     *
     * @param int $id ID удаляемого заказчика
     * @return void
     */
    public function delete($id)
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Загружаем список ТЗ заказчика для удаления физических файлов
        $specs = $this->Customer_model->get_specs($id);

        // Определяем директорию загрузки через хелпер
        $upload_dir = get_upload_dir();

        // Перебираем все ТЗ для удаления связанных файлов с диска
        foreach ($specs as $spec) {
            // Получаем файлы текущего ТЗ
            $files = $this->Customer_model->get_spec_files($spec['id']);

            // Удаляем каждый физический файл
            foreach ($files as $file) {
                // Пропускаем ссылки — у них нет файлов на диске
                if (!empty($file['is_link'])) {
                    continue;
                }

                // Формируем полный путь к файлу
                $filepath = $upload_dir . $file['filename'];

                // Удаляем файл, если он существует на диске
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
        }

        // Удаляем заказчика из БД (каскадно удалятся ТЗ и записи файлов по FK)
        $this->Customer_model->delete($id, $user_id);

        // Перенаправляем на главную страницу заказчиков
        redirect('customers');
    }

    // =========================================================================
    // CRUD РАБОТА С ТЕХНИЧЕСКИМИ ЗАДАНИЯМИ (ТЗ)
    // =========================================================================

    /**
     * Создание нового ТЗ для заказчика и привязка выбранных задач.
     * POST-метод, перенаправляет обратно на страницу заказчика.
     *
     * @return void
     */
    public function add_spec()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем данные из POST-запроса формы
        $customer_id = $this->input->post('customer_id');
        $title = trim($this->input->post('title') ?? '');
        $content = $this->input->post('content');         // HTML-текст из WYSIWYG-редактора
        $price = $this->input->post('price') ?: 0.00;
        $prepayment = $this->input->post('prepayment') ?: 0.00;
        $payment_type = $this->input->post('payment_type') ?: 'hourly';
        $files_dir = $this->input->post('files_dir') ?: NULL;
        $linked_tasks = $this->input->post('linked_tasks'); // Массив ID привязываемых задач

        // Проверяем обязательные поля — ID заказчика и название ТЗ
        if (!empty($customer_id) && !empty($title)) {
            // Создаём ТЗ в базе данных через модель
            $spec_id = $this->Customer_model->add_spec($customer_id, $title, $content, $price, $prepayment, $payment_type, $files_dir);

            // Привязываем выбранные задачи к новому ТЗ через модель
            if (!empty($linked_tasks) && is_array($linked_tasks)) {
                $this->Customer_model->link_tasks_to_spec($spec_id, $linked_tasks, $customer_id, $user_id);
            }
        }

        // Перенаправляем обратно на страницу заказчика
        redirect('customers/index/' . $customer_id);
    }

    /**
     * Редактирование ТЗ заказчика и обновление привязки задач.
     * POST-метод, перенаправляет обратно на страницу заказчика.
     *
     * @return void
     */
    public function edit_spec()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем данные из POST-запроса формы
        $customer_id = $this->input->post('customer_id');
        $spec_id = $this->input->post('spec_id');
        $title = trim($this->input->post('title') ?? '');
        $content = $this->input->post('content');
        $price = $this->input->post('price') ?: 0.00;
        $prepayment = $this->input->post('prepayment') ?: 0.00;
        $payment_type = $this->input->post('payment_type') ?: 'hourly';
        $files_dir = $this->input->post('files_dir') ?: NULL;
        $linked_tasks = $this->input->post('linked_tasks'); // Массив ID привязанных задач

        // Проверяем обязательные поля — ID ТЗ и название
        if (!empty($spec_id) && !empty($title)) {
            // Обновляем данные ТЗ через модель
            $this->Customer_model->update_spec($spec_id, $title, $content, $price, $prepayment, $payment_type, $files_dir);

            // Отвязываем все текущие задачи от этого ТЗ (для повторной привязки)
            $this->Customer_model->unlink_tasks_from_spec($spec_id, $customer_id, $user_id);

            // Привязываем заново только выбранные задачи
            if (!empty($linked_tasks) && is_array($linked_tasks)) {
                $this->Customer_model->link_tasks_to_spec($spec_id, $linked_tasks, $customer_id, $user_id);
            }
        }

        // Перенаправляем обратно на страницу заказчика
        redirect('customers/index/' . $customer_id);
    }

    /**
     * Удаление ТЗ и связанных с ним физических файлов.
     *
     * @param int $spec_id ID удаляемого ТЗ
     * @return void
     */
    public function delete_spec($spec_id)
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Проверяем, что ТЗ принадлежит текущему пользователю
        if (!$this->Customer_model->verify_spec_ownership($spec_id, $user_id)) {
            // Если ТЗ не принадлежит пользователю — возвращаемся на главную
            redirect('customers');
            return;
        }

        // Загружаем данные ТЗ для получения customer_id (для редиректа)
        $spec = $this->Customer_model->get_spec($spec_id);

        if ($spec) {
            // Запоминаем ID заказчика для редиректа после удаления
            $customer_id = $spec['customer_id'];

            // Определяем директорию загрузки через хелпер
            $upload_dir = get_upload_dir();

            // Получаем список файлов ТЗ для физического удаления
            $files = $this->Customer_model->get_spec_files($spec_id);

            // Удаляем физические файлы с диска
            foreach ($files as $file) {
                // Пропускаем ссылки — у них нет файлов на диске
                if (!empty($file['is_link'])) {
                    continue;
                }

                // Формируем полный путь к файлу
                $filepath = $upload_dir . $file['filename'];

                // Удаляем файл, если он существует
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }

            // Удаляем ТЗ из базы данных (каскадно удалятся записи файлов)
            $this->Customer_model->delete_spec($spec_id);

            // Перенаправляем на страницу заказчика
            redirect('customers/index/' . $customer_id);
        } else {
            // Если ТЗ не найдено — возвращаемся на главную
            redirect('customers');
        }
    }

    // =========================================================================
    // ЗАГРУЗКА И СКАЧИВАНИЕ ФАЙЛОВ ДЛЯ ТЗ
    // =========================================================================

    /**
     * Загрузка файла для конкретного ТЗ через AJAX.
     * Использует стандартную библиотеку CI Upload.
     *
     * @return void
     */
    public function upload_file()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем ID ТЗ из POST-запроса
        $spec_id = $this->input->post('spec_id');

        // Проверяем, что ТЗ принадлежит текущему пользователю
        if (!$this->Customer_model->verify_spec_ownership($spec_id, $user_id)) {
            $this->_json_response(['status' => 'error', 'message' => 'Доступ запрещён'], 403);
            return;
        }

        // Загружаем данные ТЗ для проверки существования
        $spec = $this->Customer_model->get_spec($spec_id);

        // Если ТЗ не найдено — возвращаем ошибку
        if (!$spec) {
            $this->_json_response(['status' => 'error', 'message' => 'ТЗ не найдено']);
            return;
        }

        // Определяем директорию загрузки через хелпер (создаётся автоматически)
        $upload_dir = get_upload_dir();

        // Конфигурируем библиотеку загрузки CodeIgniter
        $config['upload_path']   = $upload_dir;
        $config['allowed_types'] = 'gif|jpg|png|jpeg|pdf|doc|docx|xls|xlsx|txt|zip|rar|7z';
        $config['max_size']      = 10240;  // Максимально 10MB
        $config['encrypt_name']  = TRUE;   // Хешируем имена для безопасности

        // Инициализируем библиотеку загрузки с конфигурацией
        $this->load->library('upload', $config);

        // Пытаемся загрузить файл из поля 'file'
        if (!$this->upload->do_upload('file')) {
            // Если загрузка не удалась — возвращаем текст ошибки
            $this->_json_response(['status' => 'error', 'message' => $this->upload->display_errors('', '')]);
        } else {
            // Загрузка успешна — получаем информацию о загруженном файле
            $upload_data = $this->upload->data();
            $filename = $upload_data['file_name'];
            $orig_name = $upload_data['client_name'];
            $file_size = $upload_data['file_size'] * 1024; // Конвертируем КБ в байты

            // Сохраняем информацию о файле в базу данных
            $file_id = $this->Customer_model->add_spec_file($spec_id, $filename, $orig_name, $file_size);

            // Возвращаем успешный ответ с данными файла для UI
            $this->_json_response([
                'status'       => 'success',
                'file_id'      => $file_id,
                'orig_name'    => $orig_name,
                'download_url' => site_url('customers/download_file/' . $file_id),
                'icon'         => get_file_icon_emoji($orig_name, 0),
            ]);
        }
    }

    /**
     * Скачивание прикреплённого файла ТЗ.
     *
     * @param int $file_id ID скачиваемого файла
     * @return void
     */
    public function download_file($file_id)
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Проверяем, что файл принадлежит текущему пользователю
        if (!$this->Customer_model->verify_file_ownership($file_id, $user_id)) {
            show_404();
            return;
        }

        // Загружаем данные файла из БД
        $file = $this->Customer_model->get_spec_file($file_id);

        if ($file) {
            // Определяем директорию загрузки через хелпер
            $upload_dir = get_upload_dir();

            // Формируем полный путь к файлу на диске
            $filepath = $upload_dir . $file['filename'];

            // Проверяем, что файл существует на диске
            if (file_exists($filepath)) {
                // Загружаем хелпер скачивания CI
                $this->load->helper('download');

                // Отправляем файл пользователю с оригинальным именем
                force_download($file['orig_name'], file_get_contents($filepath));
                return;
            }
        }

        // Если файл не найден в БД или на диске — 404
        show_404();
    }

    /**
     * Удаление файла ТЗ через AJAX.
     *
     * @param int $file_id ID удаляемого файла
     * @return void
     */
    public function delete_file($file_id)
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Проверяем, что файл принадлежит текущему пользователю
        if (!$this->Customer_model->verify_file_ownership($file_id, $user_id)) {
            $this->_json_response(['status' => 'error', 'message' => 'Доступ запрещён'], 403);
            return;
        }

        // Загружаем данные файла из БД
        $file = $this->Customer_model->get_spec_file($file_id);

        if ($file) {
            // Удаляем физический файл только если это не внешняя ссылка
            if ($file['is_link'] == 0) {
                // Определяем директорию загрузки через хелпер
                $upload_dir = get_upload_dir();

                // Формируем полный путь к файлу
                $filepath = $upload_dir . $file['filename'];

                // Удаляем файл с диска, если он существует
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }

            // Удаляем запись о файле из базы данных
            $this->Customer_model->delete_spec_file($file_id);

            // Возвращаем успешный ответ
            $this->_json_response(['status' => 'success']);
        } else {
            // Если файл не найден — возвращаем ошибку
            $this->_json_response(['status' => 'error', 'message' => 'Файл не найден']);
        }
    }

    // =========================================================================
    // РАБОТА С ВНЕШНИМИ ССЫЛКАМИ И СКАЧИВАНИЕ ИЗ ИНТЕРНЕТА
    // =========================================================================

    /**
     * AJAX-метод добавления внешней ссылки в качестве вложения ТЗ.
     *
     * @return void
     */
    public function add_link_ajax()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем данные из POST-запроса
        $spec_id = $this->input->post('spec_id');
        $url = trim($this->input->post('url') ?? '');
        $title = trim($this->input->post('title') ?? '');

        // Проверяем, что ТЗ принадлежит текущему пользователю
        if (!$this->Customer_model->verify_spec_ownership($spec_id, $user_id)) {
            $this->_json_response(['status' => 'error', 'message' => 'Доступ запрещён'], 403);
            return;
        }

        // Проверяем существование ТЗ
        $spec = $this->Customer_model->get_spec($spec_id);
        if (!$spec) {
            $this->_json_response(['status' => 'error', 'message' => 'ТЗ не найдено']);
            return;
        }

        // Валидируем URL — должен быть непустым и корректным
        if (empty($url) || filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            $this->_json_response(['status' => 'error', 'message' => 'Неверный формат ссылки']);
            return;
        }

        // Если название не задано — используем домен из URL
        if (empty($title)) {
            $title = parse_url($url, PHP_URL_HOST) ?: $url;
        }

        // Сохраняем ссылку в базу (is_link = 1, размер = 0, filename = URL)
        $file_id = $this->Customer_model->add_spec_file($spec_id, $url, $title, 0, 1);

        // Возвращаем успешный ответ с данными для UI
        $this->_json_response([
            'status'    => 'success',
            'file_id'   => $file_id,
            'orig_name' => $title,
            'url'       => $url,
            'icon'      => get_file_icon_emoji($url, 1),
        ]);
    }

    /**
     * AJAX-метод скачивания файла из интернета и сохранения его в ТЗ.
     *
     * @return void
     */
    public function download_from_url_ajax()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем данные из POST-запроса
        $spec_id = $this->input->post('spec_id');
        $url = trim($this->input->post('url') ?? '');

        // Проверяем, что ТЗ принадлежит текущему пользователю
        if (!$this->Customer_model->verify_spec_ownership($spec_id, $user_id)) {
            $this->_json_response(['status' => 'error', 'message' => 'Доступ запрещён'], 403);
            return;
        }

        // Проверяем существование ТЗ
        $spec = $this->Customer_model->get_spec($spec_id);
        if (!$spec) {
            $this->_json_response(['status' => 'error', 'message' => 'ТЗ не найдено']);
            return;
        }

        // Валидируем URL
        if (empty($url) || filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            $this->_json_response(['status' => 'error', 'message' => 'Неверный формат ссылки']);
            return;
        }

        // Инициализируем cURL для скачивания файла
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);   // Следуем за редиректами
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);            // Максимум 3 редиректа
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);             // Таймаут 30 секунд
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   // Для Termux без CA-bundle

        // Выполняем запрос
        $file_content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        // Проверяем результат скачивания
        if (curl_errno($ch) || $http_code != 200 || !$file_content) {
            // Формируем текст ошибки для пользователя
            $err = curl_error($ch) ?: 'HTTP Code: ' . $http_code;
            curl_close($ch);
            $this->_json_response(['status' => 'error', 'message' => 'Не удалось скачать файл: ' . $err]);
            return;
        }
        // Закрываем cURL-сессию
        curl_close($ch);

        // Определяем оригинальное имя файла из URL
        $path_parts = pathinfo(parse_url($url, PHP_URL_PATH));
        $orig_name = !empty($path_parts['basename']) ? urldecode($path_parts['basename']) : 'downloaded_file';

        // Если файл не имеет расширения — определяем по Content-Type
        if (empty($path_parts['extension'])) {
            // Маппинг MIME-типов на расширения файлов
            $mime_map = [
                'image/jpeg'                   => 'jpg',
                'image/png'                    => 'png',
                'image/gif'                    => 'gif',
                'image/webp'                   => 'webp',
                'application/pdf'              => 'pdf',
                'text/plain'                   => 'txt',
                'application/zip'              => 'zip',
                'application/x-rar-compressed' => 'rar',
            ];
            // Определяем расширение или ставим 'bin' как fallback
            $ext = isset($mime_map[$content_type]) ? $mime_map[$content_type] : 'bin';
            $orig_name .= '.' . $ext;
        }

        // Генерируем безопасное хешированное имя файла для хранения на сервере
        $encrypted_name = md5(uniqid(mt_rand(), true)) . '.' . pathinfo($orig_name, PATHINFO_EXTENSION);

        // Определяем директорию загрузки через хелпер
        $upload_dir = get_upload_dir();

        // Записываем содержимое файла на диск
        $filepath = $upload_dir . $encrypted_name;
        if (file_put_contents($filepath, $file_content) === FALSE) {
            $this->_json_response(['status' => 'error', 'message' => 'Не удалось записать файл на диск']);
            return;
        }

        // Получаем фактический размер записанного файла
        $file_size = filesize($filepath);

        // Сохраняем запись о файле в БД (is_link = 0 — это локальный файл)
        $file_id = $this->Customer_model->add_spec_file($spec_id, $encrypted_name, $orig_name, $file_size, 0);

        // Возвращаем успешный ответ с данными файла
        $this->_json_response([
            'status'       => 'success',
            'file_id'      => $file_id,
            'orig_name'    => $orig_name,
            'download_url' => site_url('customers/download_file/' . $file_id),
            'file_size'    => $file_size,
            'icon'         => get_file_icon_emoji($orig_name, 0),
        ]);
    }

    // =========================================================================
    // AJAX-МЕТОДЫ ДЛЯ ДИНАМИЧЕСКОЙ ЗАГРУЗКИ ДАННЫХ
    // =========================================================================

    /**
     * AJAX-метод получения списка ТЗ заказчика.
     *
     * @param int $customer_id ID заказчика
     * @return void
     */
    public function get_specs_ajax($customer_id)
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Проверяем принадлежность заказчика пользователю
        $customer = $this->Customer_model->get_by_id($customer_id, $user_id);

        if ($customer) {
            // Получаем список ТЗ заказчика
            $specs = $this->Customer_model->get_specs($customer_id);

            // Возвращаем успешный ответ с данными ТЗ
            $this->_json_response(['status' => 'success', 'data' => $specs]);
        } else {
            // Заказчик не найден или не принадлежит пользователю
            $this->_json_response(['status' => 'error', 'message' => 'Заказчик не найден']);
        }
    }

    /**
     * AJAX-обработчик бесконечного скролла для сайдбара заказчиков.
     *
     * @return void
     */
    public function load_more_ajax()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем параметры пагинации из POST-запроса
        $offset = (int)$this->input->post('offset');
        $active_customer_id = $this->input->post('active_customer_id');

        // Загружаем настройку лимита элементов на странице
        $this->load->model('Settings_model');
        $limit = (int)$this->Settings_model->get_setting('per_page', 25);

        // Получаем следующую порцию заказчиков
        $customers = $this->Customer_model->get_all($user_id, $limit, $offset);

        // Если заказчиков больше нет — возвращаем пустой ответ
        if (empty($customers)) {
            $this->_json_response(['status' => 'success', 'html' => '', 'has_more' => false]);
            return;
        }

        // Рендерим HTML для элементов сайдбара
        $html = '';
        foreach ($customers as $c) {
            // Определяем стили для активного и неактивного элемента
            $is_active = ($active_customer_id == $c['id']);
            $active_classes = $is_active
                ? 'bg-blue-50 border-blue-500 text-blue-700'
                : 'border-transparent text-gray-600 hover:bg-gray-50';

            // Формируем HTML-элемент сайдбара
            $html .= '<a href="' . site_url('customers/index/' . $c['id']) . '" class="customer-item block px-6 py-4 border-l-4 ' . $active_classes . ' transition-all font-medium text-lg">';
            $html .= htmlspecialchars($c['name']);
            $html .= '</a>';
        }

        // Проверяем наличие следующей порции заказчиков
        $next_customers = $this->Customer_model->get_all($user_id, 1, $offset + $limit);
        $has_more = !empty($next_customers);

        // Возвращаем HTML и флаг наличия следующей страницы
        $this->_json_response([
            'status'   => 'success',
            'html'     => $html,
            'has_more' => $has_more,
        ]);
    }

    /**
     * AJAX-метод получения дерева задач заказчика с пагинацией.
     * Использует Task_tree_builder для устранения дублирования логики.
     *
     * @return void
     */
    public function load_tasks_ajax()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем параметры из POST-запроса
        $customer_id = (int)$this->input->post('customer_id');
        $offset = (int)$this->input->post('offset');
        $show_closed = (int)$this->input->post('show_closed');

        // Загружаем настройку лимита задач на страницу
        $this->load->model('Settings_model');
        $limit = (int)$this->Settings_model->get_setting('per_page', 25);

        // Получаем все задачи пользователя для построения дерева
        $this->load->model('Task_model');
        $raw_tasks = $this->Task_model->get_user_tasks($user_id);

        // Строим дерево задач через библиотеку (единый код с index())
        $result = $this->task_tree_builder->build(
            $raw_tasks,
            $customer_id,
            $user_id,
            $offset,
            $limit,
            (bool)$show_closed
        );

        // Если дерево пустое — возвращаем пустой ответ
        if (empty($result['tree'])) {
            $this->_json_response(['status' => 'success', 'html' => '', 'has_more' => false]);
            return;
        }

        // Рендерим HTML через view (шаблон повторного использования)
        $html = $this->load->view(
            'templates/customer_task_tree_loop',
            ['tasks' => $result['tree'], 'level' => 1],
            TRUE // Возвращаем строку, а не выводим
        );

        // Возвращаем HTML и флаг наличия следующей страницы
        $this->_json_response([
            'status'   => 'success',
            'html'     => $html,
            'has_more' => $result['has_more'],
        ]);
    }

    // =========================================================================
    // ВНЕШНИЕ ФАЙЛЫ И ТЕКСТОВОЕ ПРЕВЬЮ
    // =========================================================================

    /**
     * Скачивание внешних рабочих материалов ТЗ из директории files_dir.
     *
     * @return void
     */
    public function download_external_file()
    {
        // Считываем параметры из GET-запроса
        $spec_id = (int)$this->input->get('spec_id');
        $file_param = $this->input->get('file');

        // Безопасное извлечение имени файла без basename (для поддержки кириллицы в Termux)
        $filename = '';
        if (!empty($file_param)) {
            // Нормализуем разделители и берём последний сегмент пути
            $parts = explode('/', str_replace('\\', '/', $file_param));
            $filename = end($parts);
        }

        // Загружаем данные ТЗ для получения пути к директории
        $spec = $this->Customer_model->get_spec($spec_id);

        // Проверяем, что ТЗ найдено, директория задана и файл указан
        if ($spec && !empty($spec['files_dir']) && !empty($filename)) {
            // Формируем полный путь к файлу
            $filepath = rtrim($spec['files_dir'], '/') . '/' . $filename;

            // Проверяем существование и доступность файла
            if (file_exists($filepath) && is_file($filepath) && is_readable($filepath)) {
                // Загружаем хелпер скачивания CI
                $this->load->helper('download');

                // Отправляем файл пользователю
                force_download($filename, file_get_contents($filepath));
                return;
            }
        }

        // Если файл не найден — 404
        show_404();
    }

    /**
     * AJAX-метод получения текстового превью для файлов ТЗ.
     * Поддерживает как прикреплённые файлы (по file_id), так и внешние (по spec_id + file).
     *
     * @return void
     */
    public function get_text_preview_ajax()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Считываем параметры из GET-запроса
        $file_id = $this->input->get('file_id');
        $spec_id = $this->input->get('spec_id');
        $filename_param = $this->input->get('file');

        // Переменные для пути к файлу и оригинального имени
        $filepath = null;
        $orig_name = '';

        // Определяем источник файла — прикреплённый или внешний
        if (!empty($file_id)) {
            // --- Файл прикреплён к ТЗ (хранится в upload_dir) ---

            // Проверяем, что файл принадлежит текущему пользователю
            if (!$this->Customer_model->verify_file_ownership($file_id, $user_id)) {
                $this->_json_response(['status' => 'error', 'message' => 'Доступ запрещён'], 403);
                return;
            }

            // Загружаем данные файла из БД
            $file = $this->Customer_model->get_spec_file($file_id);

            // Проверяем, что файл найден и это не ссылка
            if ($file && $file['is_link'] == 0) {
                $orig_name = $file['orig_name'];

                // Определяем директорию загрузки через хелпер
                $upload_dir = get_upload_dir();

                // Формируем полный путь к файлу
                $filepath = $upload_dir . $file['filename'];
            }
        } elseif (!empty($spec_id) && !empty($filename_param)) {
            // --- Внешний файл из директории files_dir ---

            // Проверяем, что ТЗ принадлежит текущему пользователю
            if (!$this->Customer_model->verify_spec_ownership($spec_id, $user_id)) {
                $this->_json_response(['status' => 'error', 'message' => 'Доступ запрещён'], 403);
                return;
            }

            // Загружаем данные ТЗ для получения пути к директории
            $spec = $this->Customer_model->get_spec($spec_id);

            if ($spec && !empty($spec['files_dir'])) {
                // Безопасное извлечение имени файла без basename (для кириллицы в Termux)
                $parts = explode('/', str_replace('\\', '/', $filename_param));
                $filename = end($parts);
                $orig_name = $filename;

                // Формируем полный путь к внешнему файлу
                $filepath = rtrim($spec['files_dir'], '/') . '/' . $filename;
            }
        }

        // Пытаемся прочитать содержимое файла для превью
        if ($filepath && file_exists($filepath) && is_file($filepath) && is_readable($filepath)) {
            // Определяем расширение для проверки типа файла
            $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

            // Список разрешённых текстовых расширений для превью
            $allowed_exts = ['txt', 'log', 'sql', 'json', 'xml', 'csv', 'md', 'ini', 'cfg', 'yaml', 'yml', 'html', 'js', 'css'];

            // Проверяем, что расширение файла — текстовое
            if (in_array($ext, $allowed_exts)) {
                // Получаем размер файла для определения усечения
                $file_size = filesize($filepath);

                // Максимальный размер для превью — 20 КБ
                $max_read = 20480;

                // Открываем файл для чтения
                $handle = fopen($filepath, "r");

                if ($handle) {
                    // Читаем не более $max_read байт
                    $content = fread($handle, $max_read);
                    fclose($handle);

                    // Конвертируем в UTF-8, если файл в другой кодировке (CP1251 для русских файлов)
                    if (!mb_check_encoding($content, 'UTF-8')) {
                        $content = mb_convert_encoding($content, 'UTF-8', 'CP1251');
                    }

                    // Возвращаем содержимое и флаг усечения
                    $this->_json_response([
                        'status'    => 'success',
                        'content'   => $content,
                        'truncated' => ($file_size > $max_read),
                    ]);
                    return;
                }
            }
        }

        // Если не удалось прочитать файл или формат не поддерживается
        $this->_json_response(['status' => 'error', 'message' => 'Не удалось прочитать файл или формат не поддерживается']);
    }

    /**
     * Загрузка изображения из Quill-редактора (AJAX).
     * Сохраняет картинку на сервер и возвращает её публичный URL.
     * Используется вместо стандартного base64-вставки Quill.
     *
     * @return void
     */
    public function upload_editor_image_ajax()
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Если пользователь не авторизован — отказ
        if (!$user_id) {
            $this->_json_response(['status' => 'error', 'message' => 'Не авторизован'], 403);
            return;
        }

        // Определяем директорию загрузки через хелпер
        $upload_dir = get_upload_dir();

        // Создаём поддиректорию editor_images/ для картинок из редактора
        $images_dir = $upload_dir . 'editor_images/';
        if (!is_dir($images_dir)) {
            // Создаём директорию рекурсивно с правами 0755
            mkdir($images_dir, 0755, true);
        }

        // Конфигурируем библиотеку загрузки CodeIgniter
        $config['upload_path']   = $images_dir;
        $config['allowed_types'] = 'gif|jpg|jpeg|png|webp|svg';
        $config['max_size']      = 0;      // Без ограничения — сожмём после загрузки
        $config['encrypt_name']  = TRUE;   // Хешируем имена для безопасности

        // Инициализируем библиотеку загрузки с конфигурацией
        $this->load->library('upload', $config);

        // Пытаемся загрузить файл из поля 'image'.
        // ob_start/ob_end_clean подавляют PHP 8.5 deprecation notice от finfo_close()
        // внутри CI3 Upload.php — иначе HTML-ошибка ломает JSON-ответ.
        ob_start();
        $upload_ok = $this->upload->do_upload('image');
        ob_end_clean();

        if (!$upload_ok) {
            // Если загрузка не удалась — возвращаем текст ошибки
            $this->_json_response(['status' => 'error', 'message' => $this->upload->display_errors('', '')]);
        } else {
            // Загрузка успешна — получаем информацию о файле
            $upload_data = $this->upload->data();
            $filename = $upload_data['file_name'];
            $full_path = $upload_data['full_path'];

            // --- Автоматическое сжатие изображения ---
            // Максимальный размер по длинной стороне (пикселей).
            // Для ТЗ 1920px более чем достаточно — экономит место на S20+.
            $max_dimension = 1920;

            // Проверяем, что файл — растровое изображение (не SVG)
            // и его размеры превышают лимит
            $is_raster = $upload_data['is_image'] && !in_array($upload_data['file_ext'], ['.svg']);

            if ($is_raster && ($upload_data['image_width'] > $max_dimension || $upload_data['image_height'] > $max_dimension)) {
                // Конфигурируем CI3 Image Manipulation для ресайза
                $img_config['image_library']  = 'gd2';          // GD2 — доступен почти везде
                $img_config['source_image']   = $full_path;     // Исходный файл
                $img_config['maintain_ratio'] = TRUE;            // Сохранять пропорции
                $img_config['master_dim']     = 'auto';          // Авто-определение оси

                // Вычисляем целевые размеры: ограничиваем по длинной стороне
                if ($upload_data['image_width'] >= $upload_data['image_height']) {
                    // Горизонтальная или квадратная — ограничиваем ширину
                    $img_config['width']  = $max_dimension;
                    $img_config['height'] = intval($upload_data['image_height'] * $max_dimension / $upload_data['image_width']);
                } else {
                    // Вертикальная — ограничиваем высоту
                    $img_config['height'] = $max_dimension;
                    $img_config['width']  = intval($upload_data['image_width'] * $max_dimension / $upload_data['image_height']);
                }

                // Пытаемся ресайзить (если GD недоступен — просто оставляем оригинал)
                $this->load->library('image_lib', $img_config);

                // ob_start подавляет возможные warning/notice при работе с GD
                ob_start();
                $this->image_lib->resize();
                ob_end_clean();

                // Освобождаем ресурсы библиотеки
                $this->image_lib->clear();
            }

            // Для JPEG — дополнительно пережимаем с quality=85 для экономии места
            if ($is_raster && in_array(strtolower($upload_data['file_ext']), ['.jpg', '.jpeg'])) {
                // Проверяем доступность GD-функций
                if (function_exists('imagecreatefromjpeg')) {
                    $img = @imagecreatefromjpeg($full_path);
                    if ($img) {
                        // Пересохраняем с качеством 85% (баланс качество/размер)
                        imagejpeg($img, $full_path, 85);
                        imagedestroy($img);
                    }
                }
            }

            // Для PNG — пережимаем с максимальным сжатием
            if ($is_raster && strtolower($upload_data['file_ext']) === '.png') {
                if (function_exists('imagecreatefrompng')) {
                    $img = @imagecreatefrompng($full_path);
                    if ($img) {
                        // Сохраняем alpha-канал
                        imagesavealpha($img, true);
                        // Уровень сжатия PNG: 0-9, 8 — хороший баланс
                        imagepng($img, $full_path, 8);
                        imagedestroy($img);
                    }
                }
            }

            // --- Формируем публичный URL ---
            // Используем Settings_model для получения настройки upload_dir
            $this->load->model('Settings_model');
            $setting_dir = $this->Settings_model->get_setting('upload_dir', 'uploads/specs/');

            // Если путь абсолютный — формируем URL от корня сайта
            if (strpos($setting_dir, '/') === 0 || preg_match('/^[A-Z]:/i', $setting_dir)) {
                // Абсолютный путь — отдаём через контроллер (serve)
                $url = site_url('customers/serve_editor_image/' . $filename);
            } else {
                // Относительный путь — файл доступен напрямую через base_url
                $relative = rtrim($setting_dir, '/') . '/editor_images/' . $filename;
                $url = base_url($relative);
            }

            // Возвращаем успешный ответ с URL картинки
            $this->_json_response([
                'status' => 'success',
                'url'    => $url,
            ]);
        }
    }

    /**
     * Отдаёт картинку редактора по имени файла (для абсолютных путей upload_dir).
     *
     * @param string $filename Имя файла (хешированное)
     * @return void
     */
    public function serve_editor_image($filename)
    {
        // Получаем ID пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Если пользователь не авторизован — отказ
        if (!$user_id) {
            show_404();
            return;
        }

        // Санитизация имени файла — убираем всё кроме допустимых символов
        $filename = basename($filename);

        // Определяем полный путь к файлу
        $upload_dir = get_upload_dir();
        $filepath = $upload_dir . 'editor_images/' . $filename;

        // Проверяем существование файла
        if (!file_exists($filepath)) {
            show_404();
            return;
        }

        // Определяем MIME-тип файла
        $mime = mime_content_type($filepath);

        // Отдаём файл с правильными заголовками
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: public, max-age=86400'); // Кешируем на сутки
        readfile($filepath);
    }
}
