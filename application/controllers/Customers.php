<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customers extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Customer_model');
    }

    public function index() {
        $user_id = $this->session->userdata('user_id');
        $data['customers'] = $this->Customer_model->get_all($user_id);
        $this->render_page('customers', $data);
    }

    public function add() {
        $user_id = $this->session->userdata('user_id');
        $name_post = $this->input->post('name');
        $name = trim($name_post !== null ? $name_post : '');
        $hourly_rate = (float) $this->input->post('hourly_rate');

        if (!empty($name)) {
            $this->Customer_model->add($user_id, $name, $hourly_rate);
        }
        
        redirect('customers');
    }

    public function delete($id) {
        $user_id = $this->session->userdata('user_id');
        $this->Customer_model->delete($id, $user_id);
        
        redirect('customers');
    }
}
