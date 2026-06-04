<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Текстовые строки приложения (Русский язык)
|--------------------------------------------------------------------------
| Здесь собраны все хардкодные тексты для контроллеров и представлений.
| Вызов во View: <?= lang('ключ_строки'); ?>
| Вызов в Controller: $this->lang->line('ключ_строки');
*/

// ============================================
// Общие элементы (Кнопки)
// ============================================
$lang['btn_start'] = '▶ СТАРТ';
$lang['btn_stop'] = '⏹ СТОП';
$lang['btn_pause'] = '⏸ ПАУЗА';
$lang['btn_done'] = '✔ ГОТОВО';
$lang['btn_save'] = 'Сохранить';
$lang['btn_cancel'] = 'Отмена';
$lang['btn_create'] = 'Создать';
$lang['btn_add'] = 'Добавить';
$lang['btn_add_subtask'] = '+ Подзадача';
$lang['btn_edit'] = 'Редактировать';
$lang['btn_delete'] = 'Удалить';

// ============================================
// Статусы
// ============================================
$lang['status_active'] = 'В РАБОТЕ';
$lang['status_completed'] = 'ЗАВЕРШЕНО';

// ============================================
// Страница Авторизации (login.php и Auth.php)
// ============================================
$lang['login_title'] = 'Авторизация';
$lang['login_username_label'] = 'Имя пользователя';
$lang['login_username_placeholder'] = 'Введите логин...';
$lang['login_password_label'] = 'Пароль';
$lang['login_password_placeholder'] = '••••••••';
$lang['login_submit'] = 'Войти в систему';
$lang['login_error_invalid'] = 'Неверное имя пользователя или пароль.';

// ============================================
// Главная страница (dashboard.php и Tasks.php)
// ============================================
$lang['dash_new_project_title'] = 'Новый проект';
$lang['dash_new_project_placeholder'] = 'Название проекта (корневая задача)...';
$lang['dash_search_placeholder'] = 'Поиск задач...';
$lang['dash_tree_title'] = 'Дерево задач';
$lang['dash_tree_empty'] = 'У вас пока нет задач. Создайте свой первый проект!';
$lang['dash_subtask_placeholder'] = 'Название подзадачи...';
$lang['dash_subtask_placeholder'] = 'Название подзадачи...';
$lang['dash_timer_in_progress'] = '🔥 В работе';
$lang['dash_timer_current_session'] = 'Текущая сессия:';
$lang['dash_timer_total'] = 'Общее время:';

// ============================================
// Модальное окно (Ручная корректировка)
// ============================================
$lang['modal_edit_title'] = 'Корректировка времени';
$lang['modal_edit_task'] = 'Задача:';
$lang['modal_edit_start'] = 'Начало:';
$lang['modal_edit_end'] = 'Конец:';
$lang['modal_edit_save'] = 'Сохранить сессию';
$lang['modal_recent_sessions'] = 'Недавние сессии (чтение)';
$lang['modal_loading'] = 'Загрузка...';
$lang['modal_no_records'] = 'Нет записей';
$lang['modal_btn_delete_title'] = 'Удалить сессию';
$lang['modal_edit_note'] = 'Результат (заметка):';
$lang['modal_note_placeholder'] = 'Кратко опишите результат работы...';
$lang['modal_history_title'] = 'История сессий и корректировка';
$lang['modal_col_start'] = 'Старт';
$lang['modal_col_end'] = 'Конец';
$lang['modal_col_duration'] = 'Длит-ность';
$lang['modal_col_note'] = 'Результат';
$lang['modal_btn_delete_title'] = 'Удалить сессию';

// ============================================
// Окна подтверждений и Alerts (JS)
// ============================================
$lang['js_confirm_complete'] = 'Вы уверены, что хотите завершить эту задачу и все её подзадачи?';
$lang['js_confirm_delete'] = 'Точно удалить эту сессию времени?';
$lang['js_confirm_delete_task'] = 'Внимание! При удалении задачи удалятся все её подзадачи и сессии времени. Продолжить?';
$lang['js_alert_fill_fields'] = 'Заполните оба поля времени!';
$lang['js_prompt_stop_timer'] = 'Краткий результат работы (необязательно):';

// ============================================
// Сообщения об ошибках (AJAX)
// ============================================
$lang['ajax_error_start'] = 'Ошибка при запуске таймера';
$lang['ajax_error_stop'] = 'Нет активного таймера';
$lang['ajax_error_complete'] = 'Ошибка при завершении задачи';
$lang['ajax_error_no_task'] = 'Не указана задача';
$lang['ajax_error_end_less_start'] = 'Время завершения должно быть больше времени начала';
$lang['ajax_error_system_save'] = 'Системная ошибка при сохранении';
$lang['ajax_error_delete'] = 'Ошибка при удалении сессии';
$lang['ajax_error_edit_task'] = 'Ошибка при редактировании задачи';
$lang['ajax_error_delete_task'] = 'Ошибка при удалении задачи';
$lang['ajax_error_restore'] = 'Ошибка при восстановлении задачи';

// ============================================
// Дашборд дополнительные
// ============================================
$lang['btn_restore'] = 'Восстановить';
$lang['dash_show_completed_projects'] = '👁️ Показать завершенные проекты';
$lang['dash_hide_completed_projects'] = '👁️ Скрыть завершенные проекты';
$lang['dash_hidden_completed_subtasks'] = '👁️ Скрыто завершенных: %d';

// ============================================
// Отчеты и Статистика (reports.php и Reports.php)
// ============================================
$lang['nav_tasks'] = 'Задачи';
$lang['nav_reports'] = 'Статистика';
$lang['reports_title'] = 'Статистика времени';
$lang['reports_filter_today'] = 'Сегодня';
$lang['reports_filter_yesterday'] = 'Вчера';
$lang['reports_filter_week'] = 'Текущая неделя';
$lang['reports_filter_month'] = 'Текущий месяц';
$lang['reports_lbl_from'] = 'С';
$lang['reports_lbl_to'] = 'По';
$lang['reports_btn_show'] = 'Показать';
$lang['reports_total_time'] = 'Всего отработано:';
$lang['reports_tasks_list'] = 'Задачи за период:';

// ============================================
// Админка (users.php и Admin.php)
// ============================================
$lang['nav_admin'] = 'Админка';
$lang['admin_title'] = 'Управление пользователями';
$lang['admin_btn_add_user'] = '➕ Добавить пользователя';
$lang['admin_col_login'] = 'Логин';
$lang['admin_col_password'] = 'Пароль';
$lang['admin_col_role'] = 'Роль';
$lang['admin_btn_delete'] = 'Удалить пользователя';
$lang['admin_msg_user_created'] = 'Пользователь успешно создан';
$lang['admin_err_login_taken'] = 'Ошибка: такой логин уже занят';

// Бэкапы
$lang['admin_backup_title'] = 'Резервное копирование';
$lang['admin_btn_create_backup'] = '💾 Создать бэкап';
$lang['admin_backup_list'] = 'Список бэкапов';
$lang['admin_col_filename'] = 'Имя файла';
$lang['admin_col_size'] = 'Размер';
$lang['admin_col_date'] = 'Дата создания';
$lang['admin_msg_backup_created'] = 'Бэкап успешно создан';
$lang['admin_err_backup_failed'] = 'Ошибка при создании бэкапа';
$lang['admin_btn_delete_file'] = 'Удалить файл';

$lang['admin_btn_change_password'] = 'Изменить пароль';
$lang['admin_lbl_new_password'] = 'Новый пароль';
$lang['admin_lbl_repeat_password'] = 'Повторите пароль';
$lang['admin_msg_password_changed'] = 'Пароль изменен';
$lang['admin_err_short_password'] = 'Слишком короткий пароль';
$lang['admin_err_passwords_mismatch'] = 'Пароли не совпадают';
$lang['admin_col_total_tasks'] = 'Всего задач пользователя:';
$lang['admin_col_last_activity'] = 'Последняя активность:';
$lang['admin_col_total_time'] = 'Всего времени:';
$lang['admin_sys_info'] = 'Системная информация:';
$lang['admin_sys_free_space'] = 'Свободно места на диске:';

// ============================================
// Рефакторинг Статистики (Цвета, Архив, Группировка)
// ============================================
$lang['reports_color_task'] = 'Цвет задачи';
$lang['reports_archived_projects'] = 'Архивные проекты';
$lang['reports_compact_view'] = 'Компактный вид';
$lang['reports_no_color'] = 'Без цвета';
$lang['reports_group_by_days'] = 'Группировка по дням';
$lang['reports_tab_time'] = 'Затраченное время';
$lang['reports_tab_archive'] = 'Архив проектов';
$lang['reports_date'] = 'Дата: ';
$lang['reports_total_archived_time'] = 'Суммарное время:';
$lang['ajax_error_set_color'] = 'Ошибка при сохранении цвета';

// Форматы времени (для контроллеров)
$lang['time_format_hours_mins'] = '%02d ч. %02d мин.';

// ============================================
// Журнал Активности (history.php и History.php)
// ============================================
$lang['nav_history'] = 'Журнал';
$lang['history_title'] = 'Журнал активности';
$lang['history_empty'] = 'История сессий пуста.';
$lang['history_col_date'] = 'Когда';
$lang['history_col_task'] = 'Где (Задача)';
$lang['history_col_duration'] = 'Сколько';
$lang['history_col_note'] = 'Результат';
$lang['ajax_error_spam'] = 'Сессия слишком короткая (менее 1 минуты) и не была сохранена.';

// Каскадная история
$lang['cascade_history_title'] = 'История проекта';
$lang['cascade_no_sessions'] = 'Сессий не найдено';
$lang['cascade_col_task'] = 'Задача / Подзадача';
$lang['cascade_col_note'] = 'Заметка';
$lang['cascade_col_duration'] = 'Длительность';
$lang['cascade_show_more'] = 'И ещё %d записей... Откройте полную историю (📜).';
$lang['cascade_search_placeholder'] = 'Поиск по названию или заметке...';

// Заказчики и Финансы
$lang['nav_customers'] = 'Заказчики';
$lang['customers_title'] = 'Заказчики';
$lang['customers_new'] = 'Новый заказчик';
$lang['customers_name'] = 'Имя/Название';
$lang['customers_rate'] = 'Ставка в час (по умолчанию)';
$lang['finance_type'] = 'Тип оплаты';
$lang['finance_hourly'] = 'Почасовая';
$lang['finance_fixed'] = 'Фикс. цена';
$lang['finance_price'] = 'Сумма';
$lang['finance_earned'] = 'Заработано:';
$lang['finance_no_customer'] = 'Клиент не выбран';
$lang['finance_without_customer'] = 'Без заказчика';
$lang['finance_badge_fixed'] = 'ФИКС';
