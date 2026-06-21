<?php
// Запрещаем прямой доступ к файлу во избежание обхода фреймворка
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Модель управления пользователями (User_model)
 *
 * Осуществляет доступ к данным пользователей, создание учетных записей,
 * проверку авторизационных токенов и обновление профилей.
 */
class User_model extends CI_Model {

    /**
     * Конструктор модели. Загружает базу данных.
     */
    public function __construct() {
        // Вызов родительского конструктора
        parent::__construct();
        // Автоматическая загрузка библиотеки работы с БД
        $this->load->database();
    }

    /**
     * Возвращает список всех зарегистрированных пользователей со статистикой активности
     *
     * @return array Двумерный массив данных пользователей
     */
    public function get_all_users() {
        // Подготавливаем сложный SQL запрос для получения пользователей и агрегатов (кол-во задач, общее время, активность)
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
        // Выполняем SQL запрос через Query Builder и возвращаем результат в виде ассоциативного массива
        return $this->db->query($sql)->result_array();
    }

    /**
     * Создание нового пользователя с хэшированием пароля
     *
     * @param string $username Уникальный логин пользователя
     * @param string $password Сырой пароль для хэширования
     * @param string|null $email Адрес электронной почты
     * @param string|null $first_name Имя пользователя
     * @param string|null $last_name Фамилия пользователя
     * @return int|bool ID созданной записи или false в случае неудачи
     */
    public function create_user($username, $password, $email = null, $first_name = null, $last_name = null) {
        // Ищем пользователя с таким же логином для исключения дублирования
        $query = $this->db->get_where('users', ['username' => $username]);
        
        // Если логин уже существует
        if ($query->num_rows() > 0) {
            // Возвращаем false - регистрация невозможна
            return false;
        }

        // Хэшируем сырой пароль алгоритмом BCRYPT (встроенный в PHP стандарт безопасности)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Формируем массив данных для вставки в СУБД
        $data = [
            'username' => $username,
            'password' => $hashed_password,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'group_id' => 2 // Группа по умолчанию: 2 = обычный пользователь
        ];

        // Производим вставку записи в таблицу users
        $this->db->insert('users', $data);
        
        // Возвращаем ID сгенерированной записи в базе данных
        return $this->db->insert_id();
    }

    /**
     * Удаление пользователя (каскадно удалит его задачи и сессии по связям БД)
     *
     * @param int $id ID удаляемого пользователя
     * @return bool Результат операции (true/false)
     */
    public function delete_user($id) {
        // Указываем критерий отбора по ID
        $this->db->where('id', $id);
        // Производим удаление из таблицы users
        $this->db->delete('users');
        // Возвращаем true, если была затронута хотя бы одна строка
        return $this->db->affected_rows() > 0;
    }

    /**
     * Смена пароля пользователя администратором
     *
     * @param int $user_id ID пользователя
     * @param string $new_password Новый сырой пароль
     * @return bool Результат обновления
     */
    public function change_password($user_id, $new_password) {
        // Генерация безопасного хэша для нового пароля
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Указываем новое значение поля password
        $this->db->set('password', $hashed_password);
        // Фильтруем запись по ID пользователя
        $this->db->where('id', $user_id);
        // Производим обновление таблицы users
        $this->db->update('users');
        
        // Возвращаем true, если пароль успешно изменен в БД
        return $this->db->affected_rows() > 0;
    }

    /**
     * Получить пользователя по его ID
     *
     * @param int $user_id ID искомого пользователя
     * @return array|null Массив данных пользователя или null
     */
    public function get_user_by_id($user_id) {
        // Ищем запись в таблице users по первичному ключу ID
        return $this->db->get_where('users', ['id' => $user_id])->row_array();
    }

    /**
     * Получить пользователя по его логину (username)
     *
     * @param string $username Имя пользователя для входа
     * @return array|null Данные пользователя или null, если не найден
     */
    public function get_user_by_username($username) {
        // Выполняем выборку записи по полю логина (username)
        $query = $this->db->get_where('users', ['username' => $username]);
        // Возвращаем запись в виде ассоциативного массива
        return $query->row_array();
    }

    /**
     * Обновить профиль пользователя
     *
     * @param int $user_id ID пользователя
     * @param array $data Массив обновляемых полей
     * @return bool Результат обновления
     */
    public function update_profile($user_id, $data) {
        // Фильтруем запись по первичному ключу
        $this->db->where('id', $user_id);
        // Производим обновление полей
        $this->db->update('users', $data);
        // Возвращаем true, если изменения применились
        return $this->db->affected_rows() > 0;
    }

    /**
     * Установить токен автоматического входа "Запомнить меня"
     *
     * @param int $user_id ID пользователя
     * @param string|null $token Токен запоминания или null для очистки куки
     * @return void
     */
    public function set_remember_token($user_id, $token) {
        // Указываем ID пользователя для обновления токена
        $this->db->where('id', $user_id);
        // Записываем сгенерированный токен (или null при логауте) в поле remember_token
        $this->db->update('users', ['remember_token' => $token]);
    }

    /**
     * Получить пользователя по сохраненному токену куки "Запомнить меня"
     *
     * @param string $token Считанный из куки токен
     * @return array|null Данные пользователя или null
     */
    public function get_user_by_token($token) {
        // Производим выборку из таблицы users по значению remember_token
        return $this->db->get_where('users', ['remember_token' => $token])->row_array();
    }
}
