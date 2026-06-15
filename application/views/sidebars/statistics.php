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

<?php
// Проверяем, находимся ли мы на страницах временного или проектного среза статистики
if (isset($active_sub_page) && in_array($active_sub_page, ['time_slice', 'project_slice'])): 
?>
    <!-- Блок боковых фильтров, отображаемый только в разделах отчетов -->
    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 mt-4">
        <!-- Заголовок секции фильтрации -->
        <h3 class="font-black text-gray-800 text-base mb-4">Фильтры</h3>
        
        <!-- Контейнер со списком всех фильтров, разделенных вертикальными отступами -->
        <div class="flex flex-col gap-6">
            
            <!-- Секция опций отображения (архивные задачи) -->
            <div class="flex flex-col gap-2">
                <!-- Подзаголовок секции опций -->
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Опции</span>
                
                <!-- Метка с чекбоксом для скрытия архивных проектов -->
                <label class="flex items-center gap-2 cursor-pointer mt-1 group">
                    <!-- Чекбокс фильтрации "Без архивных" (показывает только активные проекты) -->
                    <input type="checkbox" id="filter-archive-active" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($show_archived) && $show_archived === 'active') ? 'checked' : ''; ?>>
                    <!-- Текстовое описание опции -->
                    <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">Без архивных</span>
                </label>
                
                <!-- Метка с чекбоксом для показа только архивных проектов -->
                <label class="flex items-center gap-2 cursor-pointer group">
                    <!-- Чекбокс фильтрации "Только архивные" (скрывает все активные проекты) -->
                    <input type="checkbox" id="filter-archive-archived" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($show_archived) && $show_archived === 'archived') ? 'checked' : ''; ?>>
                    <!-- Текстовое описание опции -->
                    <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">Только архивные</span>
                </label>
            </div>
            
            <!-- Секция фильтрации по заказчикам -->
            <div class="flex flex-col gap-2">
                <!-- Подзаголовок секции заказчиков -->
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Заказчики</span>
                <!-- Прокручиваемый контейнер со списком заказчиков -->
                <div class="max-h-40 overflow-y-auto space-y-1.5 pr-2">
                    <!-- Вариант фильтрации "Без заказчика" -->
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <!-- Чекбокс выбора задач без привязанного заказчика -->
                        <input type="checkbox" name="customer_filters[]" value="none" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($customer_filters) && in_array('none', $customer_filters)) ? 'checked' : ''; ?>>
                        <!-- Подпись для пустого заказчика -->
                        <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">Без заказчика</span>
                    </label>
                    <!-- Проход циклом по списку доступных заказчиков пользователя -->
                    <?php if (!empty($customers)): foreach($customers as $c): ?>
                        <!-- Метка для конкретного заказчика -->
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <!-- Чекбокс конкретного заказчика -->
                            <input type="checkbox" name="customer_filters[]" value="<?= $c['id']; ?>" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($customer_filters) && in_array((string)$c['id'], $customer_filters)) ? 'checked' : ''; ?>>
                            <!-- Название заказчика -->
                            <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors"><?= htmlspecialchars($c['name']); ?></span>
                        </label>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Секция фильтрации по калькуляциям -->
            <div class="flex flex-col gap-2">
                <!-- Подзаголовок секции калькуляций -->
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Калькуляция</span>
                <!-- Прокручиваемый список калькуляций -->
                <div class="max-h-40 overflow-y-auto space-y-1.5 pr-2">
                    <!-- Вариант фильтрации "Вне калькуляций" -->
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <!-- Чекбокс выбора задач, не входящих ни в одну калькуляцию -->
                        <input type="checkbox" name="calculation_filters[]" value="none" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($calculation_filters) && in_array('none', $calculation_filters)) ? 'checked' : ''; ?>>
                        <!-- Текст для задач вне калькуляций -->
                        <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">Вне калькуляций</span>
                    </label>
                    <!-- Проход циклом по списку доступных пакетов калькуляции -->
                    <?php if (!empty($calculations)): foreach($calculations as $calc): ?>
                        <!-- Метка для конкретного пакета калькуляции -->
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <!-- Чекбокс конкретного пакета калькуляции -->
                            <input type="checkbox" name="calculation_filters[]" value="<?= $calc['id']; ?>" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($calculation_filters) && in_array((string)$calc['id'], $calculation_filters)) ? 'checked' : ''; ?>>
                            <!-- Название калькуляции -->
                            <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors"><?= htmlspecialchars($calc['title']); ?></span>
                        </label>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Секция фильтрации по техническим заданиям (ТЗ) -->
            <div class="flex flex-col gap-2">
                <!-- Подзаголовок секции технических заданий -->
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Техзадания (ТЗ)</span>
                <!-- Прокручиваемый список ТЗ -->
                <div class="max-h-40 overflow-y-auto space-y-1.5 pr-2">
                    <!-- Вариант фильтрации "Вне ТЗ" -->
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <!-- Чекбокс выбора задач, не привязанных к ТЗ -->
                        <input type="checkbox" name="spec_filters[]" value="none" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($spec_filters) && in_array('none', $spec_filters)) ? 'checked' : ''; ?>>
                        <!-- Текст для задач вне ТЗ -->
                        <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">Вне ТЗ</span>
                    </label>
                    <!-- Проход циклом по списку доступных технических заданий -->
                    <?php if (!empty($specs)): foreach($specs as $spec): ?>
                        <!-- Метка для конкретного ТЗ -->
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <!-- Чекбокс конкретного ТЗ -->
                            <input type="checkbox" name="spec_filters[]" value="<?= $spec['id']; ?>" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($spec_filters) && in_array((string)$spec['id'], $spec_filters)) ? 'checked' : ''; ?>>
                            <!-- Название ТЗ с указанием имени заказчика в скобках -->
                            <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors"><?= htmlspecialchars($spec['title']); ?> <span class="text-[10px] text-gray-400 font-medium">(<?= htmlspecialchars($spec['customer_name']); ?>)</span></span>
                        </label>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
