<?php
$ci =& get_instance();
$ci->load->model('Task_model');
$user_id = $ci->session->userdata('user_id');
$active_session = $user_id ? $ci->Task_model->get_active_session($user_id) : null;
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
                        <div class="mr-8 flex space-x-6">
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
    <main class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Динамическая загрузка внутреннего представления с передачей всех данных -->
        <?php if (isset($inner_view)): ?>
            <?php $data['active_session'] = $active_session; ?>
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

        <!-- Плавающий глобальный виджет для других страниц -->
        <div id="globalFloatingWidget" class="hidden fixed z-[9999] bg-white rounded-full shadow-2xl border border-gray-200 p-2 pr-4 flex items-center gap-3 cursor-move hover:shadow-lg transition-shadow select-none" style="top: 80px; right: 20px;">
            <div class="drag-handle w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center cursor-grab active:cursor-grabbing text-xl">
                🟢
            </div>
            <div class="flex flex-col">
                <span id="globalWidgetTitle" class="text-xs font-bold text-gray-500 truncate max-w-[150px]">Task</span>
                <span id="globalWidgetTimer" class="text-lg font-mono font-bold text-emerald-600">00:00:00</span>
            </div>
            <button onclick="globalTogglePause()" id="globalWidgetPauseBtn" class="ml-2 bg-yellow-500 hover:bg-yellow-400 text-white w-10 h-10 rounded-full shadow-sm flex items-center justify-center font-bold text-xl transition-colors">
                ⏸
            </button>
        </div>
    <?php endif; ?>
</body>
