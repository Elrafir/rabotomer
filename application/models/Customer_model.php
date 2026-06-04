<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_model extends CI_Model {

    public function get_all($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('name', 'ASC');
        return $this->db->get('customers')->result_array();
    }

    public function add($user_id, $name, $hourly_rate) {
        $data = [
            'user_id' => $user_id,
            'name' => $name,
            'hourly_rate' => $hourly_rate
        ];
        return $this->db->insert('customers', $data);
    }

    public function delete($customer_id, $user_id) {
        $this->db->where('id', $customer_id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete('customers');
    }
}
