<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MobileApp extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Разрешаем доступ даже без авторизации, чтобы можно было скачать приложение до входа
    }

    public function index() {
        $data['title'] = 'Нативные приложения';
        
        $data['user_theme'] = $this->session->userdata('user_theme') ? $this->session->userdata('user_theme') : 'theme-default';
        $data['user_theme_opacity'] = $this->session->userdata('user_theme_opacity') ? $this->session->userdata('user_theme_opacity') : '1.00';
        $data['user_custom_hue'] = $this->session->userdata('user_custom_hue') !== null ? $this->session->userdata('user_custom_hue') : '221';

        if ($this->input->is_ajax_request()) {
            $data['inner_view'] = 'mobile_app';
            $this->load->view('templates/content_wrapper', $data);
        } else {
            $data['inner_view'] = 'mobile_app';
            $this->load->view('templates/header', $data);
            $this->load->view('templates/body', $data);
            $this->load->view('templates/footer', $data);
        }
    }

    /**
     * Скачивание файла приложения
     */
    public function download($platform = 'android') {
        $this->load->helper('download');
        $this->load->config('app_version', TRUE, TRUE);
        $version = $this->config->item('app_version', 'app_version') ?? '1.0.6';
        
        if ($platform === 'windows') {
            $candidates = [
                FCPATH . "assets/downloads/Работомер_v{$version}_Windows.zip",
                FCPATH . "assets/downloads/Работомер_Windows.zip"
            ];
            $defaultName = "Работомер_v{$version}_Windows.zip";
        } else {
            $candidates = [
                FCPATH . "assets/downloads/Работомер_v{$version}.apk",
                FCPATH . "assets/downloads/Работомер.apk"
            ];
            $defaultName = "Работомер_v{$version}.apk";
        }

        $filePath = null;
        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                $filePath = $cand;
                $defaultName = basename($cand);
                break;
            }
        }

        if ($filePath && file_exists($filePath)) {
            force_download($defaultName, file_get_contents($filePath));
        } else {
            show_404();
        }
    }

    /**
     * API для проверки актуальной версии
     */
    public function version() {
        $this->load->config('app_version', TRUE, TRUE); // Загружаем конфиг, если его нет - игнорируем ошибку
        
        $version = $this->config->item('app_version', 'app_version') ?? '1.0.1';
        $versionCode = $this->config->item('app_version_code', 'app_version') ?? 2;
        $notes = $this->config->item('app_release_notes', 'app_version') ?? '';
        $baseUrl = $this->config->item('app_download_base_url', 'app_version');

        // Формируем URL для скачивания
        if (!empty($baseUrl)) {
            // Если задан внешний Git-сервер (убеждаемся, что есть слэш на конце)
            $baseUrl = rtrim($baseUrl, '/') . '/';
            $androidUrl = $baseUrl . 'Работомер.apk';
            $windowsUrl = $baseUrl . 'Работомер_Windows.zip';
        } else {
            // Если не задан, используем текущий локальный сервер
            $androidUrl = site_url('MobileApp/download/android');
            $windowsUrl = site_url('MobileApp/download/windows');
        }

        $data = [
            'version' => $version,
            'versionCode' => $versionCode,
            'releaseNotes' => $notes,
            'downloadUrls' => [
                'android' => $androidUrl,
                'windows' => $windowsUrl
            ]
        ];

        // Отдаем JSON, разрешаем CORS
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($data);
    }

    /**
     * Сброс адреса сервера для мобильного приложения
     */
    public function reset_setup() {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Resetting...</title></head><body>';
        echo '<script>';
        echo 'try { localStorage.removeItem("timeTrackerServerUrl"); } catch(e){}';
        echo 'if (window.location.protocol === "file:") {';
        echo '   window.location.href = "index.html?reset=1";';
        echo '} else {';
        echo '   window.location.href = "/?reset=1";';
        echo '}';
        echo '</script>';
        echo '</body></html>';
    }
}
