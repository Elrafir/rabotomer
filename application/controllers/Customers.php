<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Контроллер управления заказчиками и связанными техническими заданиями (ТЗ)
 */
class Customers extends MY_Controller {

    public function __construct() {
        parent::__construct();
        // Загрузка модели заказчиков и хелпера
        $this->load->model('Customer_model');
        $this->load->helper('spec');
    }

    public function index($active_customer_id = null) {
        $user_id = $this->session->userdata('user_id');

        $this->load->model('Settings_model');
        $per_page = (int)$this->Settings_model->get_setting('per_page', 25);

        // Получаем первую порцию заказчиков
        $data['customers'] = $this->Customer_model->get_all($user_id, $per_page, 0);
        $data['per_page'] = $per_page;

        // Если активный заказчик не передан, выбираем первого из списка для удобства
        if ($active_customer_id === null) {
            $all_customers = $this->Customer_model->get_all($user_id, 1, 0);
            if (!empty($all_customers)) {
                $active_customer_id = $all_customers[0]['id'];
            }
        }

        // Если активный заказчик не входит в первую страницу, добавим его, чтобы он отображался в списке
        if ($active_customer_id !== null) {
            $found = false;
            foreach ($data['customers'] as $c) {
                if ($c['id'] == $active_customer_id) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $active_cust = $this->Customer_model->get_by_id($active_customer_id, $user_id);
                if ($active_cust) {
                    $data['customers'][] = $active_cust;
                    // Сортируем список заказчиков по алфавиту
                    usort($data['customers'], function($a, $b) {
                        return strcasecmp($a['name'], $b['name']);
                    });
                }
            }
        }

        $data['active_customer_id'] = $active_customer_id;
        $data['active_customer'] = null;
        $data['customer_tasks_tree'] = [];
        $data['customer_tasks'] = [];
        $data['specs'] = [];

        if ($active_customer_id !== null) {
            // Загружаем подробные данные выбранного заказчика
            $data['active_customer'] = $this->Customer_model->get_by_id($active_customer_id, $user_id);
            
            if ($data['active_customer']) {
                // Получаем все ТЗ для заказчика и файлы к каждому ТЗ
                $specs = $this->Customer_model->get_specs($active_customer_id);
                foreach ($specs as &$spec) {
                    $spec['files'] = $this->Customer_model->get_spec_files($spec['id']);
                    // Получаем ID привязанных задач для использования в шаблоне
                    $this->db->select('id');
                    $this->db->where('spec_id', $spec['id']);
                    $this->db->where('customer_id', $active_customer_id);
                    $this->db->where('user_id', $user_id);
                    $this->db->where('deleted_at IS NULL');
                    $linked_tasks_res = $this->db->get('tasks')->result_array();
                    $spec['linked_task_ids'] = array_column($linked_tasks_res, 'id');
                }
                $data['specs'] = $specs;

                // Получаем плоский список задач заказчика (для привязки к ТЗ)
                $data['customer_tasks'] = $this->Customer_model->get_customer_tasks($active_customer_id, $user_id);

                // Загружаем модель задач для формирования дерева задач заказчика
                $this->load->model('Task_model');
                $raw_tasks = $this->Task_model->get_user_tasks($user_id);
                
                // Строим иерархическое дерево задач заказчика
                $data['customer_tasks_tree'] = $this->_build_customer_tasks_tree($raw_tasks, $active_customer_id);
            }
        }

        // Рендерим страницу с передачей всех данных
        $this->render_page('customers', $data);
    }

    /**
     * Создание нового заказчика через модальную форму с финансовыми дефолтами
     */
    public function add() {
        $user_id = $this->session->userdata('user_id');
        $name = trim($this->input->post('name') ?? '');
        $notes = trim($this->input->post('notes') ?? '');
        $default_price = $this->input->post('default_price') ?: 0.00;
        $default_prepayment = $this->input->post('default_prepayment') ?: 0.00;
        $default_payment_type = $this->input->post('default_payment_type') ?: 'hourly';

        if (!empty($name)) {
            $id = $this->Customer_model->add($user_id, $name, $notes, $default_price, $default_prepayment, $default_payment_type);
            // Перенаправляем на страницу вновь созданного заказчика
            redirect('customers/index/' . $id);
        } else {
            redirect('customers');
        }
    }

    /**
     * Редактирование существующего заказчика
     */
    public function edit() {
        $user_id = $this->session->userdata('user_id');
        $id = $this->input->post('id');
        $name = trim($this->input->post('name') ?? '');
        $notes = trim($this->input->post('notes') ?? '');
        $default_price = $this->input->post('default_price') ?: 0.00;
        $default_prepayment = $this->input->post('default_prepayment') ?: 0.00;
        $default_payment_type = $this->input->post('default_payment_type') ?: 'hourly';

        if (!empty($id) && !empty($name)) {
            $this->Customer_model->update($id, $user_id, $name, $notes, $default_price, $default_prepayment, $default_payment_type);
            redirect('customers/index/' . $id);
        } else {
            redirect('customers');
        }
    }

    /**
     * Удаление заказчика
     * 
     * @param int $id ID удаляемого заказчика
     */
    public function delete($id) {
        $user_id = $this->session->userdata('user_id');
        
        // Подгружаем ТЗ заказчика, чтобы удалить связанные с ними файлы
        $specs = $this->Customer_model->get_specs($id);
        foreach ($specs as $spec) {
            $files = $this->Customer_model->get_spec_files($spec['id']);
            foreach ($files as $file) {
                $filepath = FCPATH . 'uploads/specs/' . $file['filename'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
        }

        // Удаляем заказчика из БД (каскадно удалятся ТЗ и файлы по внешним ключам)
        $this->Customer_model->delete($id, $user_id);
        redirect('customers');
    }

    // =========================================================================
    // CRUD РАБОТА С ТЕХНИЧЕСКИМИ ЗАДАНИЯМИ (ТЗ)
    // =========================================================================

    /**
     * Создание нового ТЗ для заказчика и привязка выбранных задач
     */
    public function add_spec() {
        $user_id = $this->session->userdata('user_id');
        $customer_id = $this->input->post('customer_id');
        $title = trim($this->input->post('title') ?? '');
        $content = $this->input->post('content'); // Содержит HTML текст из редактора
        $price = $this->input->post('price') ?: 0.00;
        $prepayment = $this->input->post('prepayment') ?: 0.00;
        $payment_type = $this->input->post('payment_type') ?: 'hourly';
        $linked_tasks = $this->input->post('linked_tasks'); // Массив ID привязываемых задач

        if (!empty($customer_id) && !empty($title)) {
            // Сохраняем ТЗ в базе
            $spec_id = $this->Customer_model->add_spec($customer_id, $title, $content, $price, $prepayment, $payment_type);

            // Обрабатываем привязку задач
            if (!empty($linked_tasks) && is_array($linked_tasks)) {
                $this->db->where_in('id', $linked_tasks);
                $this->db->where('customer_id', $customer_id);
                $this->db->where('user_id', $user_id);
                $this->db->update('tasks', ['spec_id' => $spec_id]);
            }
        }

        redirect('customers/index/' . $customer_id);
    }

    /**
     * Редактирование ТЗ заказчика и обновление привязки задач
     */
    public function edit_spec() {
        $user_id = $this->session->userdata('user_id');
        $customer_id = $this->input->post('customer_id');
        $spec_id = $this->input->post('spec_id');
        $title = trim($this->input->post('title') ?? '');
        $content = $this->input->post('content');
        $price = $this->input->post('price') ?: 0.00;
        $prepayment = $this->input->post('prepayment') ?: 0.00;
        $payment_type = $this->input->post('payment_type') ?: 'hourly';
        $linked_tasks = $this->input->post('linked_tasks'); // Массив ID привязанных задач

        if (!empty($spec_id) && !empty($title)) {
            $this->Customer_model->update_spec($spec_id, $title, $content, $price, $prepayment, $payment_type);

            // Отвязываем все задачи от этого ТЗ, чтобы связать заново
            $this->db->where('spec_id', $spec_id);
            $this->db->where('customer_id', $customer_id);
            $this->db->where('user_id', $user_id);
            $this->db->update('tasks', ['spec_id' => NULL]);

            // Привязываем выбранные задачи к этому ТЗ
            if (!empty($linked_tasks) && is_array($linked_tasks)) {
                $this->db->where_in('id', $linked_tasks);
                $this->db->where('customer_id', $customer_id);
                $this->db->where('user_id', $user_id);
                $this->db->update('tasks', ['spec_id' => $spec_id]);
            }
        }

        redirect('customers/index/' . $customer_id);
    }

    /**
     * Удаление ТЗ и связанных с ним файлов
     * 
     * @param int $spec_id ID удаляемого ТЗ
     */
    public function delete_spec($spec_id) {
        $spec = $this->Customer_model->get_spec($spec_id);
        if ($spec) {
            $customer_id = $spec['customer_id'];
            
            // Физически удаляем файлы ТЗ
            $files = $this->Customer_model->get_spec_files($spec_id);
            foreach ($files as $file) {
                $filepath = FCPATH . 'uploads/specs/' . $file['filename'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
            
            // Удаляем запись в бд
            $this->Customer_model->delete_spec($spec_id);
            redirect('customers/index/' . $customer_id);
        } else {
            redirect('customers');
        }
    }

    // =========================================================================
    // ЗАГРУЗКА И СКАЧИВАНИЕ ФАЙЛОВ ДЛЯ ТЗ
    // =========================================================================

    /**
     * Загрузка файла для конкретного ТЗ через AJAX
     */
    public function upload_file() {
        $spec_id = $this->input->post('spec_id');
        $spec = $this->Customer_model->get_spec($spec_id);
        
        if (!$spec) {
            echo json_encode(['status' => 'error', 'message' => 'ТЗ не найдено']);
            return;
        }

        $upload_dir = FCPATH . 'uploads/specs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Параметры библиотеки загрузки CodeIgniter
        $config['upload_path']   = $upload_dir;
        $config['allowed_types'] = 'gif|jpg|png|jpeg|pdf|doc|docx|xls|xlsx|txt|zip|rar|7z';
        $config['max_size']      = 10240; // Максимально 10MB
        $config['encrypt_name']  = TRUE;  // Хешируем имена для надежности

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            echo json_encode(['status' => 'error', 'message' => $this->upload->display_errors('', '')]);
        } else {
            $upload_data = $this->upload->data();
            $filename = $upload_data['file_name'];
            $orig_name = $upload_data['client_name'];
            $file_size = $upload_data['file_size'] * 1024; // в байты

            // Сохраняем информацию в таблице
            $file_id = $this->Customer_model->add_spec_file($spec_id, $filename, $orig_name, $file_size);
            
            echo json_encode([
                'status' => 'success', 
                'file_id' => $file_id, 
                'orig_name' => $orig_name,
                'download_url' => site_url('customers/download_file/' . $file_id),
                'icon' => get_file_icon_emoji($orig_name, 0)
            ]);
        }
    }

    /**
     * Скачивание прикрепленного файла ТЗ
     * 
     * @param int $file_id ID скачиваемого файла
     */
    public function download_file($file_id) {
        $file = $this->Customer_model->get_spec_file($file_id);
        if ($file) {
            $filepath = FCPATH . 'uploads/specs/' . $file['filename'];
            if (file_exists($filepath)) {
                $this->load->helper('download');
                force_download($file['orig_name'], file_get_contents($filepath));
                return;
            }
        }
        show_404();
    }

    /**
     * Удаление файла ТЗ через AJAX
     * 
     * @param int $file_id ID удаляемого файла
     */
    public function delete_file($file_id) {
        $file = $this->Customer_model->get_spec_file($file_id);
        if ($file) {
            // Удаляем физический файл только если это не внешняя ссылка
            if ($file['is_link'] == 0) {
                $filepath = FCPATH . 'uploads/specs/' . $file['filename'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
            $this->Customer_model->delete_spec_file($file_id);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Файл не найден']);
        }
    }

    /**
     * AJAX-метод добавления внешней ссылки в качестве вложения ТЗ
     */
    public function add_link_ajax() {
        $spec_id = $this->input->post('spec_id');
        $url = trim($this->input->post('url') ?? '');
        $title = trim($this->input->post('title') ?? '');

        $spec = $this->Customer_model->get_spec($spec_id);
        if (!$spec) {
            echo json_encode(['status' => 'error', 'message' => 'ТЗ не найдено']);
            return;
        }

        if (empty($url) || filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            echo json_encode(['status' => 'error', 'message' => 'Неверный формат ссылки']);
            return;
        }

        if (empty($title)) {
            $title = parse_url($url, PHP_URL_HOST) ?: $url;
        }

        // Сохраняем ссылку в базу (is_link = 1, размер = 0, filename = URL)
        $file_id = $this->Customer_model->add_spec_file($spec_id, $url, $title, 0, 1);

        echo json_encode([
            'status' => 'success',
            'file_id' => $file_id,
            'orig_name' => $title,
            'url' => $url,
            'icon' => get_file_icon_emoji($url, 1)
        ]);
    }

    /**
     * AJAX-метод скачивания файла из интернета и сохранения его в ТЗ
     */
    public function download_from_url_ajax() {
        $spec_id = $this->input->post('spec_id');
        $url = trim($this->input->post('url') ?? '');
        
        $spec = $this->Customer_model->get_spec($spec_id);
        if (!$spec) {
            echo json_encode(['status' => 'error', 'message' => 'ТЗ не найдено']);
            return;
        }

        if (empty($url) || filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            echo json_encode(['status' => 'error', 'message' => 'Неверный формат ссылки']);
            return;
        }

        // Пытаемся скачать файл
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $file_content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        if (curl_errno($ch) || $http_code != 200 || !$file_content) {
            $err = curl_error($ch) ?: 'HTTP Code: ' . $http_code;
            curl_close($ch);
            echo json_encode(['status' => 'error', 'message' => 'Не удалось скачать файл: ' . $err]);
            return;
        }
        curl_close($ch);

        // Определяем оригинальное имя файла
        $path_parts = pathinfo(parse_url($url, PHP_URL_PATH));
        $orig_name = !empty($path_parts['basename']) ? urldecode($path_parts['basename']) : 'downloaded_file';
        
        // Добавляем расширение по content-type, если оригинальное имя не имеет расширения
        if (empty($path_parts['extension'])) {
            $mime_map = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
                'text/plain' => 'txt',
                'application/zip' => 'zip',
                'application/x-rar-compressed' => 'rar'
            ];
            $ext = isset($mime_map[$content_type]) ? $mime_map[$content_type] : 'bin';
            $orig_name .= '.' . $ext;
        }

        // Защита и хеширование имени файла на сервере
        $encrypted_name = md5(uniqid(mt_rand(), true)) . '.' . pathinfo($orig_name, PATHINFO_EXTENSION);
        
        $upload_dir = FCPATH . 'uploads/specs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filepath = $upload_dir . $encrypted_name;
        if (file_put_contents($filepath, $file_content) === FALSE) {
            echo json_encode(['status' => 'error', 'message' => 'Не удалось записать файл на диск']);
            return;
        }

        $file_size = filesize($filepath);

        // Сохраняем локально (is_link = 0)
        $file_id = $this->Customer_model->add_spec_file($spec_id, $encrypted_name, $orig_name, $file_size, 0);

        echo json_encode([
            'status' => 'success',
            'file_id' => $file_id,
            'orig_name' => $orig_name,
            'download_url' => site_url('customers/download_file/' . $file_id),
            'file_size' => $file_size,
            'icon' => get_file_icon_emoji($orig_name, 0)
        ]);
    }

    /**
     * AJAX-метод получения ТЗ заказчика
     * 
     * @param int $customer_id ID заказчика
     */
    public function get_specs_ajax($customer_id) {
        $user_id = $this->session->userdata('user_id');
        $customer = $this->Customer_model->get_by_id($customer_id, $user_id);
        if ($customer) {
            $specs = $this->Customer_model->get_specs($customer_id);
            echo json_encode(['status' => 'success', 'data' => $specs]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Заказчик не найден']);
        }
    }

    // =========================================================================
    // ВНУТРЕННИЕ ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // =========================================================================

    /**
     * Построение иерархического дерева задач для конкретного заказчика
     * 
     * @param array $elements Плоский список задач
     * @param int $customer_id ID заказчика
     * @param int|null $parentId ID родительской задачи для рекурсии
     * @return array Дерево задач
     */
    private function _build_customer_tasks_tree(array $elements, $customer_id, $parentId = null) {
        $branch = array();
        $user_id = $this->session->userdata('user_id');

        foreach ($elements as $element) {
            // Проверяем принадлежность к родителю
            $is_match = ($element['parent_id'] == $parentId);
            
            if ($is_match) {
                // Если корень, проверяем заказчика. Для подзадач наследуем от родителя.
                if ($parentId === null && $element['customer_id'] != $customer_id) {
                    continue;
                }

                // Рекурсивный поиск дочерних подзадач
                $children = $this->_build_customer_tasks_tree($elements, $customer_id, $element['id']);
                $element['children'] = $children ? $children : [];

                // Считаем время
                $total_seconds = $this->Task_model->get_task_time_recursive($element['id'], $user_id);
                $hours = floor($total_seconds / 3600);
                $minutes = floor(($total_seconds % 3600) / 60);
                $element['formatted_time'] = sprintf(lang('time_format_hours_mins'), $hours, $minutes);

                $branch[] = $element;
            }
        }

        return $branch;
    }

    /**
     * AJAX-обработчик бесконечного скролла для сайдбара заказчиков
     */
    public function load_more_ajax() {
        $user_id = $this->session->userdata('user_id');
        $offset = (int)$this->input->post('offset');
        $active_customer_id = $this->input->post('active_customer_id');
        
        $this->load->model('Settings_model');
        $limit = (int)$this->Settings_model->get_setting('per_page', 25);
        
        $customers = $this->Customer_model->get_all($user_id, $limit, $offset);
        if (empty($customers)) {
            echo json_encode(['status' => 'success', 'html' => '', 'has_more' => false]);
            return;
        }
        
        // Рендерим HTML для элементов сайдбара
        $html = '';
        foreach ($customers as $c) {
            $is_active = ($active_customer_id == $c['id']);
            $active_classes = $is_active ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50';
            
            $html .= '<a href="' . site_url('customers/index/' . $c['id']) . '" class="customer-item block px-6 py-4 border-l-4 ' . $active_classes . ' transition-all font-medium text-lg">';
            $html .= htmlspecialchars($c['name']);
            $html .= '</a>';
        }
        
        // Проверяем, есть ли еще заказчики
        $next_customers = $this->Customer_model->get_all($user_id, 1, $offset + $limit);
        $has_more = !empty($next_customers);
        
        echo json_encode([
            'status' => 'success',
            'html' => $html,
            'has_more' => $has_more
        ]);
    }
}
