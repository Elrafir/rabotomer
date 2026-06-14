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
<body class="bg-gray-100 font-sans h-screen flex flex-col <?= htmlspecialchars($current_theme) ?>" style="<?= $inline_styles ?>">
    <!-- Верхняя навигационная панель -->
    <nav class="bg-blue-600 text-white shadow-md relative z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <a href="<?= site_url(); ?>" class="flex-shrink-0 flex items-center ajax-link hover:opacity-80 transition-opacity duration-300 py-1" onclick="event.preventDefault(); loadAjaxPage(this.href);">
                    <!-- Пользовательский логотип -->
                    <img src="<?= base_url('assets/img/logo4.png'); ?>" alt="Работомер" class="h-full w-auto object-contain rounded-xl shadow-sm">
                </a>
                <div class="flex items-center space-x-4">
                    <!-- Ссылки навигации, если пользователь авторизован -->
                    <?php if ($user_id): ?>
                        <div class="mr-8 flex items-center h-full space-x-2">
                            <!-- Кнопки добавления убрана в тело дашборда -->
                            <div class="mx-2 h-6 border-l border-blue-400"></div>
                            
                            <!-- Ссылки -->
                            <a href="<?= site_url('tasks'); ?>" class="text-xl font-bold flex items-center transition-all px-4 py-2 <?= $is_dashboard ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                <?= lang('nav_tasks'); ?>
                            </a>
                            <!--<div class="border-l border-white/30 transition-colors" style="height: 60%;"></div>
                            
                            <a href="<?= site_url('history'); ?>" class="text-xl font-bold flex items-center transition-all px-4 py-2 <?= current_url() == site_url('history') ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <?= lang('nav_history'); ?>
                            </a>-->
                            <div class="border-l border-white/30 transition-colors" style="height: 60%;"></div>
                            
                            <a href="<?= site_url('customers'); ?>" class="text-xl font-bold flex items-center transition-all px-4 py-2 <?= current_url() == site_url('customers') ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <?= lang('nav_customers'); ?>
                            </a>
                            <div class="border-l border-white/30 transition-colors" style="height: 60%;"></div>
                            
                            <?php 
                                $curr_u = current_url();
                                $is_stats_active = (strpos($curr_u, site_url('reports')) === 0 || strpos($curr_u, site_url('calculations')) === 0 || strpos($curr_u, site_url('history')) === 0);
                            ?>
                            <!-- Пункт меню "Калькуляция" удален из верхнего меню, так как он доступен через левую боковую панель -->
                            <a href="<?= site_url('reports'); ?>" class="text-xl whitespace-nowrap font-bold flex items-center transition-all px-4 py-2 <?= $is_stats_active ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                Статистика и расчёты
                            </a>
                            <div class="border-l border-white/30 transition-colors" style="height: 60%;"></div>
                            <a href="<?= site_url('tasks/trash'); ?>" class="text-xl font-bold flex items-center transition-all px-4 py-2 <?= current_url() == site_url('tasks/trash') ? 'opacity-100 nav-cloud-active' : 'opacity-70 hover:opacity-100' ?>">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Корзина
                            </a>
                        </div>
                        <?php $display_name = !empty($current_user_data['first_name']) ? $current_user_data['first_name'] : $ci->session->userdata('username'); ?>
                        
                        <!-- Theme Palette Button -->
                        <button onclick="openThemeModal()" class="flex items-center p-2 hover:bg-white/20 rounded-full transition-colors group mr-2" title="Настроить оформление">
                            <svg class="w-6 h-6 opacity-80 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                        </button>
                        
                        <!-- User Dropdown Menu -->
                        <div class="relative profile-dropdown-wrapper">
                            <button class="text-lg hover:bg-white/10 px-3 py-2 rounded-xl transition-colors flex items-center gap-2 cursor-pointer focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <?php echo htmlspecialchars($display_name); ?>
                                <svg class="w-4 h-4 ml-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            
                            <!-- Dropdown Container -->
                            <div class="absolute right-0 top-full pt-2 w-56 profile-dropdown-content" style="z-index: 99999;">
                                <div class="rounded-xl shadow-2xl border overflow-hidden text-white" style="background: linear-gradient(rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.15)), var(--theme-color-main) !important; backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-color: rgba(255,255,255,0.15) !important;">
                                    <?php if ($ci->session->userdata('group_id') == 1 || $ci->session->userdata('username') === 'root'): ?>
                                        <a href="<?= site_url('admin/users'); ?>" class="px-4 py-3 hover:bg-black/10 border-b border-white/10 text-sm font-semibold flex items-center gap-2 transition-colors text-white opacity-90 hover:opacity-100">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            <?= lang('nav_admin'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= site_url('profile'); ?>" class="px-4 py-3 hover:bg-black/10 border-b border-white/10 text-sm font-semibold flex items-center gap-2 ajax-link transition-colors text-white opacity-90 hover:opacity-100" onclick="event.preventDefault(); loadAjaxPage(this.href);">
                                        <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        Редактировать профиль
                                    </a>
                                    <a href="<?= site_url('auth/logout'); ?>" class="px-4 py-3 hover:bg-red-500/20 text-sm font-semibold flex items-center gap-2 transition-colors text-white opacity-90 hover:opacity-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        Выйти
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php if ($user_id): ?>
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
                hard_delete: '<?php echo site_url("tasks/hard_delete_ajax"); ?>'
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
        <div id="activeTimerPanel" class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-7xl bg-white border border-gray-200 rounded-t-2xl shadow-[0_-10px_40px_rgba(0,0,0,0.1)] transform transition-transform duration-300 z-50 translate-y-full">
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

        <!-- Глобальное модальное окно добавления проекта -->
        <div id="globalAddModal" onclick="closeGlobalAddModal()" class="hidden fixed inset-0 z-[100] bg-black bg-opacity-50 flex items-center justify-center p-4">
            <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-8 transform transition-all relative max-h-[90vh] flex flex-col overflow-y-auto">
                <button onclick="closeGlobalAddModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <h3 class="text-3xl font-black mb-8 text-gray-800"><?= lang('dash_new_project_title'); ?></h3>
                
                <?php echo form_open('tasks/add', ['class' => 'flex flex-col gap-6']); ?>
                    <div>
                        <input type="text" name="title" placeholder="<?= lang('dash_new_project_placeholder'); ?>" class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-xl text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
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
</body>
