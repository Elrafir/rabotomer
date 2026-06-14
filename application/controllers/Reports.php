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
        // Загружаем модель Customer_model для получения списка заказчиков в фильтрах
        $this->load->model('Customer_model');
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
        // Запоминаем текущее время сервера
        $now = time();
        
        // Извлекаем текущий час на сервере в 24-часовом формате
        $hour = (int)date('H', $now);
        
        // Если время меньше 5 утра, то рабочий день начался вчера, иначе рабочий день начался сегодня
        if ($hour < 5) {
            // Текущий рабочий день начался вчера
            $default_start = date('Y-m-d', strtotime('-1 day', $now));
        } else {
            // Текущий рабочий день начался сегодня
            $default_start = date('Y-m-d', $now);
        }
        
        // По умолчанию и начало, и конец периода устанавливаем на текущий рабочий день
        $default_end = $default_start;

        // Вычисляем вчерашний рабочий день относительно текущего рабочего дня
        $yesterday_start = date('Y-m-d', strtotime('-1 day', strtotime($default_start)));
        // Вчерашний рабочий день длится один день, поэтому конец совпадает со стартом
        $yesterday_end = $yesterday_start;

        // Получаем числовой индекс дня недели текущего рабочего дня (1 - Понедельник, 7 - Воскресенье)
        $day_of_week = (int)date('N', strtotime($default_start));
        // Вычисляем понедельник текущей рабочей недели
        $week_start = date('Y-m-d', strtotime('-' . ($day_of_week - 1) . ' days', strtotime($default_start)));
        // Временной отрезок недели заканчивается текущим рабочим днем
        $week_end = $default_start;

        // Вычисляем первый день месяца для текущего рабочего дня
        $month_start = date('Y-m-01', strtotime($default_start));
        // Период месяца заканчивается текущим рабочим днем
        $month_end = $default_start;

        // Получаем дату начала диапазона из POST/GET запроса или ставим вычисленный по умолчанию день
        $start_date = $this->input->post('start') ?: ($this->input->get('start') ?: $default_start);
        
        // Получаем дату окончания диапазона из POST/GET запроса или ставим вычисленный по умолчанию день
        $end_date = $this->input->post('end') ?: ($this->input->get('end') ?: $default_end);
        
        // Получаем флаг "Показывать архивные" (1 — показать все, 0 — только активные), по умолчанию 1
        $show_archived = $this->input->post('show_archived') !== null ? (int)$this->input->post('show_archived') : ($this->input->get('show_archived') !== null ? (int)$this->input->get('show_archived') : 1);
        
        // Получаем фильтр по заказчикам (по умолчанию 'all' - все)
        $customer_filter = $this->input->post('customer_filter') ?: ($this->input->get('customer_filter') ?: 'all');
        
        // Получаем выбранный тип сортировки (по умолчанию 'time' - по времени)
        $sort_by = $this->input->post('sort_by') ?: ($this->input->get('sort_by') ?: 'time');
        
        // Извлекаем идентификатор текущего пользователя из сессии
        $user_id = $this->session->userdata('user_id');
        
        // Загружаем список всех заказчиков пользователя
        $customers = $this->Customer_model->get_all($user_id);
        
        // Запрашиваем расчетные данные временного среза у нашей модели статистики с фильтрацией и сортировкой
        $report_data = $this->Stats_model->get_time_slice($user_id, $start_date, $end_date, $show_archived, $customer_filter, $sort_by);
        
        // Подготавливаем массив данных для рендеринга шаблона
        $data = [
            // Передаем дату старта для полей ввода во вьюшке
            'start_date' => $start_date,
            // Передаем дату завершения для полей ввода во вьюшке
            'end_date' => $end_date,
            // Передаем вычисленный текущий рабочий день
            'today_start' => $default_start,
            'today_end' => $default_end,
            // Передаем вчерашний рабочий день
            'yesterday_start' => $yesterday_start,
            'yesterday_end' => $yesterday_end,
            // Передаем диапазон текущей рабочей недели
            'week_start' => $week_start,
            'week_end' => $week_end,
            // Передаем диапазон текущего рабочего месяца
            'month_start' => $month_start,
            'month_end' => $month_end,
            // Передаем текущее состояние фильтра архивных
            'show_archived' => $show_archived,
            // Передаем активный фильтр по заказчику
            'customer_filter' => $customer_filter,
            // Передаем активный тип сортировки
            'sort_by' => $sort_by,
            // Передаем список заказчиков для выпадающего меню фильтрации
            'customers' => $customers,
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
        
        // Получаем фильтр по заказчикам (по умолчанию 'all' - все)
        $customer_filter = $this->input->post('customer_filter') ?: ($this->input->get('customer_filter') ?: 'all');
        
        // Получаем выбранный тип сортировки (по умолчанию 'time' - по времени)
        $sort_by = $this->input->post('sort_by') ?: ($this->input->get('sort_by') ?: 'time');
        
        // Получаем идентификатор текущего пользователя
        $user_id = $this->session->userdata('user_id');
        
        // Загружаем список всех заказчиков пользователя
        $customers = $this->Customer_model->get_all($user_id);
        
        // Запрашиваем дерево проектов с каскадными суммами времени у модели с учетом фильтров
        $projects_tree = $this->Stats_model->get_project_slice($user_id, $show_archived, $customer_filter, $sort_by);
        
        // Наполняем массив данных для передачи во вьюху
        $data = [
            // Флаг архива для инпутов
            'show_archived' => $show_archived,
            // Передаем активный фильтр по заказчику
            'customer_filter' => $customer_filter,
            // Передаем активный тип сортировки
            'sort_by' => $sort_by,
            // Передаем список заказчиков для выпадающего меню
            'customers' => $customers,
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
