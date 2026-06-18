<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        
        // Автоматическая миграция: заменяем hourly_rate на notes
        if (!$this->db->field_exists('notes', 'customers')) {
            $this->db->query("ALTER TABLE customers ADD COLUMN notes TEXT NULL AFTER name");
            if ($this->db->field_exists('hourly_rate', 'customers')) {
                $this->db->query("ALTER TABLE customers DROP COLUMN hourly_rate");
            }
        }

        // Автоматическая миграция: создание таблицы customer_specs для хранения ТЗ
        if (!$this->db->table_exists('customer_specs')) {
            $this->db->query("
                CREATE TABLE customer_specs (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT UNSIGNED NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    content TEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_spec_customer_id FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // Автоматическая миграция: создание таблицы customer_spec_files для хранения файлов ТЗ
        if (!$this->db->table_exists('customer_spec_files')) {
            $this->db->query("
                CREATE TABLE customer_spec_files (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    spec_id INT UNSIGNED NOT NULL,
                    filename VARCHAR(255) NOT NULL,
                    orig_name VARCHAR(255) NOT NULL,
                    file_size INT UNSIGNED NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_file_spec_id FOREIGN KEY (spec_id) REFERENCES customer_specs (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // Дополнительные колонки для финансовых дефолтов в customers
        if (!$this->db->field_exists('default_price', 'customers')) {
            $this->db->query("ALTER TABLE customers ADD COLUMN default_price DECIMAL(10,2) NULL DEFAULT 0.00 AFTER notes");
        }
        if (!$this->db->field_exists('default_prepayment', 'customers')) {
            $this->db->query("ALTER TABLE customers ADD COLUMN default_prepayment DECIMAL(10,2) NULL DEFAULT 0.00 AFTER default_price");
        }
        if (!$this->db->field_exists('default_payment_type', 'customers')) {
            $this->db->query("ALTER TABLE customers ADD COLUMN default_payment_type VARCHAR(50) NULL DEFAULT 'hourly' AFTER default_prepayment");
        }

        // Финансовые поля в ТЗ
        if (!$this->db->field_exists('price', 'customer_specs')) {
            $this->db->query("ALTER TABLE customer_specs ADD COLUMN price DECIMAL(10,2) NULL DEFAULT 0.00 AFTER title");
        }
        if (!$this->db->field_exists('prepayment', 'customer_specs')) {
            $this->db->query("ALTER TABLE customer_specs ADD COLUMN prepayment DECIMAL(10,2) NULL DEFAULT 0.00 AFTER price");
        }
        if (!$this->db->field_exists('payment_type', 'customer_specs')) {
            $this->db->query("ALTER TABLE customer_specs ADD COLUMN payment_type VARCHAR(50) NULL DEFAULT 'hourly' AFTER prepayment");
        }

        // Флаг ссылки в файлах ТЗ
        if (!$this->db->field_exists('is_link', 'customer_spec_files')) {
            $this->db->query("ALTER TABLE customer_spec_files ADD COLUMN is_link TINYINT UNSIGNED DEFAULT 0 AFTER file_size");
        }

        // Поле привязки ТЗ в задачах
        if (!$this->db->field_exists('spec_id', 'tasks')) {
            $this->db->query("ALTER TABLE tasks ADD COLUMN spec_id INT UNSIGNED NULL DEFAULT NULL AFTER customer_id");
        }
    }

    /**
     * Получить список всех заказчиков пользователя
     * 
     * @param int $user_id ID авторизованного пользователя
     * @return array Список заказчиков
     */
    public function get_all($user_id, $limit = NULL, $offset = NULL) {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('name', 'ASC');
        if ($limit !== NULL) {
            $this->db->limit($limit, $offset);
        }
        return $this->db->get('customers')->result_array();
    }

    /**
     * Получить заказчика по ID
     * 
     * @param int $customer_id ID заказчика
     * @param int $user_id ID пользователя
     * @return array|null Данные заказчика или null
     */
    public function get_by_id($customer_id, $user_id) {
        $this->db->where('id', $customer_id);
        $this->db->where('user_id', $user_id);
        return $this->db->get('customers')->row_array();
    }

    /**
     * Добавить нового заказчика с дефолтными параметрами
     * 
     * @param int $user_id ID пользователя
     * @param string $name Название заказчика
     * @param string $notes Заметки
     * @param float $default_price Ценник по умолчанию
     * @param float $default_prepayment Предоплата по умолчанию
     * @param string $default_payment_type Тип оплаты по умолчанию (fixed/hourly)
     * @return int ID добавленной записи
     */
    public function add($user_id, $name, $notes, $default_price = 0.00, $default_prepayment = 0.00, $default_payment_type = 'hourly') {
        $data = [
            'user_id' => $user_id,
            'name' => $name,
            'notes' => $notes,
            'default_price' => (float)$default_price,
            'default_prepayment' => (float)$default_prepayment,
            'default_payment_type' => $default_payment_type
        ];
        $this->db->insert('customers', $data);
        return $this->db->insert_id();
    }

    /**
     * Обновить данные заказчика с дефолтными параметрами
     * 
     * @param int $customer_id ID заказчика
     * @param int $user_id ID пользователя
     * @param string $name Название заказчика
     * @param string $notes Заметки
     * @param float $default_price Ценник по умолчанию
     * @param float $default_prepayment Предоплата по умолчанию
     * @param string $default_payment_type Тип оплаты по умолчанию (fixed/hourly)
     * @return bool Результат выполнения
     */
    public function update($customer_id, $user_id, $name, $notes, $default_price = 0.00, $default_prepayment = 0.00, $default_payment_type = 'hourly') {
        $data = [
            'name' => $name,
            'notes' => $notes,
            'default_price' => (float)$default_price,
            'default_prepayment' => (float)$default_prepayment,
            'default_payment_type' => $default_payment_type
        ];
        $this->db->where('id', $customer_id);
        $this->db->where('user_id', $user_id);
        return $this->db->update('customers', $data);
    }

    /**
     * Удалить заказчика
     * 
     * @param int $customer_id ID заказчика
     * @param int $user_id ID пользователя
     * @return bool Результат выполнения
     */
    public function delete($customer_id, $user_id) {
        $this->db->where('id', $customer_id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete('customers');
    }

    // =========================================================================
    // МЕТОДЫ РАБОТЫ С ТЕХНИЧЕСКИМИ ЗАДАНИЯМИ (ТЗ)
    // =========================================================================

    /**
     * Получить список ТЗ для заказчика
     * 
     * @param int $customer_id ID заказчика
     * @return array Список ТЗ
     */
    public function get_specs($customer_id) {
        $this->db->where('customer_id', $customer_id);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('customer_specs')->result_array();
    }

    /**
     * Получить конкретное ТЗ по его ID
     * 
     * @param int $spec_id ID ТЗ
     * @return array|null Данные ТЗ или null
     */
    public function get_spec($spec_id) {
        $this->db->where('id', $spec_id);
        return $this->db->get('customer_specs')->row_array();
    }

    /**
     * Добавить новое ТЗ для заказчика
     * 
     * @param int $customer_id ID заказчика
     * @param string $title Название ТЗ
     * @param string $content HTML-текст ТЗ
     * @param float $price Ценник ТЗ
     * @param float $prepayment Предоплата ТЗ
     * @param string $payment_type Тип оплаты ТЗ (fixed/hourly)
     * @param string|null $files_dir Путь к внешней директории с файлами
     * @return int ID добавленной записи
     */
    public function add_spec($customer_id, $title, $content, $price = 0.00, $prepayment = 0.00, $payment_type = 'hourly', $files_dir = NULL) {
        $data = [
            'customer_id' => $customer_id,
            'title' => $title,
            'content' => $content,
            'price' => (float)$price,
            'prepayment' => (float)$prepayment,
            'payment_type' => $payment_type,
            'files_dir' => empty($files_dir) ? NULL : trim($files_dir),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('customer_specs', $data);
        return $this->db->insert_id();
    }

    /**
     * Обновить ТЗ
     * 
     * @param int $spec_id ID ТЗ
     * @param string $title Название ТЗ
     * @param string $content HTML-текст ТЗ
     * @param float $price Ценник ТЗ
     * @param float $prepayment Предоплата ТЗ
     * @param string $payment_type Тип оплаты ТЗ (fixed/hourly)
     * @return bool Результат выполнения
     */
    public function update_spec($spec_id, $title, $content, $price = 0.00, $prepayment = 0.00, $payment_type = 'hourly') {
        $data = [
            'title' => $title,
            'content' => $content,
            'price' => (float)$price,
            'prepayment' => (float)$prepayment,
            'payment_type' => $payment_type
        ];
        $this->db->where('id', $spec_id);
        return $this->db->update('customer_specs', $data);
    }

    /**
     * Удалить ТЗ (перед удалением отвязывает все задачи)
     * 
     * @param int $spec_id ID ТЗ
     * @return bool Результат выполнения
     */
    public function delete_spec($spec_id) {
        // Отвязываем задачи от ТЗ
        $this->db->where('spec_id', $spec_id);
        $this->db->update('tasks', ['spec_id' => NULL]);

        $this->db->where('id', $spec_id);
        return $this->db->delete('customer_specs');
    }

    /**
     * Получить список всех задач конкретного заказчика
     * 
     * @param int $customer_id ID заказчика
     * @param int $user_id ID авторизованного пользователя
     * @return array Список задач
     */
    public function get_customer_tasks($customer_id, $user_id) {
        $this->db->where('customer_id', $customer_id);
        $this->db->where('user_id', $user_id);
        $this->db->where('status', 'active');
        $this->db->where('deleted_at IS NULL', null, false);
        $this->db->order_by('created_at', 'ASC');
        return $this->db->get('tasks')->result_array();
    }

    // =========================================================================
    // МЕТОДЫ РАБОТЫ С ФАЙЛАМИ ТЗ
    // =========================================================================

    /**
     * Получить список прикрепленных файлов для ТЗ
     * 
     * @param int $spec_id ID ТЗ
     * @return array Список файлов
     */
    public function get_spec_files($spec_id) {
        $this->db->where('spec_id', $spec_id);
        return $this->db->get('customer_spec_files')->result_array();
    }

    /**
     * Получить данные файла по его ID
     * 
     * @param int $file_id ID файла
     * @return array|null Данные файла
     */
    public function get_spec_file($file_id) {
        $this->db->where('id', $file_id);
        return $this->db->get('customer_spec_files')->row_array();
    }

    /**
     * Добавить запись о файле или ссылке в базу данных
     * 
     * @param int $spec_id ID ТЗ
     * @param string $filename Уникальное имя файла или URL-адрес ссылки
     * @param string $orig_name Оригинальное имя или название ссылки
     * @param int $file_size Размер файла в байтах (0 для ссылок)
     * @param int $is_link Является ли ссылка прикрепленной (1 - да, 0 - нет)
     * @return int ID добавленной записи
     */
    public function add_spec_file($spec_id, $filename, $orig_name, $file_size, $is_link = 0) {
        $data = [
            'spec_id' => $spec_id,
            'filename' => $filename,
            'orig_name' => $orig_name,
            'file_size' => $file_size,
            'is_link' => $is_link,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('customer_spec_files', $data);
        return $this->db->insert_id();
    }

    /**
     * Удалить запись о файле из базы данных
     * 
     * @param int $file_id ID файла
     * @return bool Результат выполнения
     */
    public function delete_spec_file($file_id) {
        $this->db->where('id', $file_id);
        return $this->db->delete('customer_spec_files');
    }
}
