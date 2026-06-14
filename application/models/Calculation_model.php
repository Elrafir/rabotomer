<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Calculation_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        
        // Auto-migration for calculation_packages
        if (!$this->db->table_exists('calculation_packages')) {
            $this->db->query("
                CREATE TABLE calculation_packages (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    notes TEXT NULL,
                    total_sum DECIMAL(10,2) DEFAULT 0.00,
                    calendar_time DECIMAL(10,2) DEFAULT 0,
                    calendar_time_type VARCHAR(20) DEFAULT 'days',
                    status VARCHAR(50) DEFAULT 'draft',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_calc_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } else {
            if (!$this->db->field_exists('calendar_time', 'calculation_packages')) {
                $this->db->query("ALTER TABLE calculation_packages CHANGE calendar_days calendar_time DECIMAL(10,2) DEFAULT 0");
                $this->db->query("ALTER TABLE calculation_packages ADD COLUMN calendar_time_type VARCHAR(20) DEFAULT 'days'");
                $this->db->query("ALTER TABLE calculation_packages ADD COLUMN status VARCHAR(50) DEFAULT 'draft'");
            }
        }

        // Auto-migration for calculation_package_tasks
        if (!$this->db->table_exists('calculation_package_tasks')) {
            $this->db->query("
                CREATE TABLE calculation_package_tasks (
                    package_id INT UNSIGNED NOT NULL,
                    task_id INT UNSIGNED NOT NULL,
                    PRIMARY KEY (package_id, task_id),
                    CONSTRAINT fk_calc_pkg_id FOREIGN KEY (package_id) REFERENCES calculation_packages (id) ON DELETE CASCADE,
                    CONSTRAINT fk_calc_task_id FOREIGN KEY (task_id) REFERENCES tasks (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }
    }

    public function get_packages($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get('calculation_packages')->result_array();
    }

    public function get_package($id, $user_id) {
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        return $this->db->get('calculation_packages')->row_array();
    }

    public function create_package($user_id, $title, $notes, $total_sum, $calendar_time, $calendar_time_type, $status) {
        $data = [
            'user_id' => $user_id,
            'title' => $title,
            'notes' => $notes,
            'total_sum' => $total_sum,
            'calendar_time' => $calendar_time,
            'calendar_time_type' => $calendar_time_type,
            'status' => $status
        ];
        $this->db->insert('calculation_packages', $data);
        return $this->db->insert_id();
    }

    public function update_package($id, $user_id, $title, $notes, $total_sum, $calendar_time, $calendar_time_type, $status) {
        $data = [
            'title' => $title,
            'notes' => $notes,
            'total_sum' => $total_sum,
            'calendar_time' => $calendar_time,
            'calendar_time_type' => $calendar_time_type,
            'status' => $status
        ];
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        return $this->db->update('calculation_packages', $data);
    }

    public function delete_package($id, $user_id) {
        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete('calculation_packages');
    }

    public function get_package_tasks($package_id) {
        $this->db->select('tasks.*, customers.name as customer_name');
        $this->db->from('calculation_package_tasks');
        $this->db->join('tasks', 'tasks.id = calculation_package_tasks.task_id');
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        $this->db->where('calculation_package_tasks.package_id', $package_id);
        return $this->db->get()->result_array();
    }

    public function add_task_to_package($package_id, $task_id) {
        // check if already exists
        $this->db->where('package_id', $package_id);
        $this->db->where('task_id', $task_id);
        $exists = $this->db->get('calculation_package_tasks')->row_array();
        if (!$exists) {
            $this->db->insert('calculation_package_tasks', [
                'package_id' => $package_id,
                'task_id' => $task_id
            ]);
            return true;
        }
        return false;
    }

    public function remove_task_from_package($package_id, $task_id) {
        $this->db->where('package_id', $package_id);
        $this->db->where('task_id', $task_id);
        return $this->db->delete('calculation_package_tasks');
    }

    public function get_tasks_for_period($user_id, $start_date, $end_date) {
        // Find tasks that have time sessions in this period
        // OR were created in this period
        $this->db->select('tasks.*, customers.name as customer_name');
        $this->db->from('tasks');
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        $this->db->where('tasks.user_id', $user_id);
        
        if (!empty($start_date) && !empty($end_date)) {
            $start = $start_date . ' 00:00:00';
            $end = $end_date . ' 23:59:59';
            
            // Subquery to find tasks with sessions in range
            $this->db->group_start();
            $this->db->where("tasks.created_at >=", $start);
            $this->db->where("tasks.created_at <=", $end);
            $this->db->or_where("tasks.id IN (SELECT task_id FROM time_sessions WHERE start_time >= '$start' AND start_time <= '$end')", NULL, FALSE);
            $this->db->group_end();
        }
        
        $this->db->order_by('tasks.created_at', 'DESC');
        return $this->db->get()->result_array();
    }
    
    public function get_package_statistics($package_id) {
        // get all tasks in package
        $tasks = $this->get_package_tasks($package_id);
        if (empty($tasks)) {
            return [
                'total_time_seconds' => 0,
                'tasks_count' => 0,
                'tasks_data' => []
            ];
        }
        
        $task_ids = array_column($tasks, 'id');
        
        // get time_sessions for these tasks
        $this->db->select('task_id, SUM(TIMESTAMPDIFF(SECOND, start_time, IFNULL(end_time, NOW())) - pause_duration) as total_seconds');
        $this->db->from('time_sessions');
        $this->db->where_in('task_id', $task_ids);
        $this->db->group_by('task_id');
        $sessions = $this->db->get()->result_array();
        
        $time_by_task = [];
        $total_seconds = 0;
        foreach ($sessions as $s) {
            $sec = max(0, (int)$s['total_seconds']);
            $time_by_task[$s['task_id']] = $sec;
            $total_seconds += $sec;
        }
        
        $tasks_data = [];
        foreach ($tasks as $t) {
            $t['tracked_seconds'] = $time_by_task[$t['id']] ?? 0;
            $tasks_data[] = $t;
        }
        
        return [
            'total_time_seconds' => $total_seconds,
            'tasks_count' => count($tasks),
            'tasks_data' => $tasks_data
        ];
    }
}
