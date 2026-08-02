<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Analytics_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Получить общую статистику за сегодня, эту неделю и этот месяц.
     */
    public function get_summary_stats($user_id) {
        // Сегодня
        $today = date('Y-m-d');
        // Текущая неделя (с понедельника)
        $week_start = date('Y-m-d', strtotime('monday this week'));
        // Текущий месяц
        $month_start = date('Y-m-01');

        $this->db->select("
            SUM(IF(DATE(start_time) = '{$today}', GREATEST(0, TIMESTAMPDIFF(SECOND, start_time, IFNULL(end_time, NOW())) - pause_duration), 0)) as today_seconds,
            SUM(IF(DATE(start_time) >= '{$week_start}', GREATEST(0, TIMESTAMPDIFF(SECOND, start_time, IFNULL(end_time, NOW())) - pause_duration), 0)) as week_seconds,
            SUM(IF(DATE(start_time) >= '{$month_start}', GREATEST(0, TIMESTAMPDIFF(SECOND, start_time, IFNULL(end_time, NOW())) - pause_duration), 0)) as month_seconds
        ");
        $this->db->where('user_id', $user_id);
        $this->db->where('start_time >=', $month_start . ' 00:00:00');
        $query = $this->db->get('time_sessions');
        $row = $query->row_array();

        return [
            'today' => (int)($row['today_seconds'] ?? 0),
            'week' => (int)($row['week_seconds'] ?? 0),
            'month' => (int)($row['month_seconds'] ?? 0)
        ];
    }

    /**
     * Получить статистику по дням за последние $days дней
     */
    public function get_daily_stats($user_id, $days = 14) {
        $start_date = date('Y-m-d', strtotime("-{$days} days"));

        $this->db->select("DATE(start_time) as date, SUM(GREATEST(0, TIMESTAMPDIFF(SECOND, start_time, IFNULL(end_time, NOW())) - pause_duration)) as total_seconds");
        $this->db->where('user_id', $user_id);
        $this->db->where('start_time >=', $start_date . ' 00:00:00');
        $this->db->group_by('DATE(start_time)');
        $this->db->order_by('DATE(start_time)', 'ASC');
        $query = $this->db->get('time_sessions');
        
        $result = $query->result_array();
        
        // Заполним все дни нулями на случай пропусков
        $daily = [];
        for ($i = $days; $i >= 0; $i--) {
            $daily[date('Y-m-d', strtotime("-{$i} days"))] = 0;
        }

        foreach ($result as $row) {
            if (isset($daily[$row['date']])) {
                $daily[$row['date']] = (int)$row['total_seconds'];
            }
        }

        return $daily;
    }

    /**
     * Получить статистику по проектам (заказчикам) за последние $days дней
     */
    public function get_project_stats($user_id, $days = 30) {
        $start_date = date('Y-m-d', strtotime("-{$days} days"));

        $this->db->select("
            customers.name as customer_name,
            SUM(GREATEST(0, TIMESTAMPDIFF(SECOND, time_sessions.start_time, IFNULL(time_sessions.end_time, NOW())) - time_sessions.pause_duration)) as total_seconds
        ");
        $this->db->from('time_sessions');
        $this->db->join('tasks', 'tasks.id = time_sessions.task_id');
        $this->db->join('customers', 'customers.id = tasks.customer_id', 'left');
        $this->db->where('time_sessions.user_id', $user_id);
        $this->db->where('time_sessions.start_time >=', $start_date . ' 00:00:00');
        // Обработка NULL значений в GROUP BY
        $this->db->group_by('tasks.customer_id');
        $query = $this->db->get();

        $result = $query->result_array();

        $stats = [];
        foreach ($result as $row) {
            $name = $row['customer_name'] ? $row['customer_name'] : 'Без проекта / Другое';
            
            $stats[] = [
                'name' => $name,
                'total_seconds' => (int)$row['total_seconds']
            ];
        }

        // Сортировка по убыванию времени
        usort($stats, function($a, $b) {
            return $b['total_seconds'] - $a['total_seconds'];
        });

        return $stats;
    }
}
