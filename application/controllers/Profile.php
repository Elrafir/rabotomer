<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
    }

    /**
     * Страница профиля
     */
    public function index() {
        $user_id = $this->session->userdata('user_id');
        $data['user'] = $this->User_model->get_user_by_id($user_id);
        
        $this->load->model('Settings_model');
        $data['upload_dir'] = $this->Settings_model->get_setting('upload_dir', 'uploads/specs/');
        
        $this->render_page('profile', $data);
    }

    /**
     * AJAX-обработчик сохранения профиля
     */
    public function save_ajax() {
        $user_id = $this->session->userdata('user_id');

        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
        $this->form_validation->set_rules('first_name', 'Имя', 'trim|max_length[50]');
        $this->form_validation->set_rules('last_name', 'Фамилия', 'trim|max_length[50]');
        $this->form_validation->set_rules('upload_dir', 'Директория хранения файлов ТЗ', 'required|trim');
        
        // Пароль опционально
        if ($this->input->post('password')) {
            $this->form_validation->set_rules('password', 'Новый пароль', 'trim|min_length[6]');
            $this->form_validation->set_rules('passconf', 'Повтор пароля', 'trim|matches[password]');
        }

        if ($this->form_validation->run() !== FALSE) {
            $update_data = [
                'email' => $this->input->post('email'),
                'first_name' => $this->input->post('first_name'),
                'last_name' => $this->input->post('last_name')
            ];

            // Если передан новый пароль, хэшируем его
            if ($this->input->post('password')) {
                $update_data['password'] = password_hash($this->input->post('password'), PASSWORD_BCRYPT);
            }

            // Убеждаемся, что email уникален (кроме текущего пользователя)
            $existing = $this->db->get_where('users', ['email' => $update_data['email'], 'id !=' => $user_id])->row();
            if ($existing) {
                echo json_encode(['status' => 'error', 'message' => 'Этот email уже используется другим пользователем']);
                return;
            }

            // Сохраняем настройку директории ТЗ
            $this->load->model('Settings_model');
            $this->Settings_model->set_setting('upload_dir', trim($this->input->post('upload_dir')));

            $this->User_model->update_profile($user_id, $update_data);
            $this->session->set_flashdata('success', 'Профиль успешно обновлен');
            echo json_encode(['status' => 'success']);
            return;
        }
        
        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX-обработчик сохранения выбранной цветовой темы (Вызывается из JS при клике на кружочек)
     */
    public function save_theme_ajax() {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // Отключаем автоматический выброс исключений БД, чтобы перехватить ошибку
        $this->db->db_debug = FALSE;
        
        // Получаем ID текущего авторизованного пользователя из сессии
        $user_id = $this->session->userdata('user_id');

        // Получаем имя темы, прозрачность и тон из POST-запроса
        $theme = $this->input->post('theme', TRUE);
        $opacity = $this->input->post('opacity', TRUE);
        $hue = $this->input->post('hue', TRUE);

        // Если тема была передана и не пустая
        if (!empty($theme)) {
            $update_data = ['user_theme' => $theme];
            
            // Добавляем прозрачность и тон в данные для обновления, если они переданы
            if ($opacity !== NULL && $opacity !== '') {
                $update_data['user_theme_opacity'] = $opacity;
            }
            if ($hue !== NULL && $hue !== '') {
                $update_data['user_custom_hue'] = $hue;
            }

            // Вызываем модель для обновления данных в БД
            $this->User_model->update_profile($user_id, $update_data);
            
            // Проверяем, произошла ли ошибка БД
            $db_error = $this->db->error();
            if (!empty($db_error['code'])) {
                echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $db_error['message']]);
                return;
            }
            
            // Также сразу обновляем значение темы и прозрачности в текущей сессии
            $this->session->set_userdata('user_theme', $theme);
            if ($opacity !== NULL && $opacity !== '') {
                $this->session->set_userdata('user_theme_opacity', $opacity);
            }
            if ($hue !== NULL && $hue !== '') {
                $this->session->set_userdata('user_custom_hue', $hue);
            }

            // Отдаем успешный JSON-ответ обратно во фронтенд (в JS)
            echo json_encode(['status' => 'success']);
        } else {
            // Если тема не передана, отдаем ошибку
            echo json_encode(['status' => 'error', 'message' => 'Тема не выбрана']);
        }
    }
}
