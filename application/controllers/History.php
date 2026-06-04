<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Контроллер для вывода глобального журнала активности.
 */
class History extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Task_model');
    }

    public function index() {
        $user_id = $this->session->userdata('user_id');

        // Получаем все завершенные сессии
        $sessions = $this->Task_model->get_global_history($user_id);

        // Форматируем данные для вывода
        foreach ($sessions as &$s) {
            $s['start_formatted'] = date('d.m.Y H:i', strtotime($s['start_time']));
            $s['end_formatted'] = date('H:i', strtotime($s['end_time'])); // Только время для компактности, если тот же день
            
            $diff = $s['duration_seconds'];
            $s['duration_formatted'] = sprintf(lang('time_format_hours_mins'), floor($diff / 3600), floor(($diff % 3600) / 60));
            
            // Защита от XSS
            $s['note_safe'] = !empty($s['note']) ? htmlspecialchars($s['note']) : '';
        }

        $data = [
            'sessions' => $sessions
        ];

        $this->render_page('history', $data);
    }
}
