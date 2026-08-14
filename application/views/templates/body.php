<?php
$ci =& get_instance();
$ci->load->model('Task_model');
$ci->load->model('Customer_model');
$ci->load->model('User_model');
$user_id = $ci->session->userdata('user_id');
$active_session = $user_id ? $ci->Task_model->get_active_session($user_id) : null;
$customers_global = $user_id ? $ci->Customer_model->get_all($user_id) : [];
$is_dashboard = (current_url() == site_url('tasks') || current_url() == site_url());
$current_user_data = $user_id ? $ci->User_model->get_user_by_id($user_id) : null;
?>
<?php 
// Получаем тему пользователя из переменной или сессии (по умолчанию 'theme-default')
$current_theme = isset($user_theme) ? $user_theme : ($ci->session->userdata('user_theme') ?: 'theme-default'); 
$current_opacity = isset($user_theme_opacity) ? $user_theme_opacity : ($ci->session->userdata('user_theme_opacity') ?: '1.00');
$current_hue = isset($user_custom_hue) ? $user_custom_hue : ($ci->session->userdata('user_custom_hue') ?: '221');

$inline_styles = "--theme-opacity: " . htmlspecialchars($current_opacity) . ";";
if ($current_theme === 'theme-custom') {
    $inline_styles .= " --theme-h: " . htmlspecialchars($current_hue) . ";";
}
?>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col <?= htmlspecialchars($current_theme) ?>" style="<?= $inline_styles ?>">
    <!-- Верхняя навигационная панель -->
    <nav id="main-nav" class="bg-blue-600 text-white shadow-md relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <a href="<?= site_url(); ?>" class="flex-shrink-0 flex items-center ajax-link hover:opacity-80 transition-opacity duration-300 py-1" onclick="event.preventDefault(); loadAjaxPage(this.href);">
                    <!-- Пользовательский логотип -->
                    <img src="<?= base_url('assets/img/logo4.png'); ?>" alt="Работомер" class="h-full w-auto object-contain rounded-xl shadow-sm">
                </a>
                <div class="flex items-center space-x-4">
                    <!-- Ссылки навигации, если пользователь авторизован -->
                    <?php if ($user_id): ?>
                        <div id="main-nav-links" class="mr-8 flex items-center h-full space-x-2 flex-shrink-0">
                            <!-- Кнопки добавления убрана в тело дашборда -->
                            <div class="mx-2 h-6 border-l border-blue-400"></div>
                            
                            <!-- Ссылки -->
                            <a href="<?= site_url('tasks'); ?>" title="<?= lang('nav_tasks') ?>" class="text-xl font-bold flex items-center transition-all px-3 py-2 <?= $is_dashboard ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <img src="<?= base_url('assets/img/icon_tasks.png') ?>" alt="Задачи" class="w-6 h-6 flex-shrink-0 object-contain" style="mix-blend-mode: multiply;">
                                <span class="nav-label ml-2"><?= lang('nav_tasks'); ?></span>
                            </a>
                            <div class="border-l border-white/30 transition-colors" style="height: 60%;"></div>
                            
                            <!--<a href="<?= site_url('history'); ?>" class="text-xl font-bold flex items-center transition-all px-4 py-2 <?= current_url() == site_url('history') ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <?= lang('nav_history'); ?>
                            </a>
                            <div class="border-l border-white/30 transition-colors" style="height: 60%;"></div>-->
                            
                            <a href="<?= site_url('customers'); ?>" title="<?= lang('nav_customers') ?>" class="text-xl font-bold flex items-center transition-all px-3 py-2 <?= current_url() == site_url('customers') ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <span class="flex-shrink-0 text-lg">🤝</span>
                                <span class="nav-label ml-2"><?= lang('nav_customers'); ?></span>
                            </a>
                            <div class="border-l border-white/30 transition-colors" style="height: 60%;"></div>
                            
                            <?php 
                                $curr_u = current_url();
                                $is_stats_active = (strpos($curr_u, site_url('reports')) === 0 || strpos($curr_u, site_url('calculations')) === 0 || strpos($curr_u, site_url('history')) === 0);
                            ?>
                            <!-- Пункт меню "Калькуляция" удален из верхнего меню, так как он доступен через левую боковую панель -->
                            <a href="<?= site_url('reports'); ?>" title="Статистика и расчёты" class="text-xl font-bold flex items-center transition-all px-3 py-2 <?= $is_stats_active ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <span class="flex-shrink-0 text-lg">📊</span>
                                <span class="nav-label ml-2">Статистика и расчёты</span>
                            </a>
                            <div class="border-l border-white/30 transition-colors" style="height: 60%;"></div>
                            <a href="<?= site_url('tasks/trash'); ?>" title="Корзина" class="text-xl font-bold flex items-center transition-all px-3 py-2 <?= current_url() == site_url('tasks/trash') ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <span class="flex-shrink-0 text-lg">🗑️</span>
                                <span class="nav-label ml-2">Корзина</span>
                            </a>
                        </div>
                        <?php $display_name = !empty($current_user_data['first_name']) ? $current_user_data['first_name'] : $ci->session->userdata('username'); ?>
                        
                        <!-- Контейнер проверки/скачивания обновлений приложения -->
                        <div id="appUpdateHeaderContainer" class="flex items-center mr-2"></div>

                        <!-- Кнопка настройки оформления -->
                        <button onclick="openThemeModal()" class="flex items-center p-2 hover:bg-white/20 rounded-full transition-colors group mr-2" title="Настроить оформление">
                            <span class="text-xl opacity-80 group-hover:opacity-100 transition-opacity">🎨</span>
                        </button>
                        
                        <!-- User Dropdown Menu -->
                        <div class="relative profile-dropdown-wrapper">
                            <!-- Кнопка профиля с аватаркой по полу -->
                            <?php
                                $gender = $current_user_data['gender'] ?? 'not_specified';
                                $avatar_src = ($gender === 'female')
                                    ? base_url('assets/img/avatar_female.png')
                                    : (($gender === 'male')
                                        ? base_url('assets/img/avatar_male.png')
                                        : base_url('assets/img/avatar_male.png')); // по умолч. мужской
                            ?>
                            <button class="hover:bg-white/10 p-1 rounded-full transition-colors flex items-center justify-center cursor-pointer focus:outline-none" title="Профиль">
                                <img src="<?= $avatar_src ?>" alt="Профиль" class="w-8 h-8 rounded-full object-cover object-top opacity-90 hover:opacity-100 transition-opacity">
                            </button>
                            
                            <!-- Dropdown Container -->
                            <div class="absolute right-0 top-full pt-2 w-56 profile-dropdown-content">
                                <div class="rounded-xl shadow-2xl border overflow-hidden text-white" style="background: linear-gradient(rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.15)), var(--theme-color-main) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-color: rgba(255,255,255,0.15) !important;">
                                    <?php if ($ci->session->userdata('group_id') == 1 || $ci->session->userdata('username') === 'root'): ?>
                                        <a href="<?= site_url('admin/users'); ?>" class="px-4 py-3 hover:bg-black/10 border-b border-white/10 text-sm font-semibold flex items-center gap-2 transition-colors text-white opacity-90 hover:opacity-100">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            <?= lang('nav_admin'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= site_url('admin/profile'); ?>" class="px-4 py-3 hover:bg-black/10 border-b border-white/10 text-sm font-semibold flex items-center gap-2 transition-colors text-white opacity-90 hover:opacity-100">
                                        <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        Личный кабинет
                                    </a>
                                    <button onclick="if(window.goToSetupScreen){window.goToSetupScreen();}else{window.location.href='<?= site_url('MobileApp/reset_setup'); ?>';}" class="w-full text-left px-4 py-3 hover:bg-black/10 border-b border-white/10 text-sm font-semibold flex items-center gap-2 transition-colors text-white opacity-90 hover:opacity-100">
                                        <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"></path></svg>
                                        Выбор сервера
                                    </button>
                                    <a href="<?= site_url('auth/logout'); ?>" class="px-4 py-3 hover:bg-red-500/20 text-sm font-semibold flex items-center gap-2 transition-colors text-white opacity-90 hover:opacity-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        Выйти
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Кнопка авторизации для неавторизованного посетителя -->
                        <a href="<?= site_url('auth/login'); ?>" class="text-xl font-bold flex items-center transition-all px-4 py-2 opacity-70 hover:opacity-100 ajax-link" onclick="event.preventDefault(); loadAjaxPage(this.href);">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Войти
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php if ($user_id): 
        $ci->load->model('Settings_model');
        $per_page_global = (int)$ci->Settings_model->get_setting('per_page', 25);
    ?>
        <!-- Глобальные настройки JS -->
        <script>
            window.globalApi = {
                start: '<?php echo site_url("tasks/start_timer_ajax"); ?>',
                stop: '<?php echo site_url("tasks/stop_timer_ajax"); ?>',
                pause: '<?php echo site_url("tasks/pause_timer_ajax"); ?>',
                complete: '<?php echo site_url("tasks/complete_ajax"); ?>',
                get_sessions: '<?php echo site_url("tasks/get_sessions_ajax"); ?>',
                get_cascading: '<?php echo site_url("tasks/get_cascading_history_ajax"); ?>',
                add_manual: '<?php echo site_url("tasks/add_manual_ajax"); ?>',
                delete_session: '<?php echo site_url("tasks/delete_session_ajax"); ?>',
                edit_session: '<?php echo site_url("tasks/edit_session_ajax"); ?>',
                edit_title: '<?php echo site_url("tasks/edit_title_ajax"); ?>',
                delete_task: '<?php echo site_url("tasks/delete_task_ajax"); ?>',
                set_color: '<?php echo site_url("tasks/set_color_ajax"); ?>',
                restore: '<?php echo site_url("tasks/restore_task_ajax"); ?>',
                restore_trash: '<?php echo site_url("tasks/restore_from_trash_ajax"); ?>',
                hard_delete: '<?php echo site_url("tasks/hard_delete_ajax"); ?>',
                search_tasks: '<?php echo site_url("calculations/search_tasks_ajax"); ?>',
                add_calculation_task: '<?php echo site_url("calculations/add_task_ajax"); ?>',
                update_calculation_task: '<?php echo site_url("calculations/update_task_ajax"); ?>',
                delete_calculation_task: '<?php echo site_url("calculations/delete_task_ajax"); ?>',
                analytics_data: '<?php echo site_url("analytics/get_data_ajax"); ?>',
                sync_session: '<?php echo site_url("tasks/sync_active_session_ajax"); ?>',
                get_timeline: '<?php echo site_url("tasks/get_timeline_ajax"); ?>',
                remove_calculation_task: '<?php echo site_url("calculations/remove_task_ajax"); ?>',
                delete_spec_file: '<?php echo site_url("customers/delete_file/"); ?>',
                upload_file: '<?php echo site_url("customers/upload_file"); ?>',
                add_link: '<?php echo site_url("customers/add_link_ajax"); ?>',
                download_url: '<?php echo site_url("customers/download_from_url_ajax"); ?>',
                load_more_customers: '<?php echo site_url("customers/load_more_ajax"); ?>',
                load_customer_tasks: '<?php echo site_url("customers/load_tasks_ajax"); ?>',
                load_more_tasks: '<?php echo site_url("tasks/load_more_tasks_ajax"); ?>',
                load_more_history: '<?php echo site_url("history/load_more_history_ajax"); ?>',
                upload_editor_image: '<?php echo site_url("customers/upload_editor_image_ajax"); ?>',
                heartbeat: '<?php echo site_url("tasks/heartbeat_ajax"); ?>',
                resolve_gap: '<?php echo site_url("tasks/resolve_gap_ajax"); ?>',
                sync_offline: '<?php echo site_url("tasks/sync_offline_actions_ajax"); ?>'
            };
            window.globalLang = {
                btn_pause: '<?= lang("btn_pause") ?>',
                btn_continue: '▶ Продолжить',
                js_prompt_stop_timer: '<?= lang("js_prompt_stop_timer") ?>',
                js_confirm_complete: '<?= lang("js_confirm_complete") ?>',
                js_confirm_delete: '<?= lang("js_confirm_delete") ?>',
                js_confirm_delete_task: '<?= lang("js_confirm_delete_task") ?>',
                js_confirm_restore: 'Восстановить задачу и все её подзадачи?',
                js_confirm_hard_delete: 'ВНИМАНИЕ! Задача и все её подзадачи будут удалены НАВСЕГДА. Продолжить?'
            };
            window.globalActiveSession = <?= $active_session ? json_encode($active_session) : 'null' ?>;
            window.isDashboardPage = <?= $is_dashboard ? 'true' : 'false' ?>;
            window.globalPerPage = <?= $per_page_global ?>;
            window.globalIsAdmin = <?= !empty($is_admin) ? 'true' : 'false' ?>;
        </script>
    <?php endif; ?>

    <!-- Основной контейнер, разделенный на 3 колонки (Левая, Центр, Правая) -->
    <main id="main-content" class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-4 flex flex-col md:flex-row gap-6 items-start">
        <?php $this->load->view('templates/content_wrapper', $data ?? []); ?>
    </main>

    <?php if ($user_id): ?>
        <div id="globalFloatingWidgetContainer" class="hidden opacity-0 scale-50 pointer-events-none transition-all duration-500 ease-out fixed flex items-center select-none" style="z-index: 40; top: 80px; right: 35px; touch-action: none; transform-origin: center right;">
            
            <!-- Кнопка полной остановки (появляется только на паузе) -->
            <button onclick="actionStopTimer();" onpointerdown="event.stopPropagation();" id="globalWidgetStopBtn" title="Завершить текущую сессию" class="absolute top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-red-600 hover:bg-red-700 shadow-[0_5px_15px_rgba(220,38,38,0.5)] border-2 border-red-500 flex items-center justify-center opacity-0 transform scale-50 pointer-events-none transition-all duration-500 ease-out z-[-1]" style="left: -56px;">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path><rect x="9" y="9" width="6" height="6" fill="currentColor"></rect></svg>
            </button>
            
            <div id="globalFloatingWidget" class="relative rounded-full widget-base-bg widget-glass-shadow px-7 py-3 flex items-center gap-4 cursor-pointer text-white transition-colors overflow-hidden max-w-full">
                <div id="globalWidgetIcon" class="w-8 h-8 flex-shrink-0 flex items-center justify-center widget-icon-bg rounded-full text-xl font-bold relative z-10">
                    <!-- Icon injected via JS -->
                </div>
                <div class="flex flex-col text-left min-w-[100px] flex-1 overflow-hidden relative z-10">
                    <span id="globalWidgetTitle" class="text-[11px] uppercase font-bold opacity-90 truncate overflow-hidden text-ellipsis whitespace-nowrap drop-shadow-sm max-w-[120px]" title="Title">Task</span>
                    <span id="globalWidgetTimer" class="text-xl font-mono font-black leading-tight tracking-wider drop-shadow-md">00:00:00</span>
                </div>
            </div>
        </div>
        
        <!-- Плавающая панель активного таймера (спрятана по умолчанию) -->
        <div id="activeTimerPanel" class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-7xl bg-white border border-gray-200 rounded-t-2xl shadow-[0_-10px_40px_rgba(0,0,0,0.1)] transform transition-transform duration-300 z-[100] translate-y-full">
            <!-- Кнопка "Свернуть" -->
            <button id="btnCollapseTimerPanel" onclick="hideTimerPanel()" class="hidden absolute -top-10 right-4 bg-white px-4 py-1 rounded-t-lg shadow-sm border border-b-0 border-gray-200 text-gray-500 hover:text-gray-700 hover:bg-gray-50 text-sm font-bold flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                свернуть
            </button>
            <div class="px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
                
                <div class="flex flex-row items-center gap-6 flex-grow overflow-hidden">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-gray-500 text-sm font-bold uppercase whitespace-nowrap"><?= lang('dash_timer_in_progress'); ?></span>
                        <span id="activeTimerTitle" class="text-xl font-black text-emerald-600 truncate max-w-sm md:max-w-md lg:max-w-lg">
                            <!-- Title updated via JS -->
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-4 ml-auto">
                        <div class="flex flex-col items-end">
                            <div class="text-gray-500 text-xs font-bold uppercase"><?= lang('dash_timer_total'); ?></div>
                            <div id="totalTimerDisplay" class="text-green-600 text-2xl font-mono leading-none">00:00:00</div>
                        </div>
                        <div class="h-8 border-l border-gray-300 hidden sm:block"></div>
                        <div class="flex flex-col items-end hidden sm:flex">
                            <div class="text-gray-500 text-xs font-bold uppercase"><?= lang('dash_timer_current_session'); ?></div>
                            <div id="timerDisplay" class="text-red-600 text-xl font-mono leading-none">00:00:00</div>
                        </div>
                    </div>
                    
                    <input type="hidden" id="activeTimerTotal" value="<?php echo $active_session ? (int)($active_session['total_accumulated'] ?? 0) : '0'; ?>">
                    <input type="hidden" id="activeTimerElapsed" value="<?php echo $active_session ? (int)($active_session['current_elapsed'] ?? 0) : '0'; ?>">
                </div>
                
                <div class="flex items-center gap-3 ml-auto flex-shrink-0">
                    <button id="btnPauseDashboard" onclick="globalTogglePause()" class="bg-yellow-500 hover:bg-yellow-400 text-white font-black py-3 px-6 rounded-full text-xl shadow-sm transition-all transform hover:scale-105 active:scale-95 whitespace-nowrap">
                        <?= lang('btn_pause'); ?>
                    </button>
                    <button onclick="actionStopTimer()" class="bg-red-600 hover:bg-red-500 text-white font-black py-3 px-8 rounded-full text-xl shadow-sm transition-all transform hover:scale-105 active:scale-95 whitespace-nowrap">
                        <?= lang('btn_stop'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Глобальное модальное окно добавления проекта с z-index фиксом -->
        <div id="globalAddModal" onclick="closeGlobalAddModal()" class="hidden fixed inset-0 z-[99999] bg-black bg-opacity-50 flex items-center justify-center p-4">
            <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-8 transform transition-all relative max-h-[90vh] flex flex-col overflow-y-auto">
                <button onclick="closeGlobalAddModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <h3 class="text-3xl font-black mb-8 text-gray-800"><?= lang('dash_new_project_title'); ?></h3>
                
                <?php echo form_open('tasks/add', ['class' => 'flex flex-col gap-6']); ?>
                    <div>
                        <input type="text" name="title" placeholder="<?= lang('dash_new_project_placeholder'); ?>" class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-xl text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    </div>
                    
                    <div>
                        <textarea name="description" placeholder="Детальное описание задачи / проекта..." class="w-full h-32 px-5 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"></textarea>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <select name="customer_id" class="customer-select flex-1 px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="updateRateGlobal(this)">
                            <option value=""><?= lang('finance_no_customer'); ?></option>
                            <option value="new" class="font-bold text-blue-600">+ Добавить нового клиента</option>
                            <?php if (!empty($customers_global)): ?>
                                <?php foreach ($customers_global as $c): ?>
                                    <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        
                        <div id="globalAddSpecContainer" class="hidden flex-1 flex flex-col">
                            <select name="spec_id" class="spec-select w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <option value="">Связать с ТЗ...</option>
                            </select>
                        </div>
                        
                        <div id="newCustomerFields" class="hidden flex gap-4 w-full sm:w-auto">
                            <input type="text" name="new_customer_name" placeholder="Имя клиента" class="flex-1 sm:w-48 px-4 py-4 bg-white border border-blue-300 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm">
                            <input type="text" name="new_customer_notes" placeholder="Заметки" class="w-full sm:w-48 px-4 py-4 bg-white border border-blue-300 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm">
                        </div>
                        
                        <select name="is_fixed_price" id="globalAddIsFixed" class="px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none w-full sm:w-1/3">
                            <option value="0"><?= lang('finance_hourly'); ?></option>
                            <option value="1"><?= lang('finance_fixed'); ?></option>
                        </select>
                        
                        <input type="number" step="0.01" name="price" id="globalAddPrice" placeholder="<?= lang('finance_price'); ?>" class="rate-input w-full sm:w-32 px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    
                    <div class="flex gap-4 mt-4">
                        <button type="submit" class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-4 px-8 rounded-xl text-xl shadow-lg transition-transform hover:-translate-y-1">
                            <?= lang('btn_add'); ?>
                        </button>
                        <button type="button" onclick="closeGlobalAddModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-4 px-8 rounded-xl text-xl transition-colors">
                            <?= lang('btn_cancel'); ?>
                        </button>
                    </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Глобальное плавающее модальное окно тем оформления -->
    <div id="themeSettingsModal" class="hidden opacity-0 fixed top-20 right-8 w-72 bg-white/95 backdrop-blur-xl shadow-2xl rounded-2xl border border-white/40 z-[9999] overflow-hidden flex flex-col transition-opacity duration-200 theme-modal-base">
        <!-- Шапка (Draggable) -->
        <div id="themeSettingsModalHeader" class="bg-gray-100/50 p-3 flex justify-between items-center cursor-move border-b border-gray-200/50">
            <span class="text-sm font-bold text-gray-700 flex items-center gap-2 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                Цвет интерфейса
            </span>
            <button onclick="closeThemeModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <!-- Содержимое (Минималистичное) -->
        <div class="p-4 flex flex-col gap-4">
            <!-- Кнопки пресетов -->
            <div class="grid grid-cols-6 gap-2">
                <button type="button" data-theme="theme-default" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-default' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #2563eb;" title="Синий"></button>
                <button type="button" data-theme="theme-emerald" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-emerald' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #059669;" title="Изумрудный"></button>
                <button type="button" data-theme="theme-sunset" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-sunset' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #ea580c;" title="Закат"></button>
                <button type="button" data-theme="theme-berry" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-berry' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #e11d48;" title="Ягодный"></button>
                <button type="button" data-theme="theme-night" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-night' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #475569;" title="Ночь"></button>
                <button type="button" data-theme="theme-ocean" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-ocean' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #0891b2;" title="Океан"></button>
                <button type="button" data-theme="theme-lavender" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-lavender' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #9333ea;" title="Лаванда"></button>
                <button type="button" data-theme="theme-coffee" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-coffee' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #d97706;" title="Кофе"></button>
                <button type="button" data-theme="theme-mint" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-mint' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #10b981;" title="Мятный"></button>
                <button type="button" data-theme="theme-gold" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-gold' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #facc15;" title="Золотой"></button>
                <button type="button" data-theme="theme-black" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-black' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background-color: #1e293b;" title="Черный"></button>
                <button type="button" data-theme="theme-custom" class="theme-selector w-8 h-8 rounded-full shadow-sm border <?= $current_theme == 'theme-custom' ? 'border-gray-800 ring-2' : 'border-transparent hover:scale-110' ?>" style="background: conic-gradient(red, yellow, lime, cyan, blue, magenta, red);" title="Свой цвет"></button>
            </div>

            <!-- Насыщенность -->
            <div class="mt-2">
                <div class="flex justify-between text-xs text-gray-500 mb-1 font-bold">
                    <span>Плотность фона</span>
                    <span id="opacity_value"><?= round((float)$current_opacity * 100) ?>%</span>
                </div>
                <input type="range" id="theme_opacity" name="theme_opacity" min="0.05" max="1.00" step="0.01" value="<?= html_escape($current_opacity) ?>" class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer">
            </div>

            <!-- Кастомный цвет (Hue) -->
            <div id="custom_hue_container" class="<?= $current_theme == 'theme-custom' ? '' : 'hidden' ?> mt-1">
                <div class="flex justify-between text-xs text-gray-500 mb-1 font-bold">
                    <span>Тон (Hue)</span>
                    <span id="hue_value"><?= html_escape($current_hue) ?>&deg;</span>
                </div>
                <input type="range" id="theme_hue" name="theme_hue" min="0" max="360" step="1" value="<?= html_escape($current_hue) ?>" class="w-full h-1.5 rounded-lg appearance-none cursor-pointer" style="background: linear-gradient(to right, #ff0000 0%, #ffff00 17%, #00ff00 33%, #00ffff 50%, #0000ff 67%, #ff00ff 83%, #ff0000 100%);">
            </div>
        </div>
        </div>
    </div>

    <!-- Модальное окно при обрыве пульса (Gap Resolution) -->
    <div id="gapModal" class="hidden fixed inset-0 z-[999999] bg-black bg-opacity-70 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8 transform transition-all relative">
            <h3 class="text-2xl font-bold mb-4 text-red-600 flex items-center gap-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                Обрыв связи
            </h3>
            
            <p class="text-gray-700 mb-6 text-lg" id="gapModalText"></p>
            
            <div class="space-y-3">
                <button onclick="resolveGap('keep')" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors flex justify-between items-center">
                    <span>Оставить время</span>
                    <span class="text-sm font-normal opacity-80">(Продолжить таймер)</span>
                </button>
                <button onclick="resolveGap('pause')" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors flex justify-between items-center">
                    <span>Поставить на паузу</span>
                    <span class="text-sm font-normal opacity-80">(С момента обрыва)</span>
                </button>
                <button onclick="resolveGap('stop')" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-colors flex justify-between items-center">
                    <span>Остановить полностью</span>
                    <span class="text-sm font-normal opacity-80">(С момента обрыва)</span>
                </button>
            </div>
        </div>
    </div>
</body>
