<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Базовый контроллер приложения.
 * Все рабочие контроллеры (где нужна авторизация) должны наследоваться от него.
 * Он обеспечивает централизованную проверку сессии пользователя.
 */
class MY_Controller extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // Проверяем, есть ли 'user_id' в сессии
        // Пропускаем проверку, если мы уже находимся в контроллере Auth (чтобы избежать бесконечного цикла)
        $current_controller = $this->router->fetch_class();
        
        if (!$this->session->userdata('user_id')) {
            // Пытаемся авторизовать по куке "Запомнить меня"
            $this->load->helper('cookie');
            $token = get_cookie('remember_token');
            if ($token) {
                $this->load->model('User_model');
                $user = $this->User_model->get_user_by_token($token);
                if ($user) {
                    $this->session->set_userdata([
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'group_id' => $user['group_id'], // Запоминаем группу в сессии
                        'user_theme' => isset($user['user_theme']) ? $user['user_theme'] : 'theme-default', // Сохраняем тему при авто-логине
                        'user_theme_opacity' => isset($user['user_theme_opacity']) ? $user['user_theme_opacity'] : '1.00', // Сохраняем прозрачность темы
                        'user_custom_hue' => isset($user['user_custom_hue']) ? $user['user_custom_hue'] : '221' // Сохраняем кастомный тон
                    ]);
                }
            }
            
            // Если всё ещё нет авторизации и мы не в Auth
            if (!$this->session->userdata('user_id') && $current_controller !== 'auth') {
                if ($this->input->is_ajax_request()) {
                    // При AJAX-запросе для SPA перезагружаем страницу, чтобы сработал редирект
                    echo "<script>window.location.href = '" . site_url('auth/login') . "';</script>";
                    exit;
                }
                // Перенаправляем на страницу входа
                redirect('auth/login');
            }
        }
    }

    /**
     * Обертка для рендеринга страниц с использованием шаблона.
     * Загружает header, body (в который вкладывается $inner_view) и footer.
     * 
     * @param string $inner_view Имя файла представления для загрузки внутри body
     * @param array $data Массив данных для передачи в представления
     */
    protected function render_page($inner_view, $data = []) {
        // Передаем текущую тему пользователя (из сессии) во вьюху (по умолчанию theme-default)
        $data['user_theme'] = $this->session->userdata('user_theme') ? $this->session->userdata('user_theme') : 'theme-default';
        $data['user_theme_opacity'] = $this->session->userdata('user_theme_opacity') ? $this->session->userdata('user_theme_opacity') : '1.00';
        $data['user_custom_hue'] = $this->session->userdata('user_custom_hue') !== null ? $this->session->userdata('user_custom_hue') : '221';

        if ($this->input->is_ajax_request()) {
            // Для AJAX (SPA) нам нужно передать active_session, так как он обычно загружается в body.php
            $this->load->model('Task_model');
            $user_id = $this->session->userdata('user_id');
            $data['active_session'] = $user_id ? $this->Task_model->get_active_session($user_id) : null;
            
            // Отдаем контент вместе с боковыми панелями
            $data['inner_view'] = $inner_view;
            $this->load->view('templates/content_wrapper', $data);
        } else {
            // Обычный запрос (отдаем всё)
            $data['inner_view'] = $inner_view;
            $this->load->view('templates/header', $data);
            $this->load->view('templates/body', $data);
            $this->load->view('templates/footer', $data);
        }
    }
}
