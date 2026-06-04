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
                    $session_data = [
                        'user_id' => $user->id,
                        'username' => $user->username
                    ];
                    $this->session->set_userdata($session_data);

                    // Перенаправляем на дашборд
                    redirect('tasks');
                } else {
                    // Пароль неверный или пользователя нет
                    $data['error'] = lang('login_error_invalid');
                }
            }
        }

        // Используем обертку для рендеринга страницы
        $this->render_page('login', $data);
    }

    /**
     * Выход из системы
     */
    public function logout() {
        // Уничтожаем сессию
        $this->session->sess_destroy();
        // Перенаправляем на страницу входа
        redirect('auth/login');
    }
}
