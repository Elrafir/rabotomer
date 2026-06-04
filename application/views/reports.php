<!-- application/views/reports.php -->
<div class="max-w-6xl mx-auto min-h-[80vh] pb-32">
    
    <!-- Заголовок страницы -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-black text-gray-800"><?= lang('reports_title'); ?></h1>
    </div>

    <!-- Компактная панель фильтров -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="flex flex-wrap justify-between items-center gap-4">
            <!-- Кнопки быстрого выбора -->
            <div class="flex gap-2">
                <button onclick="setFilter('today')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold py-2 px-4 rounded-lg transition-colors">
                    <?= lang('reports_filter_today'); ?>
                </button>
                <button onclick="setFilter('yesterday')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold py-2 px-4 rounded-lg transition-colors">
                    <?= lang('reports_filter_yesterday'); ?>
                </button>
                <button onclick="setFilter('week')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold py-2 px-4 rounded-lg transition-colors">
                    <?= lang('reports_filter_week'); ?>
                </button>
                <button onclick="setFilter('month')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold py-2 px-4 rounded-lg transition-colors">
                    <?= lang('reports_filter_month'); ?>
                </button>
            </div>

            <!-- Форма ручного выбора дат -->
            <?php echo form_open('reports', ['method' => 'GET', 'class' => 'flex items-center gap-4', 'id' => 'filterForm']); ?>
                <div class="flex items-center gap-2">
                    <span class="text-gray-600 text-sm font-bold"><?= lang('reports_lbl_from'); ?></span>
                    <input type="date" id="dateStart" name="start" value="<?= htmlspecialchars($start_date ?? ''); ?>" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-gray-600 text-sm font-bold"><?= lang('reports_lbl_to'); ?></span>
                    <input type="date" id="dateEnd" name="end" value="<?= htmlspecialchars($end_date ?? ''); ?>" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg text-sm shadow-sm transition-colors">
                    <?= lang('reports_btn_show'); ?>
                </button>
            <?php echo form_close(); ?>
        </div>
    </div>

    <!-- Общие показатели (Суммарное время и Заработок) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-blue-600 p-6 rounded-2xl text-white shadow-md flex justify-between items-center">
            <div>
                <span class="text-blue-200 text-sm font-bold uppercase tracking-wider block mb-1"><?= lang('reports_total_time'); ?></span>
                <span class="text-3xl font-black"><?= $total_time_formatted; ?></span>
            </div>
            <div class="text-4xl opacity-50">⏱️</div>
        </div>
        <div class="bg-green-600 p-6 rounded-2xl text-white shadow-md flex justify-between items-center">
            <div>
                <span class="text-green-200 text-sm font-bold uppercase tracking-wider block mb-1"><?= lang('finance_earned'); ?></span>
                <span class="text-3xl font-black"><?= number_format(isset($total_earned) ? $total_earned : 0, 2, '.', ' '); ?></span>
            </div>
            <div class="text-4xl opacity-50">💰</div>
        </div>
    </div>

    <!-- Вкладки (Tabs) для переключения между "Затраченное время" и "Архив" -->
    <div class="flex border-b border-gray-200 mb-6">
        <button onclick="switchTab('time')" id="tab-time" class="py-2 px-6 text-sm font-bold text-blue-600 border-b-2 border-blue-600">
            <?= lang('reports_tab_time'); ?> (<?= $total_time_formatted; ?>)
        </button>
        <button onclick="switchTab('archive')" id="tab-archive" class="py-2 px-6 text-sm font-bold text-gray-500 border-b-2 border-transparent hover:text-gray-700">
            <?= lang('reports_tab_archive'); ?>
        </button>
    </div>

    <!-- Вкладка: Время за период (Группировка по дням) -->
    <div id="content-time">
        <?php if (empty($grouped_data)): ?>
            <div class="bg-white p-8 rounded-xl border border-gray-100 text-center shadow-sm">
                <span class="text-4xl mb-2 block">📭</span>
                <p class="text-lg text-gray-500">За выбранный период активности не найдено.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($grouped_data as $date => $group): ?>
                    <div>
                        <h3 class="text-sm font-black text-gray-500 mb-2 uppercase tracking-wider pl-2">
                            <?= lang('reports_date'); ?> <?= $group['date_formatted']; ?>
                        </h3>
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <table class="w-full text-left border-collapse text-sm">
                                <tbody class="divide-y divide-gray-50">
                                    <?php foreach ($group['tasks'] as $row): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="py-2 px-4 w-6">
                                                <!-- Цветная метка задачи -->
                                                <div class="w-3 h-3 rounded-full shadow-sm border border-gray-200" style="background-color: <?= $row['color'] ? $row['color'] : '#e5e7eb'; ?>"></div>
                                            </td>
                                            <td class="py-2 px-2 font-medium text-gray-800">
                                                <?= htmlspecialchars($row['title'] ?? ''); ?>
                                                <?php if (!empty($row['customer_name'])): ?>
                                                    <span class="text-xs text-gray-400 ml-1">[<?= htmlspecialchars($row['customer_name'] ?? ''); ?>]</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-2 px-4 text-right font-bold text-blue-600 w-32 whitespace-nowrap">
                                                <?= $row['duration_formatted']; ?>
                                            </td>
                                            <td class="py-2 px-4 text-right font-bold text-green-600 w-32 whitespace-nowrap">
                                                <?php if ($row['is_fixed']): ?>
                                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded mr-1"><?= lang('finance_badge_fixed'); ?></span>
                                                <?php endif; ?>
                                                <?= number_format($row['earned'], 2, '.', ' '); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Вкладка: Архив проектов -->
    <div id="content-archive" class="hidden">
        <?php if (empty($archive_data)): ?>
        <div class="bg-white p-8 rounded-xl border border-gray-100 text-center shadow-sm">
            <span class="text-4xl mb-2 block">📁</span>
            <p class="text-lg text-gray-500">Архив проектов пуст.</p>
        </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-gray-500">
                            <th class="py-3 px-4 font-bold">Проект</th>
                            <th class="py-3 px-4 font-bold text-right">Затрачено</th>
                            <th class="py-3 px-4 font-bold text-right">Завершен</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($archive_data as $proj): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4 font-medium text-gray-800 flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full shadow-sm border border-gray-200" style="background-color: <?= $proj['color'] ? $proj['color'] : '#e5e7eb'; ?>"></div>
                                    <?= htmlspecialchars($proj['title'] ?? ''); ?>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-indigo-600">
                                    <?= $proj['duration_formatted']; ?>
                                </td>
                                <td class="py-3 px-4 text-right text-gray-400 font-mono">
                                    <?= $proj['date_completed']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Скрипт для фильтров и вкладок -->
<script>
    function formatDate(date) {
        let d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    function setFilter(type) {
        let start = new Date();
        let end = new Date();

        if (type === 'today') {
            // Сегодня
        } else if (type === 'yesterday') {
            start.setDate(start.getDate() - 1);
            end.setDate(end.getDate() - 1);
        } else if (type === 'week') {
            let day = start.getDay() || 7; 
            if (day !== 1) start.setHours(-24 * (day - 1));
        } else if (type === 'month') {
            start.setDate(1);
        }

        $('#dateStart').val(formatDate(start));
        $('#dateEnd').val(formatDate(end));
        $('#filterForm').submit();
    }

    function switchTab(tabName) {
        // Сбрасываем стили вкладок
        $('#tab-time, #tab-archive').removeClass('text-blue-600 border-blue-600').addClass('text-gray-500 border-transparent hover:text-gray-700');
        // Прячем контент
        $('#content-time, #content-archive').addClass('hidden');

        // Активируем нужную вкладку
        if (tabName === 'time') {
            $('#tab-time').removeClass('text-gray-500 border-transparent hover:text-gray-700').addClass('text-blue-600 border-blue-600');
            $('#content-time').removeClass('hidden');
        } else {
            $('#tab-archive').removeClass('text-gray-500 border-transparent hover:text-gray-700').addClass('text-blue-600 border-blue-600');
            $('#content-archive').removeClass('hidden');
        }
    }
</script>
