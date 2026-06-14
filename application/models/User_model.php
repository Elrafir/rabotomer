<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Возвращает список всех зарегистрированных пользователей со статистикой
     */
    public function get_all_users() {
        $sql = "
            SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.group_id, u.created_at,
                   g.name as group_name, g.description as group_description,
                   (SELECT COUNT(*) FROM tasks t WHERE t.user_id = u.id) as total_tasks,
                   (SELECT SUM(TIMESTAMPDIFF(SECOND, ts.start_time, ts.end_time)) 
                    FROM time_sessions ts 
                    WHERE ts.user_id = u.id AND ts.end_time IS NOT NULL) as total_time_seconds,
                   (SELECT MAX(ts.end_time) FROM time_sessions ts WHERE ts.user_id = u.id) as last_activity
            FROM users u
            LEFT JOIN user_groups g ON u.group_id = g.id
            ORDER BY u.created_at DESC
        ";
        return $this->db->query($sql)->result_array();
    }

    /**
     * Создание нового пользователя
     * Проверяет уникальность логина.
     */
    public function create_user($username, $password, $email = null, $first_name = null, $last_name = null) {
        // Проверка на дубликат
        $query = $this->db->get_where('users', ['username' => $username]);
        if ($query->num_rows() > 0) {
            return false; // Логин уже занят
        }

        // Хэширование пароля
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $data = [
            'username' => $username,
            'password' => $hashed_password,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'group_id' => 2 // 2 = user (по умолчанию)
        ];

        $this->db->insert('users', $data);
        return $this->db->insert_id();
    }

    /**
     * Удаление пользователя (каскадно удалит все его задачи и сессии)
     */
    public function delete_user($id) {
        $this->db->where('id', $id);
        $this->db->delete('users');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Смена пароля пользователя администратором
     */
    public function change_password($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $this->db->set('password', $hashed_password);
        $this->db->where('id', $user_id);
        $this->db->update('users');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Получить пользователя по ID
     */
    public function get_user_by_id($user_id) {
        return $this->db->get_where('users', ['id' => $user_id])->row_array();
    }

    /**
     * Обновить профиль пользователя
     */
    public function update_profile($user_id, $data) {
        $this->db->where('id', $user_id);
        $this->db->update('users', $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Установить токен "Запомнить меня"
     */
    public function set_remember_token($user_id, $token) {
        $this->db->where('id', $user_id);
        $this->db->update('users', ['remember_token' => $token]);
    }

    /**
     * Получить пользователя по токену "Запомнить меня"
     */
    public function get_user_by_token($token) {
        return $this->db->get_where('users', ['remember_token' => $token])->row_array();
    }
}
