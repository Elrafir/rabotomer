<?php
// Запрещаем прямой доступ к файлу во избежание обхода фреймворка
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Контроллер авторизации (Auth)
 *
 * Управляет сессиями пользователей, обработкой форм входа, регистрации
 * и выходом из системы. Наследуется от MY_Controller для поддержки
 * рендеринга страниц.
 */
class Auth extends MY_Controller {

    /**
     * Конструктор контроллера. Загружает модель пользователей.
     */
    public function __construct() {
        // Вызов родительского конструктора базового класса
        parent::__construct();
        // Подключаем модель пользователей для работы во всех методах
        $this->load->model('User_model');
    }

    /**
     * Отображение страницы входа и обработка авторизации
     *
     * @return void
     */
    public function login() {
        // Если пользователь уже авторизован (сессия активна)
        if ($this->session->userdata('user_id')) {
            // Перенаправляем его непосредственно на рабочий дашборд
            redirect('tasks');
        }

        // Инициализируем пустой массив данных для передачи в шаблон
        $data = [];

        // Проверяем, была ли отправлена POST-форма входа
        if ($this->input->post()) {
            // Настраиваем жесткие правила проверки ввода: обязательность полей и удаление пробелов
            $this->form_validation->set_rules('username', 'Имя пользователя', 'required|trim');
            $this->form_validation->set_rules('password', 'Пароль', 'required|trim');

            // Если валидация полей ввода пройдена успешно
            if ($this->form_validation->run() !== FALSE) {
                // Извлекаем логин и пароль из POST-параметров
                $username = $this->input->post('username');
                $password = $this->input->post('password');

                // Ищем пользователя в БД по его логину через метод модели
                $user = $this->User_model->get_user_by_username($username);

                // Если пользователь найден и хэш пароля совпадает с введенным паролем
                if ($user && password_verify($password, $user['password'])) {
                    
                    // Формируем структуру данных сессии
                    $session_data = [
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'group_id' => $user['group_id'],
                        // Передаем настройки кастомных тем интерфейса
                        'user_theme' => isset($user['user_theme']) ? $user['user_theme'] : 'theme-default',
                        'user_theme_opacity' => isset($user['user_theme_opacity']) ? $user['user_theme_opacity'] : '1.00',
                        'user_custom_hue' => isset($user['user_custom_hue']) ? $user['user_custom_hue'] : '221'
                    ];
                    // Сохраняем сформированный массив данных в сессию CodeIgniter
                    $this->session->set_userdata($session_data);

                    // Если пользователь установил чекбокс "Запомнить меня"
                    if ($this->input->post('remember_me')) {
                        // Загружаем хелпер для установки кук
                        $this->load->helper('cookie');
                        // Генерируем криптографически стойкий случайный токен длиной 32 байта
                        $token = bin2hex(random_bytes(32));
                        
                        // Сохраняем сгенерированный токен запоминания в профиле пользователя в БД
                        $this->User_model->set_remember_token($user['id'], $token);
                        
                        // Устанавливаем куку 'remember_token' в браузере сроком действия на 30 дней
                        set_cookie('remember_token', $token, 30 * 24 * 60 * 60);
                    }

                    // Перенаправляем пользователя на рабочий дашборд задач
                    redirect('tasks');
                } else {
                    // Если пользователь не найден или пароль неверен, выводим ошибку из языкового файла
                    $data['error'] = lang('login_error_invalid');
                }
            }
        }

        // Рендерим страницу входа (шаблон login.php) с передачей данных
        $this->render_page('login', $data);
    }

    /**
     * Отображение страницы регистрации нового пользователя
     *
     * @return void
     */
    public function register() {
        // Если сессия активна, перенаправляем на дашборд
        if ($this->session->userdata('user_id')) {
            redirect('tasks');
        }
        // Рендерим чистую страницу регистрации
        $this->render_page('register');
    }

    /**
     * AJAX-обработчик регистрации пользователя
     *
     * @return void Выводит JSON-ответ
     */
    public function register_ajax() {
        // Устанавливаем правила проверки: логин уникален в таблице users.username
        $this->form_validation->set_rules('username', 'Логин', 'required|trim|min_length[4]|alpha_numeric|is_unique[users.username]', [
            'is_unique' => 'Этот логин уже занят.'
        ]);
        // Email уникален в таблице users.email
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[users.email]', [
            'is_unique' => 'Этот email уже используется.'
        ]);
        // Минимальная длина пароля - 6 символов
        $this->form_validation->set_rules('password', 'Пароль', 'required|trim|min_length[6]');
        // Проверка совпадения повторного ввода пароля
        $this->form_validation->set_rules('passconf', 'Повтор пароля', 'required|trim|matches[password]');
        // Лимит по длине имени
        $this->form_validation->set_rules('first_name', 'Имя', 'trim|max_length[50]');

        // Если все правила валидации формы выполнены без ошибок
        if ($this->form_validation->run() !== FALSE) {
            // Вызываем модель для регистрации новой записи в СУБД
            $user_id = $this->User_model->create_user(
                $this->input->post('username'),
                $this->input->post('password'),
                $this->input->post('email'),
                $this->input->post('first_name'),
                $this->input->post('last_name')
            );

            // Если запись успешно добавлена
            if ($user_id) {
                // Извлекаем полные данные свежесозданного пользователя из БД
                $user = $this->User_model->get_user_by_id($user_id);
                // Сразу авторизуем его, наполнив сессионный массив
                $this->session->set_userdata([
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'group_id' => $user['group_id'],
                    'user_theme' => isset($user['user_theme']) ? $user['user_theme'] : 'theme-default',
                    'user_theme_opacity' => isset($user['user_theme_opacity']) ? $user['user_theme_opacity'] : '1.00',
                    'user_custom_hue' => isset($user['user_custom_hue']) ? $user['user_custom_hue'] : '221'
                ]);
                
                // Устанавливаем уведомление для вывода на дашборде
                $this->session->set_flashdata('success', 'Регистрация прошла успешно! Добро пожаловать.');
                // Возвращаем успешный статус клиенту в JSON
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        
        // В случае ошибок валидации возвращаем их текст в JSON ответе
        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * Выход из системы с очисткой сессий и куки автологина
     *
     * @return void
     */
    public function logout() {
        // Подключаем хелпер кук
        $this->load->helper('cookie');
        
        // Если у пользователя присутствует кука автоматического входа
        if (get_cookie('remember_token')) {
            // Сбрасываем токен в БД для этого пользователя
            $this->User_model->set_remember_token($this->session->userdata('user_id'), null);
            // Удаляем токен-куку из браузера
            delete_cookie('remember_token');
        }

        // Полностью уничтожаем текущую сессию CodeIgniter
        $this->session->sess_destroy();
        // Перенаправляем посетителя на страницу входа
        redirect('auth/login');
    }

    /**
     * AJAX-авторизация (для браузерного расширения)
     */
    public function login_ajax() {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        $user = $this->User_model->get_user_by_username($username);

        if ($user && password_verify($password, $user['password'])) {
            $session_data = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'group_id' => $user['group_id'],
                'user_theme' => isset($user['user_theme']) ? $user['user_theme'] : 'theme-default',
                'user_theme_opacity' => isset($user['user_theme_opacity']) ? $user['user_theme_opacity'] : '1.00',
                'user_custom_hue' => isset($user['user_custom_hue']) ? $user['user_custom_hue'] : '221'
            ];
            $this->session->set_userdata($session_data);

            // Устанавливаем токен для запоминания сессии
            $this->load->helper('cookie');
            $token = bin2hex(random_bytes(32));
            $this->User_model->set_remember_token($user['id'], $token);
            set_cookie('remember_token', $token, 30 * 24 * 60 * 60);

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Неверный логин или пароль']);
        }
    }
}
