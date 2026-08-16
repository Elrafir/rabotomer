<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Controller for Offline-First 2-Way Synchronization
 */
class Api_sync extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->_handle_cors();
    }

    /**
     * Send CORS headers and handle OPTIONS preflight
     */
    private function _handle_cors() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Client-Version');
        header('Access-Control-Max-Age: 86400');

        if ($this->input->method(TRUE) === 'OPTIONS') {
            $this->output->set_status_header(200)->_display();
            exit();
        }
    }

    /**
     * Response helper
     */
    private function _json_response($data, $status = 200) {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * Milliseconds Unix Timestamp (UTC)
     */
    private function _now_ms() {
        return (int) round(microtime(true) * 1000);
    }

    /**
     * Convert timestamp or string into MySQL DATETIME string (UTC)
     */
    private function _to_datetime($val) {
        if (empty($val)) return null;
        if (is_numeric($val)) {
            // Milliseconds or seconds
            $sec = ($val > 10000000000) ? (int)($val / 1000) : (int)$val;
            return gmdate('Y-m-d H:i:s', $sec);
        }
        $ts = strtotime($val);
        return ($ts !== false) ? gmdate('Y-m-d H:i:s', $ts) : null;
    }

    /**
     * Convert MySQL DATETIME to milliseconds timestamp
     */
    private function _to_ms($datetime_str) {
        if (empty($datetime_str)) return null;
        $ts = strtotime($datetime_str . ' UTC');
        return ($ts !== false) ? ($ts * 1000) : null;
    }

    /**
     * GET /api/sync/status
     * Quick health check / subnet beacon endpoint
     */
    public function status() {
        $this->load->config('app_version', TRUE, TRUE);
        $version = $this->config->item('app_version', 'app_version') ?? '1.1.2';

        $this->_json_response([
            'status' => 'online',
            'server_name' => 'Работомер Сервер',
            'app_version' => $version,
            'server_time_ms' => $this->_now_ms(),
            'server_time_iso' => gmdate('Y-m-d\TH:i:s\Z'),
            'features' => ['offline_sync', 'multi_user', 'uuid_v4']
        ]);
    }

    /**
     * GET/POST /api/sync/bootstrap
     * Full database dump for initial device onboarding
     */
    public function bootstrap() {
        $server_now = $this->_now_ms();

        // 1. Users
        $users = $this->db->select('id, uuid, username, email, first_name, last_name, group_id, user_theme, user_bg_theme, user_card_theme, user_text_theme, created_at, updated_at, deleted_at')
            ->from('users')
            ->where('deleted_at IS NULL')
            ->get()->result_array();

        // 2. Customers
        $customers = $this->db->select('id, uuid, user_uuid, name, notes, default_price, default_prepayment, default_payment_type, created_at, updated_at, deleted_at')
            ->from('customers')
            ->get()->result_array();

        // 3. Tasks
        $tasks = $this->db->select('id, uuid, user_uuid, customer_uuid, parent_uuid, title, description, status, color, is_fixed_price, price, created_at, updated_at, deleted_at')
            ->from('tasks')
            ->get()->result_array();

        // 4. Time Sessions
        $sessions = $this->db->select('id, uuid, user_uuid, task_uuid, start_time, end_time, note, is_paused, pause_duration, last_paused_at, last_heartbeat, updated_at, deleted_at')
            ->from('time_sessions')
            ->get()->result_array();

        $this->_json_response([
            'success' => true,
            'server_time_ms' => $server_now,
            'counts' => [
                'users' => count($users),
                'customers' => count($customers),
                'tasks' => count($tasks),
                'time_sessions' => count($sessions)
            ],
            'data' => [
                'users' => $users,
                'customers' => $customers,
                'tasks' => $tasks,
                'time_sessions' => $sessions
            ]
        ]);
    }

    /**
     * POST /api/sync/pull
     * Incremental sync: returns changes since given timestamp
     */
    public function pull() {
        $raw = $this->input->raw_input_stream;
        $params = json_decode($raw, true) ?: $this->input->post() ?: $this->input->get();

        $since_ms = isset($params['since']) ? (int)$params['since'] : 0;
        $user_uuid = isset($params['user_uuid']) ? trim($params['user_uuid']) : null;
        $since_dt = $this->_to_datetime($since_ms);

        $server_now = $this->_now_ms();

        // Users
        $this->db->select('id, uuid, username, email, first_name, last_name, group_id, user_theme, user_bg_theme, user_card_theme, user_text_theme, created_at, updated_at, deleted_at')
            ->from('users');
        if ($since_dt) {
            $this->db->where("updated_at >= '$since_dt' OR deleted_at >= '$since_dt'");
        }
        $users = $this->db->get()->result_array();

        // Customers
        $this->db->select('id, uuid, user_uuid, name, notes, default_price, default_prepayment, default_payment_type, created_at, updated_at, deleted_at')
            ->from('customers');
        if ($since_dt) {
            $this->db->where("updated_at >= '$since_dt' OR deleted_at >= '$since_dt'");
        }
        $customers = $this->db->get()->result_array();

        // Tasks
        $this->db->select('id, uuid, user_uuid, customer_uuid, parent_uuid, title, description, status, color, is_fixed_price, price, created_at, updated_at, deleted_at')
            ->from('tasks');
        if ($since_dt) {
            $this->db->where("updated_at >= '$since_dt' OR deleted_at >= '$since_dt'");
        }
        $tasks = $this->db->get()->result_array();

        // Time Sessions
        $this->db->select('id, uuid, user_uuid, task_uuid, start_time, end_time, note, is_paused, pause_duration, last_paused_at, last_heartbeat, updated_at, deleted_at')
            ->from('time_sessions');
        if ($since_dt) {
            $this->db->where("updated_at >= '$since_dt' OR deleted_at >= '$since_dt'");
        }
        $sessions = $this->db->get()->result_array();

        $this->_json_response([
            'success' => true,
            'server_time_ms' => $server_now,
            'since_ms' => $since_ms,
            'data' => [
                'users' => $users,
                'customers' => $customers,
                'tasks' => $tasks,
                'time_sessions' => $sessions
            ]
        ]);
    }

    /**
     * POST /api/sync/push
     * Batch upsert from offline mobile clients
     */
    public function push() {
        $raw = $this->input->raw_input_stream;
        $payload = json_decode($raw, true);

        if (!$payload || !is_array($payload)) {
            $this->_json_response(['success' => false, 'error' => 'Invalid JSON payload'], 400);
            return;
        }

        $processed = [
            'customers' => 0,
            'tasks' => 0,
            'time_sessions' => 0
        ];
        $conflicts = [];

        // 1. Process Customers
        if (!empty($payload['customers']) && is_array($payload['customers'])) {
            foreach ($payload['customers'] as $c) {
                if (empty($c['uuid'])) continue;

                // Resolve user_id from user_uuid
                $user_id = 1;
                if (!empty($c['user_uuid'])) {
                    $u = $this->db->select('id')->from('users')->where('uuid', $c['user_uuid'])->get()->row();
                    if ($u) $user_id = $u->id;
                }

                $existing = $this->db->from('customers')->where('uuid', $c['uuid'])->get()->row();

                $data = [
                    'uuid' => $c['uuid'],
                    'user_id' => $user_id,
                    'user_uuid' => $c['user_uuid'] ?? null,
                    'name' => $c['name'] ?? 'Без названия',
                    'notes' => $c['notes'] ?? null,
                    'default_price' => $c['default_price'] ?? 0.00,
                    'default_prepayment' => $c['default_prepayment'] ?? 0.00,
                    'default_payment_type' => $c['default_payment_type'] ?? 'hourly',
                    'updated_at' => $this->_to_datetime($c['updated_at'] ?? null) ?: date('Y-m-d H:i:s'),
                    'deleted_at' => $this->_to_datetime($c['deleted_at'] ?? null)
                ];

                if ($existing) {
                    $this->db->where('uuid', $c['uuid'])->update('customers', $data);
                } else {
                    if (!empty($c['created_at'])) {
                        $data['created_at'] = $this->_to_datetime($c['created_at']);
                    }
                    $this->db->insert('customers', $data);
                }
                $processed['customers']++;
            }
        }

        // 2. Process Tasks
        if (!empty($payload['tasks']) && is_array($payload['tasks'])) {
            foreach ($payload['tasks'] as $t) {
                if (empty($t['uuid'])) continue;

                // Resolve foreign IDs
                $user_id = 1;
                if (!empty($t['user_uuid'])) {
                    $u = $this->db->select('id')->from('users')->where('uuid', $t['user_uuid'])->get()->row();
                    if ($u) $user_id = $u->id;
                }

                $customer_id = null;
                if (!empty($t['customer_uuid'])) {
                    $cust = $this->db->select('id')->from('customers')->where('uuid', $t['customer_uuid'])->get()->row();
                    if ($cust) $customer_id = $cust->id;
                }

                $parent_id = null;
                if (!empty($t['parent_uuid'])) {
                    $p = $this->db->select('id')->from('tasks')->where('uuid', $t['parent_uuid'])->get()->row();
                    if ($p) $parent_id = $p->id;
                }

                $existing = $this->db->from('tasks')->where('uuid', $t['uuid'])->get()->row();

                $data = [
                    'uuid' => $t['uuid'],
                    'user_id' => $user_id,
                    'user_uuid' => $t['user_uuid'] ?? null,
                    'customer_id' => $customer_id,
                    'customer_uuid' => $t['customer_uuid'] ?? null,
                    'parent_id' => $parent_id,
                    'parent_uuid' => $t['parent_uuid'] ?? null,
                    'title' => $t['title'] ?? 'Новая задача',
                    'description' => $t['description'] ?? null,
                    'status' => in_array($t['status'] ?? '', ['active', 'completed']) ? $t['status'] : 'active',
                    'color' => $t['color'] ?? null,
                    'is_fixed_price' => !empty($t['is_fixed_price']) ? 1 : 0,
                    'price' => $t['price'] ?? 0.00,
                    'updated_at' => $this->_to_datetime($t['updated_at'] ?? null) ?: date('Y-m-d H:i:s'),
                    'deleted_at' => $this->_to_datetime($t['deleted_at'] ?? null)
                ];

                if ($existing) {
                    $this->db->where('uuid', $t['uuid'])->update('tasks', $data);
                } else {
                    if (!empty($t['created_at'])) {
                        $data['created_at'] = $this->_to_datetime($t['created_at']);
                    }
                    $this->db->insert('tasks', $data);
                }
                $processed['tasks']++;
            }
        }

        // 3. Process Time Sessions (Additive / No overwrite of un-synced logs)
        if (!empty($payload['time_sessions']) && is_array($payload['time_sessions'])) {
            foreach ($payload['time_sessions'] as $s) {
                if (empty($s['uuid'])) continue;

                $user_id = 1;
                if (!empty($s['user_uuid'])) {
                    $u = $this->db->select('id')->from('users')->where('uuid', $s['user_uuid'])->get()->row();
                    if ($u) $user_id = $u->id;
                }

                $task_id = null;
                if (!empty($s['task_uuid'])) {
                    $tsk = $this->db->select('id')->from('tasks')->where('uuid', $s['task_uuid'])->get()->row();
                    if ($tsk) $task_id = $tsk->id;
                }

                if (!$task_id) {
                    // Task might not have been pushed yet or is missing
                    continue;
                }

                $existing = $this->db->from('time_sessions')->where('uuid', $s['uuid'])->get()->row();

                $data = [
                    'uuid' => $s['uuid'],
                    'user_id' => $user_id,
                    'user_uuid' => $s['user_uuid'] ?? null,
                    'task_id' => $task_id,
                    'task_uuid' => $s['task_uuid'],
                    'start_time' => $this->_to_datetime($s['start_time']),
                    'end_time' => $this->_to_datetime($s['end_time'] ?? null),
                    'note' => $s['note'] ?? null,
                    'is_paused' => !empty($s['is_paused']) ? 1 : 0,
                    'last_paused_at' => $this->_to_datetime($s['last_paused_at'] ?? null),
                    'pause_duration' => (int)($s['pause_duration'] ?? 0),
                    'last_heartbeat' => $this->_to_datetime($s['last_heartbeat'] ?? null),
                    'updated_at' => $this->_to_datetime($s['updated_at'] ?? null) ?: date('Y-m-d H:i:s'),
                    'deleted_at' => $this->_to_datetime($s['deleted_at'] ?? null)
                ];

                if ($existing) {
                    $this->db->where('uuid', $s['uuid'])->update('time_sessions', $data);
                } else {
                    $this->db->insert('time_sessions', $data);
                }
                $processed['time_sessions']++;
            }
        }

        $this->_json_response([
            'success' => true,
            'server_time_ms' => $this->_now_ms(),
            'processed' => $processed,
            'conflicts' => $conflicts
        ]);
    }
}
