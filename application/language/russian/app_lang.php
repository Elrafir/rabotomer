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
$lang['btn_to_trash'] = 'В корзину';

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
$lang['js_confirm_delete_task'] = 'Отправить задачу и все её подзадачи в корзину? (Оттуда их можно будет восстановить)';
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

// Дополнительные ключи для Заказчиков и ТЗ
$lang['nav_stats_calc'] = 'Статистика и расчёты';
$lang['nav_calculations'] = 'Калькуляция';
$lang['cust_title_and_spec'] = 'Заказчики и ТЗ';
$lang['cust_list_title'] = 'Список заказчиков';
$lang['cust_empty_list'] = 'Заказчиков нет';
$lang['cust_no_customers_title'] = 'У вас пока нет заказчиков';
$lang['cust_no_customers_desc'] = 'Создайте своего первого заказчика при помощи кнопки слева, чтобы начать работу.';
$lang['cust_info_btn'] = 'Подробнее (Инфо)';
$lang['cust_delete_confirm'] = 'Вы действительно хотите удалить заказчика и все его ТЗ?';
$lang['cust_linked_tasks'] = 'Связанные задачи';
$lang['cust_no_tasks'] = 'К этому заказчику пока не привязано ни одной задачи.';
$lang['cust_specs_title'] = 'Технические задания (ТЗ)';
$lang['cust_create_spec_btn'] = 'Создать ТЗ';
$lang['cust_no_specs'] = 'Технических заданий не создано. Нажмите кнопку выше, чтобы добавить новое ТЗ.';
$lang['cust_created_at'] = 'Создано:';
$lang['cust_price_badge'] = 'Ценник:';
$lang['cust_prepayment_badge'] = 'Предоплата:';
$lang['cust_payment_badge'] = 'Оплата:';
$lang['cust_delete_spec_confirm'] = 'Удалить это ТЗ со всеми прикрепленными файлами?';
$lang['cust_spec_linked_tasks'] = 'Связанные задачи:';
$lang['cust_spec_no_linked_tasks'] = 'Нет связанных задач';
$lang['cust_attached_files_title'] = 'Прикрепленные файлы и ссылки';
$lang['cust_no_files'] = 'Нет файлов';
$lang['cust_file_link'] = 'Ссылка';
$lang['cust_download_title'] = 'Скачать';
$lang['cust_dropzone_text'] = 'Перетащите файлы сюда или';
$lang['cust_dropzone_select'] = 'выберите на диске';
$lang['cust_details_title'] = 'Детали заказчика';
$lang['cust_name_label'] = 'Название / Имя';
$lang['cust_notes_label'] = 'Заметки / Контакты';
$lang['cust_no_notes'] = 'Нет примечаний';
$lang['cust_default_price_label'] = 'Ценник по умолчанию (руб.)';
$lang['cust_default_prepayment_label'] = 'Предоплата по умолчанию (руб.)';
$lang['cust_default_payment_type_label'] = 'Тип оплаты по умолчанию';
$lang['cust_edit_customer_title'] = 'Редактировать заказчика';
$lang['cust_new_spec_title'] = 'Новое техническое задание';
$lang['cust_spec_title_label'] = 'Название ТЗ';
$lang['cust_spec_title_placeholder'] = 'Например: Разработка модуля авторизации';
$lang['cust_price_label'] = 'Ценник (руб.)';
$lang['cust_prepayment_label'] = 'Предоплата (руб.)';
$lang['cust_payment_type_label'] = 'Тип оплаты';
$lang['cust_link_tasks_label'] = 'Привязать задачи';
$lang['cust_no_tasks_available'] = 'Нет доступных задач для привязки';
$lang['cust_spec_content_label'] = 'Содержимое (Требования)';
$lang['cust_save_spec_btn'] = 'Сохранить ТЗ';
$lang['cust_edit_spec_title'] = 'Редактировать ТЗ';
$lang['cust_save_changes_btn'] = 'Сохранить изменения';
$lang['cust_spec_desc_placeholder'] = 'Опишите требования к ТЗ...';

$lang['js_confirm_delete_file'] = 'Вы действительно хотите удалить этот файл ТЗ?';
$lang['js_err_system_upload'] = 'Произошла системная ошибка при загрузке файла';
$lang['js_err_upload_fail'] = 'Ошибка отправки файла на сервер';
$lang['js_err_delete_file_fail'] = 'Произошла ошибка при удалении файла';
$lang['js_err_enter_url'] = 'Введите URL-адрес ссылки';
$lang['js_err_add_link_fail'] = 'Ошибка при добавлении ссылки';
$lang['js_err_enter_url_download'] = 'Введите URL-адрес для скачивания';
$lang['js_err_download_url_fail'] = 'Ошибка при скачивании файла';

// Общие ключи для форм и таблиц
$lang['lbl_actions'] = 'Действия';
$lang['lbl_task'] = 'Задача';
$lang['lbl_select_task'] = 'Выберите задачу...';
$lang['lbl_start_time'] = 'Время начала';
$lang['lbl_end_time'] = 'Время окончания';
$lang['lbl_note_result'] = 'Заметка / Результат';
$lang['lbl_what_was_done_placeholder'] = 'Что было сделано...';
$lang['lbl_session_add'] = 'Добавить сессию';
$lang['lbl_session_edit'] = 'Редактировать сессию';

// JS ошибки и оповещения
$lang['js_err_required_fields'] = 'Заполните все обязательные поля!';
$lang['js_err_system_save'] = 'Произошла системная ошибка при сохранении';
$lang['js_err_system_save_changes'] = 'Произошла системная ошибка при сохранении изменений';
$lang['js_err_system_delete'] = 'Произошла системная ошибка при удалении';
$lang['js_err_delete_session_fail'] = 'Произошла ошибка при удалении сессии';

// Дополнительные ключи для дашборда
$lang['dash_subtasks_count'] = 'Подзадач';
$lang['dash_stop_timer_title'] = 'Остановить сессию';
$lang['dash_stop_timer_btn'] = 'Остановить?';
$lang['dash_finalize_title'] = 'Финализировать задачу';
$lang['dash_finalize_desc'] = 'Завершает данную задачу и все вложенные в нее подзадачи.';
$lang['dash_edit_properties_title'] = 'Редактировать свойства задачи';
$lang['dash_manual_adjust_title'] = 'Ручная корректировка времени';
$lang['dash_add_new_client'] = '+ Добавить нового клиента';
$lang['dash_client_name_placeholder'] = 'Имя клиента';
$lang['dash_client_notes_placeholder'] = 'Заметки';
$lang['dash_link_spec_label'] = 'Техническое задание (ТЗ)';
$lang['dash_link_spec_placeholder'] = 'Связать с ТЗ...';
$lang['js_confirm_restore_task'] = 'Восстановить задачу?';
$lang['js_err_enter_task_title'] = 'Введите название задачи!';

// Дополнительные ключи для шаблона body.php и тем оформления
$lang['nav_trash'] = 'Корзина';
$lang['body_theme_config'] = 'Настроить оформление';
$lang['body_edit_profile'] = 'Редактировать профиль';
$lang['body_logout'] = 'Выйти';
$lang['body_widget_stop_session'] = 'Завершить текущую сессию';
$lang['body_widget_collapse'] = 'свернуть';
$lang['body_theme_color'] = 'Цвет интерфейса';
$lang['body_theme_bg_opacity'] = 'Плотность фона';
$lang['body_theme_hue'] = 'Тон (Hue)';

// Названия тем
$lang['theme_name_default'] = 'Синий';
$lang['theme_name_emerald'] = 'Изумрудный';
$lang['theme_name_sunset'] = 'Закат';
$lang['theme_name_berry'] = 'Ягодный';
$lang['theme_name_night'] = 'Ночь';
$lang['theme_name_ocean'] = 'Океан';
$lang['theme_name_lavender'] = 'Лаванда';
$lang['theme_name_coffee'] = 'Кофе';
$lang['theme_name_mint'] = 'Мятный';
$lang['theme_name_gold'] = 'Золотой';
$lang['theme_name_black'] = 'Черный';
$lang['theme_name_custom'] = 'Свой цвет';



