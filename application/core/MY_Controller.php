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
        
        if (!$this->session->userdata('user_id') && $current_controller !== 'auth') {
            // Перенаправляем на страницу входа
            redirect('auth/login');
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
        if ($this->input->is_ajax_request()) {
            // Для AJAX (SPA) нам нужно передать active_session, так как он обычно загружается в body.php
            $this->load->model('Task_model');
            $user_id = $this->session->userdata('user_id');
            $data['active_session'] = $user_id ? $this->Task_model->get_active_session($user_id) : null;
            
            // Отдаем только контент (без хидера и футера)
            $this->load->view($inner_view, $data);
        } else {
            // Обычный запрос (отдаем всё)
            $data['inner_view'] = $inner_view;
            $this->load->view('templates/header', $data);
            $this->load->view('templates/body', $data);
            $this->load->view('templates/footer', $data);
        }
    }
}
