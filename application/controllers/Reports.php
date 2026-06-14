<?php
// Блокируем прямой запуск файла вне фреймворка CodeIgniter
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Контроллер Reports
 * Отвечает за рендеринг разделов статистики и обработку входящих AJAX-запросов фильтрации.
 */
class Reports extends MY_Controller {

    /**
     * Конструктор контроллера. Загружает необходимые модели и вызывает родительский конструктор.
     */
    public function __construct() {
        // Вызываем родительский конструктор базового контроллера MY_Controller для проверки авторизации
        parent::__construct();
        // Загружаем классическую модель задач, если потребуется
        $this->load->model('Task_model');
        // Загружаем специализированную модель Stats_model для вычислений статистики
        $this->load->model('Stats_model');
    }

    /**
     * Главная страница отчетов. Перенаправляет пользователя на подраздел "Временной срез" по умолчанию.
     */
    public function index() {
        // Вызываем метод временного среза, который является дефолтным представлением статистики
        $this->time_slice();
    }

    /**
     * Подраздел статистики: Временной срез (Динамика за период).
     * Обрабатывает выбор дат и фильтрацию архивных проектов.
     */
    public function time_slice() {
        // Получаем дату начала диапазона из POST/GET запроса или ставим первый день текущего месяца
        $start_date = $this->input->post('start') ?: ($this->input->get('start') ?: date('Y-m-01'));
        
        // Получаем дату окончания диапазона из POST/GET запроса или ставим сегодняшний день
        $end_date = $this->input->post('end') ?: ($this->input->get('end') ?: date('Y-m-d'));
        
        // Получаем флаг "Показывать архивные" (1 — показать все, 0 — только активные), по умолчанию 1
        $show_archived = $this->input->post('show_archived') !== null ? (int)$this->input->post('show_archived') : ($this->input->get('show_archived') !== null ? (int)$this->input->get('show_archived') : 1);
        
        // Извлекаем идентификатор текущего пользователя из сессии
        $user_id = $this->session->userdata('user_id');
        
        // Запрашиваем расчетные данные временного среза у нашей модели статистики
        $report_data = $this->Stats_model->get_time_slice($user_id, $start_date, $end_date, $show_archived);
        
        // Подготавливаем массив данных для рендеринга шаблона
        $data = [
            // Передаем дату старта для полей ввода во вьюшке
            'start_date' => $start_date,
            // Передаем дату завершения для полей ввода во вьюшке
            'end_date' => $end_date,
            // Передаем текущее состояние фильтра архивных
            'show_archived' => $show_archived,
            // Общее затраченное время текстом
            'total_time_formatted' => $report_data['formatted_total_time'],
            // Список проектов с подсчитанными процентами и долями
            'projects' => $report_data['projects'],
            // Указываем левую панель меню разделов статистики
            'left_sidebar_view' => 'sidebars/statistics',
            // Указываем активную вкладку для левого меню
            'active_sub_page' => 'time_slice'
        ];
        
        // Рендерим представление временного среза (MY_Controller сам выделит AJAX и SPA-обертку)
        $this->render_page('reports/time_slice', $data);
    }

    /**
     * Подраздел статистики: Проектный срез (Абсолютный учёт долгостроев).
     * Строит дерево проектов с каскадными суммами времени.
     */
    public function project_slice() {
        // Получаем флаг отображения архивных (1 — показывать, 0 — скрыть), по умолчанию 1
        $show_archived = $this->input->post('show_archived') !== null ? (int)$this->input->post('show_archived') : ($this->input->get('show_archived') !== null ? (int)$this->input->get('show_archived') : 1);
        
        // Получаем идентификатор текущего пользователя
        $user_id = $this->session->userdata('user_id');
        
        // Запрашиваем дерево проектов с каскадными суммами времени у модели
        $projects_tree = $this->Stats_model->get_project_slice($user_id, $show_archived);
        
        // Наполняем массив данных для передачи во вьюху
        $data = [
            // Флаг архива для инпутов
            'show_archived' => $show_archived,
            // Готовое дерево проектов с подсчитанным временем
            'projects_tree' => $projects_tree,
            // Левое меню статистики
            'left_sidebar_view' => 'sidebars/statistics',
            // Активная вкладка левой панели
            'active_sub_page' => 'project_slice'
        ];
        
        // Отрисовываем страницу проектного среза
        $this->render_page('reports/project_slice', $data);
    }
}
