<?php
// Запрещаем прямой доступ к файлу во избежание обхода фреймворка
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Базовый контроллер приложения (MY_Controller)
 *
 * Все рабочие контроллеры проекта (где требуется обязательная авторизация)
 * наследуются от этого класса. Он обеспечивает автоматическую проверку
 * сессии пользователя на каждом запросе, включая куки автологина.
 */
class MY_Controller extends CI_Controller {

    /**
     * Конструктор базового класса.
     * Автоматически проверяет авторизацию пользователя и осуществляет автологин.
     */
    public function __construct() {
        // Вызов конструктора родительского класса CI_Controller
        parent::__construct();

        // Считываем имя текущего вызываемого контроллера
        $current_controller = $this->router->fetch_class();
        
        // Если в сессии отсутствует идентификатор пользователя (пользователь не авторизован)
        if (!$this->session->userdata('user_id')) {
            
            // Подключаем хелпер для считывания куки автологина
            $this->load->helper('cookie');
            
            // Пытаемся получить сохраненный токен "Запомнить меня" из куки
            $token = get_cookie('remember_token');
            
            // Если токен присутствует в браузере
            if ($token) {
                // Подгружаем модель пользователей
                $this->load->model('User_model');
                // Запрашиваем из БД пользователя по данному токену
                $user = $this->User_model->get_user_by_token($token);
                
                // Если пользователь с таким токеном успешно найден
                if ($user) {
                    // Восстанавливаем данные авторизации в сессионном пуле
                    $this->session->set_userdata([
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'group_id' => $user['group_id'], // Сохраняем группу доступа
                        'user_theme' => isset($user['user_theme']) ? $user['user_theme'] : 'theme-default', // Применяем тему
                        'user_theme_opacity' => isset($user['user_theme_opacity']) ? $user['user_theme_opacity'] : '1.00', // Применяем прозрачность
                        'user_custom_hue' => isset($user['user_custom_hue']) ? $user['user_custom_hue'] : '221' // Применяем кастомный тон
                    ]);
                }
            }
            
            // Если после попытки автологина сессия все еще не создана, и текущий запрос идет не к контроллеру 'auth'
            if (!$this->session->userdata('user_id') && $current_controller !== 'auth') {
                
                // Проверяем, является ли запрос AJAX-запросом (актуально для SPA переходов)
                if ($this->input->is_ajax_request()) {
                    // При AJAX-запросе возвращаем скрипт перенаправления на страницу входа
                    echo "<script>window.location.href = '" . site_url('auth/login') . "';</script>";
                    // Завершаем выполнение скрипта
                    exit;
                }
                
                // Для обычных браузерных запросов делаем редирект на страницу входа
                redirect('auth/login');
            }
        }
    }

    /**
     * Обертка для рендеринга страниц с использованием общего макета.
     * Загружает header, body (в который вкладывается $inner_view) и footer.
     * 
     * @param string $inner_view Имя файла представления для загрузки внутри body
     * @param array $data Массив данных для передачи в представления
     */
    protected function render_page($inner_view, $data = []) {
        // Наполняем массив данных настройками оформления текущего пользователя из сессии
        $data['user_theme'] = $this->session->userdata('user_theme') ? $this->session->userdata('user_theme') : 'theme-default';
        $data['user_theme_opacity'] = $this->session->userdata('user_theme_opacity') ? $this->session->userdata('user_theme_opacity') : '1.00';
        $data['user_custom_hue'] = $this->session->userdata('user_custom_hue') !== null ? $this->session->userdata('user_custom_hue') : '221';

        // Если запрос является AJAX (SPA переход)
        if ($this->input->is_ajax_request()) {
            // Подгружаем модель задач
            $this->load->model('Task_model');
            // Получаем ID пользователя
            $user_id = $this->session->userdata('user_id');
            // Проверяем наличие запущенного таймера активности для вывода в шапке
            $data['active_session'] = $user_id ? $this->Task_model->get_active_session($user_id) : null;
            
            // Указываем имя шаблона внутреннего контента
            $data['inner_view'] = $inner_view;
            // Загружаем только контентную область для SPA обновления
            $this->load->view('templates/content_wrapper', $data);
        } else {
            // В случае обычного запроса собираем страницу полностью из трех частей
            $data['inner_view'] = $inner_view;
            // Шапка (head, стили)
            $this->load->view('templates/header', $data);
            // Тело страницы (меню, разметка)
            $this->load->view('templates/body', $data);
            // Подвал (подключение скриптов)
            $this->load->view('templates/footer', $data);
        }
    }
}
