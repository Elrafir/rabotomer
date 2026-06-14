<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Контроллер авторизации.
 * Наследуется от стандартного CI_Controller, так как здесь не нужна проверка
 * авторизации (пользователь только пытается войти).
 */
class Auth extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Страница входа
     */
    public function login() {
        // Если пользователь уже авторизован, отправляем его на главную
        if ($this->session->userdata('user_id')) {
            redirect('tasks');
        }

        $data = [];

        // Проверяем, была ли отправлена форма (POST-запрос)
        if ($this->input->post()) {
            // Настраиваем правила валидации формы (обязательное поле + удаление пробелов)
            $this->form_validation->set_rules('username', 'Имя пользователя', 'required|trim');
            $this->form_validation->set_rules('password', 'Пароль', 'required|trim');

            // Если форма заполнена корректно
            if ($this->form_validation->run() !== FALSE) {
                $username = $this->input->post('username');
                $password = $this->input->post('password');

                // Ищем пользователя в БД с помощью Query Builder
                $query = $this->db->get_where('users', ['username' => $username]);
                $user = $query->row();

                // Проверяем, найден ли пользователь и совпадает ли пароль
                // password_verify автоматически проверяет введённый пароль с хэшом из БД
                if ($user && password_verify($password, $user->password)) {
                    // Пароль верный, сохраняем данные в сессию
                    // Добавлено сохранение темы пользователя в сессию (user_theme)
                    $session_data = [
                        'user_id' => $user->id,
                        'username' => $user->username,
                        'group_id' => $user->group_id,
                        'user_theme' => isset($user->user_theme) ? $user->user_theme : 'theme-default',
                        'user_theme_opacity' => isset($user->user_theme_opacity) ? $user->user_theme_opacity : '1.00',
                        'user_custom_hue' => isset($user->user_custom_hue) ? $user->user_custom_hue : '221'
                    ];
                    $this->session->set_userdata($session_data);

                    // Если поставлена галочка "Запомнить меня"
                    if ($this->input->post('remember_me')) {
                        $this->load->helper('cookie');
                        $token = bin2hex(random_bytes(32)); // Генерируем случайный токен
                        
                        $this->load->model('User_model');
                        $this->User_model->set_remember_token($user->id, $token);
                        
                        // Кука на 30 дней
                        set_cookie('remember_token', $token, 30 * 24 * 60 * 60);
                    }

                    // Перенаправляем на дашборд
                    redirect('tasks');
                } else {
                    // Пароль неверный или пользователя нет
                    $data['error'] = lang('login_error_invalid');
                }
            }
        }

        $this->render_page('login', $data);
    }

    /**
     * Страница регистрации
     */
    public function register() {
        if ($this->session->userdata('user_id')) {
            redirect('tasks');
        }
        $this->render_page('register');
    }

    /**
     * AJAX-обработчик регистрации
     */
    public function register_ajax() {
        $this->form_validation->set_rules('username', 'Логин', 'required|trim|min_length[4]|alpha_numeric|is_unique[users.username]', [
            'is_unique' => 'Этот логин уже занят.'
        ]);
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[users.email]', [
            'is_unique' => 'Этот email уже используется.'
        ]);
        $this->form_validation->set_rules('password', 'Пароль', 'required|trim|min_length[6]');
        $this->form_validation->set_rules('passconf', 'Повтор пароля', 'required|trim|matches[password]');
        $this->form_validation->set_rules('first_name', 'Имя', 'trim|max_length[50]');

        if ($this->form_validation->run() !== FALSE) {
            $this->load->model('User_model');
            $user_id = $this->User_model->create_user(
                $this->input->post('username'),
                $this->input->post('password'),
                $this->input->post('email'),
                $this->input->post('first_name'),
                $this->input->post('last_name') // опционально
            );

            if ($user_id) {
                // Авторизуем пользователя сразу после регистрации
                $user = $this->User_model->get_user_by_id($user_id);
                $this->session->set_userdata([
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'group_id' => $user['group_id'],
                    'user_theme' => isset($user['user_theme']) ? $user['user_theme'] : 'theme-default',
                    'user_theme_opacity' => isset($user['user_theme_opacity']) ? $user['user_theme_opacity'] : '1.00',
                    'user_custom_hue' => isset($user['user_custom_hue']) ? $user['user_custom_hue'] : '221'
                ]);
                $this->session->set_flashdata('success', 'Регистрация прошла успешно! Добро пожаловать.');
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        
        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * Выход из системы
     */
    public function logout() {
        // Удаляем куку "Запомнить меня"
        $this->load->helper('cookie');
        if (get_cookie('remember_token')) {
            $this->load->model('User_model');
            $this->User_model->set_remember_token($this->session->userdata('user_id'), null);
            delete_cookie('remember_token');
        }

        // Уничтожаем сессию
        $this->session->sess_destroy();
        // Перенаправляем на страницу входа
        redirect('auth/login');
    }
}
