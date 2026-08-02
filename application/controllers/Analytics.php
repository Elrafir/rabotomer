<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Analytics extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Analytics_model');
    }

    /**
     * Отображение страницы аналитики
     */
    public function index() {
        $data = [
            'title' => 'Аналитика - Работомер',
            'left_sidebar_view' => 'sidebars/statistics',
            'active_sub_page' => 'analytics',
            'custom_js' => [
                'assets/js/chart.umd.js',
                'assets/js/analytics.js',
                'assets/js/timeline.js'
            ]
        ];

        // Рендерим страницу
        $this->render_page('analytics', $data);
    }

    /**
     * AJAX-метод получения данных для графиков
     */
    public function get_data_ajax() {
        $user_id = $this->session->userdata('user_id');
        
        $summary = $this->Analytics_model->get_summary_stats($user_id);
        $daily = $this->Analytics_model->get_daily_stats($user_id, 14);
        $projects = $this->Analytics_model->get_project_stats($user_id, 30);

        echo json_encode([
            'status' => 'success',
            'summary' => $summary,
            'daily' => $daily,
            'projects' => $projects
        ]);
    }
}
