<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calculations extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Calculation_model');
        $this->load->model('Task_model');
    }

    public function index() {
        $user_id = $this->session->userdata('user_id');
        $data['packages'] = $this->Calculation_model->get_packages($user_id);
        $data['left_sidebar_view'] = 'sidebars/statistics';
        $this->render_page('calculations/index', $data);
    }

    public function view($id) {
        $user_id = $this->session->userdata('user_id');
        $package = $this->Calculation_model->get_package($id, $user_id);
        if (!$package) {
            show_404();
        }
        
        $data['package'] = $package;
        $data['stats'] = $this->Calculation_model->get_package_statistics($id);
        $data['left_sidebar_view'] = 'sidebars/statistics';
        $this->render_page('calculations/view', $data);
    }

    public function create() {
        $user_id = $this->session->userdata('user_id');
        $title = trim($this->input->post('title') ?? '');
        $notes = $this->input->post('notes');
        $total_sum = (float)$this->input->post('total_sum');
        $calendar_time = (float)$this->input->post('calendar_time');
        $calendar_time_type = $this->input->post('calendar_time_type') ?: 'days';
        $status = $this->input->post('status') ?: 'draft';

        if (!empty($title)) {
            $id = $this->Calculation_model->create_package($user_id, $title, $notes, $total_sum, $calendar_time, $calendar_time_type, $status);
            redirect('calculations/view/' . $id);
        } else {
            redirect('calculations');
        }
    }

    public function update($id) {
        $user_id = $this->session->userdata('user_id');
        $title = trim($this->input->post('title') ?? '');
        $notes = $this->input->post('notes');
        $total_sum = (float)$this->input->post('total_sum');
        $calendar_time = (float)$this->input->post('calendar_time');
        $calendar_time_type = $this->input->post('calendar_time_type') ?: 'days';
        $status = $this->input->post('status') ?: 'draft';

        if (!empty($title)) {
            $this->Calculation_model->update_package($id, $user_id, $title, $notes, $total_sum, $calendar_time, $calendar_time_type, $status);
        }
        
        redirect('calculations/view/' . $id);
    }

    public function delete($id) {
        $user_id = $this->session->userdata('user_id');
        $this->Calculation_model->delete_package($id, $user_id);
        redirect('calculations');
    }

    // AJAX Endpoints
    public function add_task_ajax() {
        $user_id = $this->session->userdata('user_id');
        $package_id = $this->input->post('package_id');
        $task_id = $this->input->post('task_id');
        
        // Verify package ownership
        $pkg = $this->Calculation_model->get_package($package_id, $user_id);
        if ($pkg) {
            if ($this->Calculation_model->add_task_to_package($package_id, $task_id)) {
                echo json_encode(['status' => 'success']);
                return;
            }
        }
        echo json_encode(['status' => 'error']);
    }

    public function remove_task_ajax() {
        $user_id = $this->session->userdata('user_id');
        $package_id = $this->input->post('package_id');
        $task_id = $this->input->post('task_id');
        
        $pkg = $this->Calculation_model->get_package($package_id, $user_id);
        if ($pkg) {
            $this->Calculation_model->remove_task_from_package($package_id, $task_id);
            echo json_encode(['status' => 'success']);
            return;
        }
        echo json_encode(['status' => 'error']);
    }

    public function search_tasks_ajax() {
        $user_id = $this->session->userdata('user_id');
        $start_date = $this->input->post('start_date');
        $end_date = $this->input->post('end_date');
        
        $tasks = $this->Calculation_model->get_tasks_for_period($user_id, $start_date, $end_date);
        
        // format output
        $html = '';
        foreach ($tasks as $t) {
            $html .= '<div class="task-draggable bg-white border border-gray-200 p-3 mb-2 rounded shadow-sm cursor-move flex justify-between items-center" data-id="' . $t['id'] . '" draggable="true">';
            $html .= '<div><span class="font-bold text-sm text-gray-800">' . htmlspecialchars($t['title']) . '</span><br><span class="text-xs text-gray-500">' . htmlspecialchars($t['customer_name'] ?? 'Без заказчика') . '</span></div>';
            $html .= '<button type="button" class="text-blue-500 hover:text-blue-700 p-1" onclick="addTaskToPackage(' . $t['id'] . ')"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></button>';
            $html .= '</div>';
        }
        if (empty($tasks)) {
            $html = '<div class="text-gray-500 text-sm text-center py-4">Задачи не найдены</div>';
        }
        
        echo json_encode(['status' => 'success', 'html' => $html]);
    }
}
