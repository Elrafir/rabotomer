<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Получить значение настройки по ключу
     * @param string $key
     * @param mixed $default_value Значение по умолчанию, если ключ не найден
     * @return mixed
     */
    public function get_setting($key, $default_value = null) {
        $query = $this->db->get_where('settings', ['setting_key' => $key]);
        if ($query->num_rows() > 0) {
            return $query->row()->setting_value;
        }
        return $default_value;
    }

    /**
     * Сохранить или обновить настройку
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function set_setting($key, $value) {
        $query = $this->db->get_where('settings', ['setting_key' => $key]);
        
        if ($query->num_rows() > 0) {
            $this->db->where('setting_key', $key);
            return $this->db->update('settings', ['setting_value' => $value]);
        } else {
            return $this->db->insert('settings', [
                'setting_key' => $key,
                'setting_value' => $value
            ]);
        }
    }
}
