<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends MY_Controller {

    public function __construct() {
        parent::__construct();
        
        // Жёсткая проверка: доступ только для admin (group_id = 1) или root
        if ($this->session->userdata('group_id') != 1 && $this->session->userdata('username') !== 'root') {
            show_404();
        }

        $this->load->model('User_model');
    }

    /**
     * Вытягивает список пользователей и бэкапов, рендерит страницу
     */
    public function users() {
        $data['users'] = $this->User_model->get_all_users();
        $data['groups'] = $this->db->get('user_groups')->result_array();
        
        // Получаем список бэкапов
        $backup_dir = FCPATH . 'backups/';
        $backups = [];
        if (is_dir($backup_dir)) {
            $files = scandir($backup_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && $file !== '.htaccess' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $filepath = $backup_dir . $file;
                    $size_bytes = filesize($filepath);
                    // Перевод в мегабайты/килобайты
                    if ($size_bytes > 1048576) {
                        $size = round($size_bytes / 1048576, 2) . ' MB';
                    } else {
                        $size = round($size_bytes / 1024, 2) . ' KB';
                    }
                    
                    $backups[] = [
                        'filename' => $file,
                        'size' => $size,
                        'date' => date('d.m.Y H:i', filemtime($filepath)),
                        'timestamp' => filemtime($filepath)
                    ];
                }
            }
            // Сортируем новые бэкапы сверху
            usort($backups, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
        }
        
        // Системная информация (Место на диске)
        $free_space = disk_free_space('/');
        $total_space = disk_total_space('/');
        
        if ($free_space !== false && $total_space !== false) {
            $free_gb = round($free_space / 1073741824, 1);
            $total_gb = round($total_space / 1073741824, 1);
            $data['sys_space'] = sprintf("%s %s ГБ из %s ГБ", lang('admin_sys_free_space'), $free_gb, $total_gb);
        } else {
            $data['sys_space'] = "Не удалось определить место на диске";
        }

        $data['backups'] = $backups;
        
        // Системные настройки
        $this->load->model('Settings_model');
        $data['pause_limit_minutes'] = $this->Settings_model->get_setting('pause_limit_minutes', 10);
        $data['per_page'] = $this->Settings_model->get_setting('per_page', 25);

        $this->render_page('admin/users', $data);
    }
    
    /**
     * AJAX-обработчик сохранения системных настроек
     */
    public function save_settings_ajax() {
        $this->form_validation->set_rules('pause_limit_minutes', 'Лимит паузы', 'required|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('per_page', 'Строк на странице', 'required|in_list[10,25,50,100]');
        
        if ($this->form_validation->run() !== FALSE) {
            $this->load->model('Settings_model');
            $this->Settings_model->set_setting('pause_limit_minutes', $this->input->post('pause_limit_minutes'));
            $this->Settings_model->set_setting('per_page', $this->input->post('per_page'));
            
            echo json_encode(['status' => 'success', 'message' => 'Настройки успешно сохранены']);
            return;
        }
        
        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX-обработчик добавления пользователя
     */
    public function add_user_ajax() {
        // Мощная валидация: минимум 4 символа, только латиница/цифры для логина; минимум 6 символов для пароля
        $this->form_validation->set_rules('username', lang('admin_col_login'), 'required|trim|min_length[4]|alpha_numeric');
        $this->form_validation->set_rules('password', lang('admin_col_password'), 'required|trim|min_length[6]');

        if ($this->form_validation->run() !== FALSE) {
            $username = $this->input->post('username');
            $password = $this->input->post('password');

            $user_id = $this->User_model->create_user(
                $username, 
                $password, 
                $this->input->post('email'), 
                $this->input->post('first_name'), 
                $this->input->post('last_name')
            );

            if ($user_id) {
                // Если передана группа, обновляем её
                if ($this->input->post('group_id')) {
                    $this->User_model->update_profile($user_id, ['group_id' => $this->input->post('group_id')]);
                }
                echo json_encode(['status' => 'success', 'message' => lang('admin_msg_user_created')]);
                return;
            } else {
                echo json_encode(['status' => 'error', 'message' => lang('admin_err_login_taken')]);
                return;
            }
        }

        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX-обработчик редактирования пользователя
     */
    public function edit_user_ajax() {
        $this->form_validation->set_rules('user_id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email');
        $this->form_validation->set_rules('group_id', 'Группа', 'numeric');

        if ($this->form_validation->run() !== FALSE) {
            $user_id = $this->input->post('user_id');
            $data = [
                'email' => $this->input->post('email'),
                'first_name' => $this->input->post('first_name'),
                'last_name' => $this->input->post('last_name'),
                'group_id' => $this->input->post('group_id')
            ];

            if ($this->input->post('password')) {
                $data['password'] = password_hash($this->input->post('password'), PASSWORD_BCRYPT);
            }

            if ($this->User_model->update_profile($user_id, $data)) {
                echo json_encode(['status' => 'success', 'message' => 'Пользователь обновлен']);
            } else {
                echo json_encode(['status' => 'success', 'message' => 'Изменений нет']);
            }
            return;
        }

        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX-обработчик удаления пользователя
     */
    public function delete_user_ajax() {
        $user_id = $this->input->post('user_id');

        // Защита от удаления самого себя (root)
        if ($user_id == $this->session->userdata('user_id')) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete yourself']);
            return;
        }

        if (!empty($user_id)) {
            if ($this->User_model->delete_user($user_id)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Error deleting user']);
    }

    /**
     * AJAX-обработчик смены пароля пользователя
     */
    public function change_password_ajax() {
        $this->form_validation->set_rules('user_id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('password', lang('admin_lbl_new_password'), 'required|trim|min_length[6]');
        $this->form_validation->set_rules('passconf', lang('admin_lbl_repeat_password'), 'required|trim|matches[password]');

        if ($this->form_validation->run() !== FALSE) {
            $user_id = $this->input->post('user_id');
            $password = $this->input->post('password');

            if ($this->User_model->change_password($user_id, $password)) {
                echo json_encode(['status' => 'success', 'message' => lang('admin_msg_password_changed')]);
                return;
            }
        }
        
        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX-обработчик создания бэкапа базы данных
     */
    public function backup_db_ajax() {
        $backup_dir = FCPATH . 'backups/';

        // Создаем папку, если её нет
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }

        // Защищаем папку через .htaccess от скачивания напрямую
        $htaccess_path = $backup_dir . '.htaccess';
        if (!file_exists($htaccess_path)) {
            file_put_contents($htaccess_path, "Deny from all\n");
        }

        // Формируем имя файла
        $filename = 'backup_time_tracker_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backup_dir . $filename;

        // Данные для подключения (тянем из конфига или захардкодим для Termux)
        $db_user = $this->db->username;
        $db_pass = $this->db->password;
        $db_name = $this->db->database;

        // Формируем команду для Termux (mysqldump)
        // В Termux mysql часто работает без пароля для root, но если он есть, подставляем
        $pass_str = !empty($db_pass) ? " -p" . escapeshellarg($db_pass) : "";
        $command = "mysqldump -u " . escapeshellarg($db_user) . $pass_str . " " . escapeshellarg($db_name) . " > " . escapeshellarg($filepath) . " 2>&1";

        // Выполняем команду
        exec($command, $output, $return_var);

        // Проверяем, создался ли файл и не пустой ли он
        if (file_exists($filepath) && filesize($filepath) > 0) {
            echo json_encode(['status' => 'success', 'message' => lang('admin_msg_backup_created')]);
        } else {
            // Если что-то пошло не так, удаляем пустой файл на всякий случай
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            // Можно записать $output в лог для отладки
            echo json_encode(['status' => 'error', 'message' => lang('admin_err_backup_failed'), 'debug' => implode("\n", $output)]);
        }
    }

    /**
     * AJAX-обработчик удаления бэкапа
     */
    public function delete_backup_ajax() {
        $filename = $this->input->post('filename');
        $backup_dir = FCPATH . 'backups/';

        // Базовая защита от выхода за пределы директории
        if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid filename']);
            return;
        }

        $filepath = $backup_dir . $filename;

        if (file_exists($filepath) && is_file($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) === 'sql') {
            if (unlink($filepath)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        
        echo json_encode(['status' => 'error', 'message' => 'Error deleting file']);
    }
    /**
     * AJAX-обработчик добавления группы
     */
    public function add_group_ajax() {
        $this->form_validation->set_rules('name', 'Название', 'required|trim|is_unique[user_groups.name]');
        $this->form_validation->set_rules('description', 'Описание', 'trim');

        if ($this->form_validation->run() !== FALSE) {
            $data = [
                'name' => $this->input->post('name'),
                'description' => $this->input->post('description')
            ];
            $this->db->insert('user_groups', $data);
            echo json_encode(['status' => 'success']);
            return;
        }
        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX-обработчик редактирования группы
     */
    public function edit_group_ajax() {
        $this->form_validation->set_rules('group_id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('name', 'Название', 'required|trim');
        $this->form_validation->set_rules('description', 'Описание', 'trim');

        if ($this->form_validation->run() !== FALSE) {
            $id = $this->input->post('group_id');
            // Проверка уникальности (исключая саму группу)
            $existing = $this->db->get_where('user_groups', ['name' => $this->input->post('name'), 'id !=' => $id])->row();
            if ($existing) {
                echo json_encode(['status' => 'error', 'message' => 'Такая группа уже существует']);
                return;
            }

            $data = [
                'name' => $this->input->post('name'),
                'description' => $this->input->post('description')
            ];
            $this->db->where('id', $id);
            $this->db->update('user_groups', $data);
            echo json_encode(['status' => 'success']);
            return;
        }
        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX-обработчик удаления группы
     */
    public function delete_group_ajax() {
        $group_id = $this->input->post('group_id');
        if ($group_id == 1 || $group_id == 2) {
            echo json_encode(['status' => 'error', 'message' => 'Нельзя удалить базовые группы (admin, user)']);
            return;
        }
        if (!empty($group_id)) {
            $this->db->where('id', $group_id);
            if ($this->db->delete('user_groups')) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Ошибка удаления группы']);
    }
}
