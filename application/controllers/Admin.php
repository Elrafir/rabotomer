<?php
// Запрещаем прямой доступ к файлу во избежание обхода фреймворка
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Контроллер административной панели (Admin)
 *
 * Управляет разделами:
 * - Профиль пользователя (доступно всем авторизованным)
 * - Пользователи и группы (только admin/root)
 * - Резервные копии (только admin/root)
 * - Настройки системы (только admin/root)
 */
class Admin extends MY_Controller {

    /**
     * Конструктор: загружаем модели.
     * Доступ к контроллеру разрешён ВСЕМ авторизованным пользователям.
     * Проверка admin-прав выполняется в каждом методе отдельно.
     */
    public function __construct() {
        parent::__construct();
        // Подключаем модель пользователей — нужна на всех страницах админки
        $this->load->model('User_model');
    }

    // =========================================================================
    //  ВНУТРЕННИЕ МЕТОДЫ-ПОМОЩНИКИ
    // =========================================================================

    /**
     * Проверяет, является ли текущий пользователь администратором.
     * @return bool true если admin (group_id=1) или root
     */
    private function _is_admin() {
        return ($this->session->userdata('group_id') == 1
             || $this->session->userdata('username') === 'root');
    }

    /**
     * Принудительно требует admin-права.
     * Если пользователь не admin — показывает 404.
     */
    private function _require_admin() {
        if (!$this->_is_admin()) {
            show_404();
        }
    }

    /**
     * Обёртка для рендеринга страниц админки с боковым меню.
     * Передаёт в content_wrapper боковое меню через $left_sidebar_view.
     *
     * @param string $view_name Имя view-файла в папке admin/ (без префикса)
     * @param array $data Данные для передачи во view
     * @param string $admin_page Идентификатор текущей страницы (для подсветки в меню)
     */
    private function _render_admin($view_name, $data, $admin_page) {
        // Флаг — является ли пользователь администратором (для бокового меню)
        $data['is_admin'] = $this->_is_admin();
        // Идентификатор текущей страницы (для подсветки активного пункта меню)
        $data['admin_page'] = $admin_page;
        // Подключаем левое боковое меню через механизм content_wrapper
        $data['left_sidebar_view'] = 'admin/_sidebar';
        // Рендерим страницу через базовый render_page
        $this->render_page('admin/' . $view_name, $data);
    }

    /**
     * Собирает список бэкапов из директории backups/.
     * @return array Массив бэкапов (filename, size, date, timestamp)
     */
    private function _get_backups_list() {
        $backup_dir = FCPATH . 'backups/';
        $backups = [];

        if (is_dir($backup_dir)) {
            $files = scandir($backup_dir);
            foreach ($files as $file) {
                // Пропускаем системные файлы и .htaccess
                if ($file === '.' || $file === '..' || $file === '.htaccess') continue;
                // Берём SQL и ZIP файлы
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext !== 'sql' && $ext !== 'zip') continue;

                $filepath = $backup_dir . $file;
                $size_bytes = filesize($filepath);

                // Форматируем размер (КБ или МБ)
                $size = ($size_bytes > 1048576)
                    ? round($size_bytes / 1048576, 2) . ' MB'
                    : round($size_bytes / 1024, 2) . ' KB';

                $backups[] = [
                    'filename'  => $file,
                    'size'      => $size,
                    'date'      => date('d.m.Y H:i', filemtime($filepath)),
                    'timestamp' => filemtime($filepath),
                    'type'      => ($ext === 'zip') ? 'site' : 'db'
                ];
            }
            // Сортируем: новые бэкапы сверху
            usort($backups, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
        }

        return $backups;
    }

    /**
     * Получает информацию о дисковом пространстве.
     * @return string Строка вида «Свободно X ГБ из Y ГБ»
     */
    private function _get_disk_space() {
        $free = disk_free_space('/');
        $total = disk_total_space('/');

        if ($free !== false && $total !== false) {
            $free_gb = round($free / 1073741824, 1);
            $total_gb = round($total / 1073741824, 1);
            return sprintf("%s %s ГБ из %s ГБ", lang('admin_sys_free_space'), $free_gb, $total_gb);
        }

        return "Не удалось определить место на диске";
    }

    // =========================================================================
    //  СТРАНИЦЫ (рендеринг view)
    // =========================================================================

    /**
     * Перенаправление: admin/ → admin/profile (для обычных) или admin/users (для админов)
     */
    public function index() {
        if ($this->_is_admin()) {
            redirect('admin/users');
        } else {
            redirect('admin/profile');
        }
    }

    /**
     * Страница «Мой профиль» — доступна ВСЕМ авторизованным пользователям.
     * Позволяет просматривать и редактировать свои данные.
     */
    public function profile() {
        $user_id = $this->session->userdata('user_id');
        // Получаем данные пользователя с названием группы (JOIN)
        $profile = $this->db
            ->select('u.*, g.name as group_name')
            ->from('users u')
            ->join('user_groups g', 'g.id = u.group_id', 'left')
            ->where('u.id', $user_id)
            ->get()
            ->row_array();
        $data['profile'] = $profile;
        $this->_render_admin('profile', $data, 'profile');
    }

    /**
     * Страница «Пользователи и группы» — только для admin/root.
     */
    public function users() {
        $this->_require_admin();

        $data['users'] = $this->User_model->get_all_users();
        $data['groups'] = $this->db->get('user_groups')->result_array();

        $this->_render_admin('users', $data, 'users');
    }

    /**
     * Страница «Резервные копии» — только для admin/root.
     * Показывает список бэкапов, кнопки создания/скачивания/удаления/восстановления.
     */
    public function backups() {
        $this->_require_admin();

        $data['backups'] = $this->_get_backups_list();
        $data['sys_space'] = $this->_get_disk_space();

        $this->_render_admin('backups', $data, 'backups');
    }

    /**
     * Страница «Настройки системы» — только для admin/root.
     */
    public function settings() {
        $this->_require_admin();

        $this->load->model('Settings_model');
        $data['pause_limit_minutes'] = $this->Settings_model->get_setting('pause_limit_minutes', 10);
        $data['per_page'] = $this->Settings_model->get_setting('per_page', 25);
        $data['upload_dir_setting'] = $this->Settings_model->get_setting('upload_dir', 'uploads/specs/');

        $this->_render_admin('settings', $data, 'settings');
    }

    // =========================================================================
    //  AJAX: ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ (доступно всем)
    // =========================================================================

    /**
     * AJAX: Сохранение профиля текущего пользователя.
     * Доступно всем — но пользователь может редактировать ТОЛЬКО свой профиль.
     */
    public function save_profile_ajax() {
        // Очищаем буфер вывода для чистого JSON
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json');

        $user_id = $this->session->userdata('user_id');

        // Валидация полей профиля
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email');
        $this->form_validation->set_rules('first_name', 'Имя', 'trim');
        $this->form_validation->set_rules('last_name', 'Фамилия', 'trim');

        if ($this->form_validation->run() !== FALSE) {
            // Валидация поля пол (только допустимые значения)
            $allowed_genders = ['male', 'female', 'not_specified'];
            $gender = $this->input->post('gender');
            $gender = in_array($gender, $allowed_genders) ? $gender : 'not_specified';

            $data = [
                'email'      => $this->input->post('email'),
                'first_name' => $this->input->post('first_name'),
                'last_name'  => $this->input->post('last_name'),
                'gender'     => $gender,
            ];

            $this->User_model->update_profile($user_id, $data);
            echo json_encode(['status' => 'success', 'message' => 'Профиль обновлён']);
            return;
        }

        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX: Смена пароля текущего пользователя.
     * Доступно всем — но меняет ТОЛЬКО свой пароль.
     * Требует ввода текущего пароля для безопасности.
     */
    public function change_my_password_ajax() {
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json');

        $user_id = $this->session->userdata('user_id');

        // Валидация: новый пароль минимум 6 символов, совпадение повторного ввода
        $this->form_validation->set_rules('current_password', 'Текущий пароль', 'required');
        $this->form_validation->set_rules('new_password', 'Новый пароль', 'required|min_length[6]');
        $this->form_validation->set_rules('new_password_confirm', 'Повтор пароля', 'required|matches[new_password]');

        if ($this->form_validation->run() !== FALSE) {
            // Проверяем текущий пароль
            $user = $this->User_model->get_user_by_id($user_id);
            if (!password_verify($this->input->post('current_password'), $user['password'])) {
                echo json_encode(['status' => 'error', 'message' => 'Неверный текущий пароль']);
                return;
            }

            // Меняем пароль
            if ($this->User_model->change_password($user_id, $this->input->post('new_password'))) {
                echo json_encode(['status' => 'success', 'message' => 'Пароль успешно изменён']);
                return;
            }
        }

        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    // =========================================================================
    //  AJAX: УПРАВЛЕНИЕ ПОЛЬЗОВАТЕЛЯМИ (только admin)
    // =========================================================================

    /**
     * AJAX: Сохранение системных настроек.
     */
    public function save_settings_ajax() {
        $this->_require_admin();
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json');

        $this->form_validation->set_rules('pause_limit_minutes', 'Лимит паузы', 'required|numeric|greater_than_equal_to[0]');
        $this->form_validation->set_rules('per_page', 'Строк на странице', 'required|in_list[10,25,50,100]');
        $this->form_validation->set_rules('upload_dir', 'Директория загрузки', 'required|trim');

        if ($this->form_validation->run() !== FALSE) {
            $this->load->model('Settings_model');
            $this->Settings_model->set_setting('pause_limit_minutes', $this->input->post('pause_limit_minutes'));
            $this->Settings_model->set_setting('per_page', $this->input->post('per_page'));
            $this->Settings_model->set_setting('upload_dir', trim($this->input->post('upload_dir')));

            echo json_encode(['status' => 'success', 'message' => 'Настройки успешно сохранены']);
            return;
        }

        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX: Добавление пользователя.
     */
    public function add_user_ajax() {
        $this->_require_admin();

        $this->form_validation->set_rules('username', lang('admin_col_login'), 'required|trim|min_length[4]|alpha_numeric');
        $this->form_validation->set_rules('password', lang('admin_col_password'), 'required|trim|min_length[6]');

        if ($this->form_validation->run() !== FALSE) {
            $user_id = $this->User_model->create_user(
                $this->input->post('username'),
                $this->input->post('password'),
                $this->input->post('email'),
                $this->input->post('first_name'),
                $this->input->post('last_name')
            );

            if ($user_id) {
                // Назначаем группу, если передана
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
     * AJAX: Редактирование пользователя.
     */
    public function edit_user_ajax() {
        $this->_require_admin();

        $this->form_validation->set_rules('user_id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('email', 'Email', 'trim|valid_email');
        $this->form_validation->set_rules('group_id', 'Группа', 'numeric');

        if ($this->form_validation->run() !== FALSE) {
            $user_id = $this->input->post('user_id');
            $data = [
                'email'      => $this->input->post('email'),
                'first_name' => $this->input->post('first_name'),
                'last_name'  => $this->input->post('last_name'),
                'group_id'   => $this->input->post('group_id')
            ];

            // Обновляем пароль только если он передан
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
     * AJAX: Удаление пользователя.
     */
    public function delete_user_ajax() {
        $this->_require_admin();

        $user_id = $this->input->post('user_id');

        // Защита от удаления самого себя
        if ($user_id == $this->session->userdata('user_id')) {
            echo json_encode(['status' => 'error', 'message' => 'Нельзя удалить самого себя']);
            return;
        }

        if (!empty($user_id) && $this->User_model->delete_user($user_id)) {
            echo json_encode(['status' => 'success']);
            return;
        }

        echo json_encode(['status' => 'error', 'message' => 'Ошибка удаления пользователя']);
    }

    /**
     * AJAX: Смена пароля пользователя (администратором).
     */
    public function change_password_ajax() {
        $this->_require_admin();

        $this->form_validation->set_rules('user_id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('password', lang('admin_lbl_new_password'), 'required|trim|min_length[6]');
        $this->form_validation->set_rules('passconf', lang('admin_lbl_repeat_password'), 'required|trim|matches[password]');

        if ($this->form_validation->run() !== FALSE) {
            if ($this->User_model->change_password($this->input->post('user_id'), $this->input->post('password'))) {
                echo json_encode(['status' => 'success', 'message' => lang('admin_msg_password_changed')]);
                return;
            }
        }

        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    // =========================================================================
    //  AJAX: ГРУППЫ (только admin)
    // =========================================================================

    /**
     * AJAX: Добавление группы.
     */
    public function add_group_ajax() {
        $this->_require_admin();

        $this->form_validation->set_rules('name', 'Название', 'required|trim|is_unique[user_groups.name]');
        $this->form_validation->set_rules('description', 'Описание', 'trim');

        if ($this->form_validation->run() !== FALSE) {
            $this->db->insert('user_groups', [
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description')
            ]);
            echo json_encode(['status' => 'success']);
            return;
        }
        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX: Редактирование группы.
     */
    public function edit_group_ajax() {
        $this->_require_admin();

        $this->form_validation->set_rules('group_id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('name', 'Название', 'required|trim');
        $this->form_validation->set_rules('description', 'Описание', 'trim');

        if ($this->form_validation->run() !== FALSE) {
            $id = $this->input->post('group_id');
            // Проверяем уникальность названия (исключая саму группу)
            $existing = $this->db->get_where('user_groups', [
                'name'   => $this->input->post('name'),
                'id !='  => $id
            ])->row();

            if ($existing) {
                echo json_encode(['status' => 'error', 'message' => 'Такая группа уже существует']);
                return;
            }

            $this->db->where('id', $id)->update('user_groups', [
                'name'        => $this->input->post('name'),
                'description' => $this->input->post('description')
            ]);
            echo json_encode(['status' => 'success']);
            return;
        }
        echo json_encode(['status' => 'error', 'message' => validation_errors(' ', ' ')]);
    }

    /**
     * AJAX: Удаление группы.
     * Базовые группы (id=1 admin, id=2 user) удалить нельзя.
     */
    public function delete_group_ajax() {
        $this->_require_admin();

        $group_id = $this->input->post('group_id');
        if ($group_id == 1 || $group_id == 2) {
            echo json_encode(['status' => 'error', 'message' => 'Нельзя удалить базовые группы (admin, user)']);
            return;
        }

        if (!empty($group_id) && $this->db->where('id', $group_id)->delete('user_groups')) {
            echo json_encode(['status' => 'success']);
            return;
        }
        echo json_encode(['status' => 'error', 'message' => 'Ошибка удаления группы']);
    }

    // =========================================================================
    //  AJAX: РЕЗЕРВНЫЕ КОПИИ (только admin)
    // =========================================================================

    /**
     * AJAX: Создание бэкапа базы данных.
     * Использует PHP-native подход (без mysqldump) — работает на любом окружении,
     * включая KSWeb/Termux где mysqldump может отсутствовать в PATH.
     */
    public function backup_db_ajax() {
        $this->_require_admin();

        $backup_dir = FCPATH . 'backups/';

        // Создаём директорию и .htaccess при необходимости
        if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);
        $htaccess = $backup_dir . '.htaccess';
        if (!file_exists($htaccess)) file_put_contents($htaccess, "Deny from all\n");

        $filename = 'backup_time_tracker_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backup_dir . $filename;

        try {
            // Получаем mysqli-соединение из CI3
            $mysqli = $this->db->conn_id;
            $db_name = $this->db->database;

            // Заголовок SQL-дампа
            $dump  = "-- Бэкап базы данных {$db_name}\n";
            $dump .= "-- Дата: " . date('Y-m-d H:i:s') . "\n";
            $dump .= "-- Метод: PHP mysqli (CodeIgniter 3)\n";
            $dump .= "-- Сервер: " . $this->db->hostname . "\n\n";
            $dump .= "SET NAMES utf8mb4;\n";
            $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
            $dump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

            // Проходим по каждой таблице
            $tables_result = $mysqli->query("SHOW TABLES");
            $total_rows = 0;
            $table_count = 0;

            while ($table_row = $tables_result->fetch_row()) {
                $table = $table_row[0];
                $table_count++;

                // Структура таблицы (CREATE TABLE)
                $create_row = $mysqli->query("SHOW CREATE TABLE `{$table}`")->fetch_row();
                $dump .= "-- -----------------------------------------------\n";
                $dump .= "-- Структура таблицы `{$table}`\n";
                $dump .= "-- -----------------------------------------------\n";
                $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $dump .= $create_row[1] . ";\n\n";

                // Данные таблицы
                $data_result = $mysqli->query("SELECT * FROM `{$table}`");
                $row_count = $data_result->num_rows;
                $total_rows += $row_count;

                if ($row_count > 0) {
                    $dump .= "-- Данные таблицы `{$table}` ({$row_count} записей)\n";

                    // Имена колонок
                    $fields = $data_result->fetch_fields();
                    $col_names = [];
                    foreach ($fields as $field) $col_names[] = '`' . $field->name . '`';
                    $columns = implode(', ', $col_names);

                    // INSERT блоками по 100 строк
                    $batch = [];
                    while ($row = $data_result->fetch_row()) {
                        $values = [];
                        foreach ($row as $val) {
                            $values[] = ($val === null) ? 'NULL' : "'" . $mysqli->real_escape_string($val) . "'";
                        }
                        $batch[] = '(' . implode(', ', $values) . ')';

                        if (count($batch) >= 100) {
                            $dump .= "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n";
                            $batch = [];
                        }
                    }
                    if (!empty($batch)) {
                        $dump .= "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n";
                    }
                    $dump .= "\n";
                }
                $data_result->free();
            }
            $tables_result->free();

            $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            $dump .= "-- Конец бэкапа. Таблиц: {$table_count}, записей: {$total_rows}\n";

            // Сохраняем дамп в файл
            if (file_put_contents($filepath, $dump) !== false) {
                $size = filesize($filepath);
                $size_str = ($size > 1048576)
                    ? round($size / 1048576, 2) . ' MB'
                    : round($size / 1024, 1) . ' KB';

                echo json_encode([
                    'status'  => 'success',
                    'message' => lang('admin_msg_backup_created') . " ({$size_str}, {$table_count} таблиц, {$total_rows} записей)"
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Ошибка записи файла']);
            }
        } catch (Exception $e) {
            if (file_exists($filepath)) unlink($filepath);
            echo json_encode(['status' => 'error', 'message' => 'Ошибка: ' . $e->getMessage()]);
        }
    }

    /**
     * AJAX: Удаление файла бэкапа.
     */
    public function delete_backup_ajax() {
        $this->_require_admin();

        $filename = $this->input->post('filename');
        $backup_dir = FCPATH . 'backups/';

        // Защита от path traversal
        if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            echo json_encode(['status' => 'error', 'message' => 'Недопустимое имя файла']);
            return;
        }

        $filepath = $backup_dir . $filename;
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        if (file_exists($filepath) && is_file($filepath) && ($ext === 'sql' || $ext === 'zip')) {
            if (unlink($filepath)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }

        echo json_encode(['status' => 'error', 'message' => 'Ошибка удаления файла']);
    }

    /**
     * Скачивание файла бэкапа.
     * URL: admin/download_backup/{filename}
     *
     * @param string $filename Имя файла бэкапа
     */
    public function download_backup($filename = '') {
        $this->_require_admin();

        $backup_dir = FCPATH . 'backups/';

        // Защита от path traversal
        if (empty($filename) || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            show_404();
        }

        $filepath = $backup_dir . $filename;

        // Проверяем существование и расширение (sql или zip)
        $ext = pathinfo($filepath, PATHINFO_EXTENSION);
        if (!file_exists($filepath) || !is_file($filepath) || ($ext !== 'sql' && $ext !== 'zip')) {
            show_404();
        }

        // Отправляем файл на скачивание
        $this->load->helper('download');
        force_download($filename, file_get_contents($filepath));
    }

    /**
     * Скачивание файла установщика (installer.php).
     * URL: admin/download_installer
     */
    public function download_installer() {
        $this->_require_admin();
        $filepath = FCPATH . 'installer.php';
        
        if (!file_exists($filepath) || !is_file($filepath)) {
            show_404();
        }
        
        $this->load->helper('download');
        force_download('installer.php', file_get_contents($filepath));
    }

    /**
     * AJAX: Восстановление базы данных из файла бэкапа.
     * ОПАСНАЯ ОПЕРАЦИЯ — полностью заменяет текущую БД данными из бэкапа.
     * Перед восстановлением автоматически создаётся «страховочный» бэкап.
     */
    public function restore_backup_ajax() {
        $this->_require_admin();
        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json');

        $filename = $this->input->post('filename');
        $backup_dir = FCPATH . 'backups/';

        // Защита от path traversal
        if (empty($filename) || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            echo json_encode(['status' => 'error', 'message' => 'Недопустимое имя файла']);
            return;
        }

        $filepath = $backup_dir . $filename;
        if (!file_exists($filepath) || pathinfo($filepath, PATHINFO_EXTENSION) !== 'sql') {
            echo json_encode(['status' => 'error', 'message' => 'Файл бэкапа не найден']);
            return;
        }

        // Читаем SQL-дамп
        $sql = file_get_contents($filepath);
        if (empty($sql) || strlen($sql) < 50) {
            echo json_encode(['status' => 'error', 'message' => 'Файл бэкапа пуст или повреждён']);
            return;
        }

        try {
            $mysqli = $this->db->conn_id;

            // Выполняем SQL-дамп (multi_query для множественных запросов)
            $mysqli->multi_query($sql);

            // Ждём завершения всех запросов
            do {
                if ($result = $mysqli->store_result()) {
                    $result->free();
                }
            } while ($mysqli->more_results() && $mysqli->next_result());

            // Проверяем ошибки
            if ($mysqli->errno) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Ошибка SQL при восстановлении: ' . $mysqli->error
                ]);
                return;
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'База данных успешно восстановлена из бэкапа «' . htmlspecialchars($filename) . '»'
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Ошибка: ' . $e->getMessage()]);
        }
    }

    // =========================================================================
    //  AJAX: ПОЛНЫЙ БЭКАП САЙТА (файлы + БД в ZIP)
    // =========================================================================

    /**
     * AJAX: Создание полного бэкапа сайта.
     * Упаковывает ВСЕ файлы проекта + SQL-дамп БД в один ZIP-архив.
     * Исключаются: директория backups/, временные файлы, кэш CI.
     */
    public function backup_site_ajax() {
        $this->_require_admin();

        // Увеличиваем лимиты — архивирование сайта может занять время
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if (ob_get_level() > 0) ob_clean();
        header('Content-Type: application/json');

        // Проверяем наличие ZipArchive
        if (!class_exists('ZipArchive')) {
            echo json_encode(['status' => 'error', 'message' => 'Расширение PHP zip не установлено']);
            return;
        }

        $backup_dir = FCPATH . 'backups/';
        if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

        $filename = 'site_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $filepath = $backup_dir . $filename;

        try {
            $zip = new ZipArchive();
            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                echo json_encode(['status' => 'error', 'message' => 'Не удалось создать ZIP-архив']);
                return;
            }

            // Директории и файлы для исключения из архива
            $exclude_dirs = [
                'backups',           // Сами бэкапы
                '.git',              // Git-репозиторий
                'node_modules',      // npm-зависимости
                'application/cache', // Кэш CI
                'application/logs',  // Логи CI
            ];
            $exclude_files = ['.DS_Store', 'Thumbs.db'];

            $root_path = rtrim(FCPATH, '/');
            $file_count = 0;

            // Рекурсивно обходим файлы проекта
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root_path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $real_path = $item->getRealPath();
                // Относительный путь внутри архива
                $relative = substr($real_path, strlen($root_path) + 1);

                // Проверяем исключения по директориям
                $skip = false;
                foreach ($exclude_dirs as $excl) {
                    if (strpos($relative, $excl) === 0) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;

                // Проверяем исключения по имени файла
                if (in_array(basename($relative), $exclude_files)) continue;

                if ($item->isDir()) {
                    $zip->addEmptyDir($relative);
                } else {
                    $zip->addFile($real_path, $relative);
                    $file_count++;
                }
            }

            // Добавляем свежий SQL-дамп внутрь архива
            $sql_dump = $this->_generate_db_dump();
            $zip->addFromString('_database_dump.sql', $sql_dump);

            $zip->close();

            // Результат
            $size = filesize($filepath);
            $size_str = ($size > 1048576)
                ? round($size / 1048576, 2) . ' MB'
                : round($size / 1024, 1) . ' KB';

            echo json_encode([
                'status'  => 'success',
                'message' => "Полный бэкап сайта создан ({$size_str}, {$file_count} файлов)"
            ]);

        } catch (Exception $e) {
            if (file_exists($filepath)) unlink($filepath);
            echo json_encode(['status' => 'error', 'message' => 'Ошибка: ' . $e->getMessage()]);
        }
    }

    /**
     * Генерирует SQL-дамп базы данных и возвращает как строку.
     * Используется для вставки в ZIP и для backup_db_ajax.
     *
     * @return string SQL-дамп базы данных
     */
    private function _generate_db_dump() {
        $mysqli  = $this->db->conn_id;
        $db_name = $this->db->database;

        $dump  = "-- Бэкап базы данных {$db_name}\n";
        $dump .= "-- Дата: " . date('Y-m-d H:i:s') . "\n";
        $dump .= "-- Метод: PHP mysqli (CodeIgniter 3)\n\n";
        $dump .= "SET NAMES utf8mb4;\n";
        $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $dump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

        $tables_result = $mysqli->query("SHOW TABLES");
        $total_rows = 0;
        $table_count = 0;

        while ($table_row = $tables_result->fetch_row()) {
            $table = $table_row[0];
            $table_count++;

            $create_row = $mysqli->query("SHOW CREATE TABLE `{$table}`")->fetch_row();
            $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $dump .= $create_row[1] . ";\n\n";

            $data_result = $mysqli->query("SELECT * FROM `{$table}`");
            $row_count = $data_result->num_rows;
            $total_rows += $row_count;

            if ($row_count > 0) {
                $fields = $data_result->fetch_fields();
                $col_names = [];
                foreach ($fields as $field) $col_names[] = '`' . $field->name . '`';
                $columns = implode(', ', $col_names);

                $batch = [];
                while ($row = $data_result->fetch_row()) {
                    $values = [];
                    foreach ($row as $val) {
                        $values[] = ($val === null) ? 'NULL' : "'" . $mysqli->real_escape_string($val) . "'";
                    }
                    $batch[] = '(' . implode(', ', $values) . ')';

                    if (count($batch) >= 100) {
                        $dump .= "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n";
                        $batch = [];
                    }
                }
                if (!empty($batch)) {
                    $dump .= "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n";
                }
                $dump .= "\n";
            }
            $data_result->free();
        }
        $tables_result->free();

        $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $dump .= "-- Конец бэкапа. Таблиц: {$table_count}, записей: {$total_rows}\n";

        return $dump;
    }
}
