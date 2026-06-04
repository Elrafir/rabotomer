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
            SELECT u.id, u.username, u.created_at,
                   (SELECT COUNT(*) FROM tasks t WHERE t.user_id = u.id) as total_tasks,
                   (SELECT SUM(TIMESTAMPDIFF(SECOND, ts.start_time, ts.end_time)) 
                    FROM time_sessions ts 
                    WHERE ts.user_id = u.id AND ts.end_time IS NOT NULL) as total_time_seconds,
                   (SELECT MAX(ts.end_time) FROM time_sessions ts WHERE ts.user_id = u.id) as last_activity
            FROM users u
            ORDER BY u.created_at DESC
        ";
        return $this->db->query($sql)->result_array();
    }

    /**
     * Создание нового пользователя
     * Проверяет уникальность логина.
     */
    public function create_user($username, $password) {
        // Проверка на дубликат
        $query = $this->db->get_where('users', ['username' => $username]);
        if ($query->num_rows() > 0) {
            return false; // Логин уже занят
        }

        // Хэширование пароля
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $data = [
            'username' => $username,
            'password' => $hashed_password
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
}
