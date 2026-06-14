<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Task_model');
    }

    /**
     * Главная страница отчетов
     */
    public function index() {
        $user_id = $this->session->userdata('user_id');

        // Получаем даты из GET или ставим текущий месяц по умолчанию
        $start_date = $this->input->get('start');
        $end_date = $this->input->get('end');

        if (empty($start_date) || empty($end_date)) {
            // По умолчанию - с первого дня текущего месяца по сегодняшний день
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d');
        }

        // Запрашиваем данные у модели
        $raw_report = $this->Task_model->get_time_report_grouped($user_id, $start_date, $end_date);
        $archived_projects = $this->Task_model->get_archived_projects($user_id);

        // Формируем красивые данные для отображения с группировкой по дням
        $grouped_data = [];
        $total_seconds_period = 0;
        $total_earned_period = 0.0;
        $seen_fixed_tasks = []; // Чтобы не считать фикс-задачи дважды

        foreach ($raw_report as $row) {
            $seconds = (int)$row['total_seconds'];
            $total_seconds_period += $seconds;

            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            
            $date_key = $row['report_date']; // например '2026-06-03'
            
            if (!isset($grouped_data[$date_key])) {
                $grouped_data[$date_key] = [
                    'date_formatted' => date('d.m.Y', strtotime($date_key)),
                    'tasks' => []
                ];
            }
            
            // Расчет заработка
            $earned = 0.0;
            $is_fixed = (bool)$row['is_fixed_price'];
            $price = (float)$row['price'];
            
            if ($is_fixed) {
                if (!in_array($row['id'], $seen_fixed_tasks)) {
                    $earned = $price;
                    $total_earned_period += $earned;
                    $seen_fixed_tasks[] = $row['id'];
                }
            } else {
                $earned = ($seconds / 3600) * $price;
                $total_earned_period += $earned;
            }

            $grouped_data[$date_key]['tasks'][] = [
                'task_id' => $row['id'],
                'title' => $row['title'],
                'parent_id' => $row['parent_id'],
                'parent_title' => $row['parent_title'],
                'color' => $row['color'],
                'customer_name' => $row['customer_name'],
                'is_fixed' => $is_fixed,
                'price' => $price,
                'earned' => $earned,
                'duration_formatted' => sprintf(lang('time_format_hours_mins'), $hours, $minutes)
            ];
        }

        // Форматируем архивные проекты
        $archive_data = [];
        $total_archive_seconds = 0;
        foreach ($archived_projects as $proj) {
            $sec = (int)$proj['total_seconds'];
            $total_archive_seconds += $sec;
            $h = floor($sec / 3600);
            $m = floor(($sec % 3600) / 60);
            
            $archive_data[] = [
                'id' => $proj['id'],
                'title' => $proj['title'],
                'color' => $proj['color'],
                'date_completed' => date('d.m.Y', strtotime($proj['created_at'])), // Или дата обновления/последней активности
                'duration_formatted' => sprintf(lang('time_format_hours_mins'), $h, $m)
            ];
        }

        // Форматируем общее время за период
        $total_hours = floor($total_seconds_period / 3600);
        $total_minutes = floor(($total_seconds_period % 3600) / 60);
        $total_time_formatted = sprintf(lang('time_format_hours_mins'), $total_hours, $total_minutes);
        
        $total_archive_h = floor($total_archive_seconds / 3600);
        $total_archive_m = floor(($total_archive_seconds % 3600) / 60);

        // Формируем результирующий массив данных для отображения во вьюшке.
        // Добавляем параметр left_sidebar_view со значением 'sidebars/statistics',
        // чтобы подключить левое меню навигации и стандартизировать интерфейс страницы.
        $data = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'grouped_data' => $grouped_data,
            'archive_data' => $archive_data,
            'total_time_formatted' => $total_time_formatted,
            'total_archive_formatted' => sprintf(lang('time_format_hours_mins'), $total_archive_h, $total_archive_m),
            'total_earned' => $total_earned_period,
            'left_sidebar_view' => 'sidebars/statistics' // Подключение левой панели статистики и навигации
        ];

        // Рендерим через каноничный сборщик, передавая подготовленный массив данных
        $this->render_page('reports', $data);
    }
}
