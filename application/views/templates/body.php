<?php
$ci =& get_instance();
$ci->load->model('Task_model');
$ci->load->model('Customer_model');
$user_id = $ci->session->userdata('user_id');
$active_session = $user_id ? $ci->Task_model->get_active_session($user_id) : null;
$customers_global = $user_id ? $ci->Customer_model->get_all($user_id) : [];
$is_dashboard = (current_url() == site_url('tasks') || current_url() == site_url());
?>
<body class="bg-gray-100 font-sans h-screen flex flex-col">
    <!-- Верхняя навигационная панель -->
    <nav class="bg-blue-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex-shrink-0 flex items-center gap-2">
                    <!-- Логотип -->
                    <img src="<?= base_url('assets/img/clock-icon.svg'); ?>" alt="Logo" class="w-8 h-8 filter invert">
                    <!-- Крупный заголовок трекера в шапке -->
                    <span class="text-2xl font-bold">Тайм-трекер</span>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Ссылки навигации, если пользователь авторизован -->
                    <?php if ($user_id): ?>
                        <div class="mr-8 flex items-center space-x-6">
                            <!-- Кнопка добавления -->
                            <button onclick="openGlobalAddModal()" style="background-color: #22c55e;" class="flex items-center gap-2 hover:opacity-90 text-white font-black py-2 px-6 rounded-full text-lg shadow-lg transition-transform transform hover:scale-105 active:scale-95" title="<?= lang('btn_add'); ?>">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                                <?= lang('btn_add'); ?>
                            </button>
                            <!-- Разделитель -->
                            <div class="h-6 border-l border-blue-400"></div>
                            <a href="<?= site_url('tasks'); ?>" class="text-xl font-bold hover:text-blue-200 transition-colors <?= $is_dashboard ? 'text-blue-200 underline' : '' ?>"><?= lang('nav_tasks'); ?></a>
                            <a href="<?= site_url('history'); ?>" class="text-xl font-bold hover:text-blue-200 transition-colors <?= current_url() == site_url('history') ? 'text-blue-200 underline' : '' ?>"><?= lang('nav_history'); ?></a>
                            <a href="<?= site_url('customers'); ?>" class="text-xl font-bold hover:text-blue-200 transition-colors <?= current_url() == site_url('customers') ? 'text-blue-200 underline' : '' ?>"><?= lang('nav_customers'); ?></a>
                            <a href="<?= site_url('reports'); ?>" class="text-xl font-bold hover:text-blue-200 transition-colors <?= current_url() == site_url('reports') ? 'text-blue-200 underline' : '' ?>"><?= lang('nav_reports'); ?></a>
                            <?php if ($ci->session->userdata('username') === 'root'): ?>
                                <a href="<?= site_url('admin/users'); ?>" class="text-xl font-bold hover:text-blue-200 transition-colors text-purple-200 <?= current_url() == site_url('admin/users') ? 'underline' : '' ?>"><?= lang('nav_admin'); ?></a>
                            <?php endif; ?>
                        </div>
                        <span class="text-lg">Привет, <?php echo $ci->session->userdata('username'); ?></span>
                        <a href="<?php echo site_url('auth/logout'); ?>" class="px-4 py-2 bg-red-500 hover:bg-red-600 rounded text-lg transition-colors">Выйти</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Основной контейнер, в который вкладывается контент страницы -->
    <main id="main-content" class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Динамическая загрузка внутреннего представления с передачей всех данных -->
        <?php if (isset($inner_view)): ?>
            <?php $data['active_session'] = $active_session; ?>
            <?php $this->load->view('templates/flash_messages'); ?>
            <?php $this->load->view($inner_view, $data ?? []); ?>
        <?php else: ?>
            <p class="text-red-500">Ошибка: Внутреннее представление не задано.</p>
        <?php endif; ?>

    </main>

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
                edit_title: '<?php echo site_url("tasks/edit_title_ajax"); ?>',
                delete_task: '<?php echo site_url("tasks/delete_task_ajax"); ?>',
                set_color: '<?php echo site_url("tasks/set_color_ajax"); ?>',
                restore: '<?php echo site_url("tasks/restore_task_ajax"); ?>'
            };
            window.globalLang = {
                btn_pause: '<?= lang("btn_pause") ?>',
                btn_continue: '▶ Продолжить',
                js_prompt_stop_timer: '<?= lang("js_prompt_stop_timer") ?>',
                js_confirm_complete: '<?= lang("js_confirm_complete") ?>',
                js_confirm_delete: '<?= lang("js_confirm_delete") ?>',
                js_confirm_delete_task: '<?= lang("js_confirm_delete_task") ?>'
            };
            window.globalActiveSession = <?= $active_session ? json_encode($active_session) : 'null' ?>;
            window.isDashboardPage = <?= $is_dashboard ? 'true' : 'false' ?>;
        </script>

        <div id="globalFloatingWidgetContainer" class="hidden opacity-0 scale-50 pointer-events-none transition-all duration-500 ease-out fixed z-[9999] flex items-center select-none" style="top: 80px; right: 35px; touch-action: none; transform-origin: center right;">
            
            <!-- Кнопка полной остановки (появляется только на паузе) -->
            <button onclick="actionStopTimer();" onpointerdown="event.stopPropagation();" id="globalWidgetStopBtn" title="Завершить задачу" class="absolute top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-red-600 hover:bg-red-700 shadow-[0_5px_15px_rgba(220,38,38,0.5)] border border-red-400 text-white text-xl flex items-center justify-center opacity-0 transform scale-50 pointer-events-none transition-all duration-500 ease-out z-[-1]" style="left: -56px;">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none"><rect x="4" y="9" width="16" height="6" fill="white" rx="1"/></svg>
            </button>
            
            <div id="globalFloatingWidget" class="relative rounded-full widget-base-bg widget-glass-shadow px-7 py-3 flex items-center gap-4 cursor-pointer text-white transition-colors">
                <div id="globalWidgetIcon" class="w-8 h-8 flex-shrink-0 flex items-center justify-center widget-icon-bg rounded-full text-xl font-bold relative z-10">
                    <!-- Icon injected via JS -->
                </div>
                <div class="flex flex-col text-left min-w-[120px] relative z-10">
                    <span id="globalWidgetTitle" class="text-[11px] uppercase font-bold opacity-90 truncate max-w-[160px] drop-shadow-sm">Task</span>
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
        <div id="globalAddModal" class="hidden fixed inset-0 z-[100] bg-black bg-opacity-50 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-10 transform transition-all relative">
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
                            <?php if (!empty($customers_global)): ?>
                                <?php foreach ($customers_global as $c): ?>
                                    <option value="<?= $c['id']; ?>" data-rate="<?= $c['hourly_rate']; ?>"><?= htmlspecialchars($c['name'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        
                        <select name="is_fixed_price" class="px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none w-full sm:w-1/3">
                            <option value="0"><?= lang('finance_hourly'); ?></option>
                            <option value="1"><?= lang('finance_fixed'); ?></option>
                        </select>
                        
                        <input type="number" step="0.01" name="price" placeholder="<?= lang('finance_price'); ?>" class="rate-input w-full sm:w-32 px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
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
</body>
