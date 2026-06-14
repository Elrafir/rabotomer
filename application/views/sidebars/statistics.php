<!-- Левая панель навигации для разделов статистики и калькуляций -->
<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden sticky top-4">
    <!-- Шапка панели навигации -->
    <div class="p-6 border-b border-gray-50 bg-gray-50">
        <!-- Заголовок панели "Статистика и расчеты" из файла локализации -->
        <h3 class="font-black text-gray-800 text-lg"><?= lang('nav_stats_calc'); ?></h3>
    </div>
    <!-- Блок ссылок навигации -->
    <nav class="flex flex-col p-2 gap-1">
        <?php
        // Проверяем, активна ли вкладка "Временной срез" (time_slice)
        $is_time_active = (isset($active_sub_page) && $active_sub_page === 'time_slice');
        
        // Формируем CSS классы для временного среза (синяя плашка если активен, серые цвета при наведении если нет)
        $time_classes = $is_time_active ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
        ?>
        <!-- Ссылка на подраздел "Временной срез" -->
        <a href="<?= site_url('reports/time_slice') ?>" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= $time_classes; ?>">
            <!-- Иконка часов для временного среза -->
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <!-- Текст ссылки на русском языке -->
            Временной срез
        </a>

        <?php
        // Проверяем, активна ли вкладка "Проектный срез" (project_slice)
        $is_project_active = (isset($active_sub_page) && $active_sub_page === 'project_slice');
        
        // Формируем CSS классы для проектного среза
        $project_classes = $is_project_active ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
        ?>
        <!-- Ссылка на подраздел "Проектный срез" -->
        <a href="<?= site_url('reports/project_slice') ?>" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= $project_classes; ?>">
            <!-- Иконка структуры/папки для проектного среза -->
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14"></path></svg>
            <!-- Текст ссылки на русском языке -->
            Проектный срез
        </a>
        
        <?php
        // Проверяем, открыт ли раздел "Калькуляции" (поиск вхождения в URL)
        $is_calc_active = (strpos(current_url(), site_url('calculations')) === 0);
        
        // Стилизуем пункт "Калькуляция" в зависимости от активности
        $calc_classes = $is_calc_active ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
        ?>
        <!-- Стандартная ссылка на раздел Калькуляций -->
        <a href="<?= site_url('calculations') ?>" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= $calc_classes; ?>">
            <!-- Иконка калькулятора -->
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            <!-- Название раздела из файла локализации -->
            <?= lang('nav_calculations'); ?>
        </a>
        
        <?php
        // Проверяем, открыт ли раздел "История" (Журнал сессий)
        $is_history_active = (current_url() == site_url('history'));
        
        // Классы стилей для Журнала сессий
        $history_classes = $is_history_active ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
        ?>
        <!-- Стандартная ссылка на Журнал сессий -->
        <a href="<?= site_url('history') ?>" class="flex items-center gap-3 px-4 py-3 rounded-2xl transition-colors <?= $history_classes; ?>">
            <!-- Иконка журнала/истории -->
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <!-- Название раздела из локализации -->
            <?= lang('nav_history'); ?>
        </a>
    </nav>
</div>
