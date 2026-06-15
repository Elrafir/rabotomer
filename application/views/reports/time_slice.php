<!-- Контейнер для страницы временного среза с отступами -->
<div class="w-full min-h-[80vh] pb-32">
    
    <!-- Шапка страницы с заголовком и иконкой -->
    <div class="flex justify-between items-end mb-4">
        <!-- Группа выравнивания иконки и заголовка -->
        <div class="flex items-center gap-6">
            <!-- Декоративное изображение логотипа отчетов -->
            <img src="<?= base_url('assets/img/reports_logo.png') ?>" alt="Reports Logo" class="w-16 h-16 object-cover rounded-2xl shadow-sm">
            <!-- Главный заголовок страницы с выводом периода -->
            <div>
                <h1 class="text-3xl font-black text-gray-800">Временной срез</h1>
                <!-- Рабочий период с 5 утра до 5 утра следующего дня -->
                <p class="text-[11px] text-gray-500 font-bold mt-1 uppercase tracking-wider">
                    Период: с <span class="text-blue-600 font-black"><?= date('d.m.Y', strtotime($start_date)); ?> 05:00</span> по <span class="text-blue-600 font-black"><?= date('d.m.Y', strtotime('+1 day', strtotime($end_date))); ?> 05:00</span>
                </p>
            </div>
        </div>
    </div>

    <?php 
    // Загружаем общий тулбар управления фильтрами
    // Передаем параметры: отображать селекторы дат и текущие значения фильтров
    $this->load->view('reports/toolbar', [
        'show_dates' => true, 
        'start_date' => $start_date, 
        'end_date' => $end_date, 
        'show_archived' => $show_archived,
        // Передаем массивы выбранных множественных фильтров
        'customer_filters' => $customer_filters,
        'calculation_filters' => $calculation_filters,
        'spec_filters' => $spec_filters,
        'sort_by' => $sort_by,
        // Передаем направление сортировки
        'sort_dir' => $sort_dir,
        // Передаем справочники
        'customers' => $customers,
        'calculations' => $calculations,
        'specs' => $specs,
        'today_start' => $today_start,
        'today_end' => $today_end,
        'yesterday_start' => $yesterday_start,
        'yesterday_end' => $yesterday_end,
        'week_start' => $week_start,
        'week_end' => $week_end,
        'month_start' => $month_start,
        'month_end' => $month_end
    ]); 
    ?>

    <!-- Крупный информационный блок: Общее затраченное время за период (динамический фон темы) -->
    <div class="p-8 rounded-3xl text-white shadow-md flex justify-between items-center mb-8" style="background: linear-gradient(to right, var(--theme-color-main), var(--theme-color-hover)) !important;">
        <!-- Группа подписи и цифры -->
        <div>
            <!-- Небольшая подпись над цифрой -->
            <span class="text-blue-200 text-xs font-bold uppercase tracking-widest block mb-2">Общее затраченное время за период:</span>
            <!-- Отображаем отформатированные часы и минуты за период -->
            <span class="text-4xl font-black tracking-tight"><?= htmlspecialchars($total_time_formatted); ?></span>
        </div>
        <!-- Иконка секундомера с прозрачностью -->
        <div class="text-5xl opacity-40 select-none">⏱️</div>
    </div>

    <!-- Заголовок детализации распределения времени -->
    <h2 class="text-lg font-black text-gray-800 mb-4 flex items-center gap-2">
        <span>📊</span>
        <span>Распределение времени по проектам</span>
    </h2>

    <?php 
    // Если список проектов пуст (время ни на один проект не списывалось)
    if (empty($projects)){ 
    ?>
        <!-- Выводим карточку пустого состояния -->
        <div class="bg-white p-12 rounded-3xl border border-gray-100 text-center shadow-sm">
            <!-- Эмодзи почтового ящика -->
            <span class="text-5xl mb-4 block">📭</span>
            <!-- Поясняющий текст -->
            <p class="text-lg font-bold text-gray-500">За выбранный период активности не найдено.</p>
            <!-- Дополнительная подсказка -->
            <p class="text-sm text-gray-400 mt-1">Попробуйте расширить временной отрезок или проверить фильтр архивных задач.</p>
        </div>
    <?php 
    } else { 
    ?>
        <!-- Контейнер для карточек проектов -->
        <div class="grid grid-cols-1 gap-4">
            <?php 
            // Проходим в цикле по каждому корневому проекту с записанным временем
            foreach ($projects as $project){ 
            ?>
                <!-- Карточка отдельного корневого проекта -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4 transition-all hover:shadow-md">
                    
                    <!-- Левый блок: Цветовой маркер и название проекта -->
                    <div class="flex items-center gap-4 min-w-0 flex-1">
                        <!-- Цветная метка проекта, цвет берется из базы -->
                        <div class="w-4 h-4 rounded-full border border-gray-200 shadow-sm flex-shrink-0" style="background-color: <?= htmlspecialchars($project['color']); ?>"></div>
                        <!-- Название проекта с обрезкой длинного текста -->
                        <span class="text-base font-bold text-gray-800 truncate"><?= htmlspecialchars($project['title']); ?></span>
                        <?php // Если у проекта есть привязанный заказчик, выводим его имя рядом ?>
                        <?php if (!empty($project['customer_name'])): ?>
                            <span class="text-xs text-gray-400 font-medium px-2 py-0.5 rounded-full bg-gray-50 border border-gray-200">
                                [<?= htmlspecialchars($project['customer_name']); ?>]
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Средний блок: Наглядный прогресс-бар доли времени проекта -->
                    <div class="w-full md:w-64 flex flex-col gap-1">
                        <!-- Контейнер шкалы прогресса -->
                        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                            <!-- Заполненная часть шкалы, окрашивается цветом проекта -->
                            <div class="h-2.5 rounded-full" style="width: <?= $project['percentage']; ?>%; background-color: <?= htmlspecialchars($project['color']); ?>"></div>
                        </div>
                        <!-- Процентное соотношение -->
                        <span class="text-xs font-bold text-gray-400 self-end"><?= $project['percentage']; ?>% от общего времени</span>
                    </div>

                    <!-- Правый блок: Суммарное время, затраченное на этот проект за период -->
                    <div class="text-right flex-shrink-0">
                        <!-- Отформатированное время (часы и минуты) жирным шрифтом -->
                        <span class="text-lg font-black text-blue-600 block"><?= htmlspecialchars($project['formatted_time']); ?></span>
                    </div>

                </div>
            <?php 
            } 
            ?>
        </div>
    <?php 
    } 
    ?>

</div>

<!-- Подключаем JS-модуль отчетов и статистики -->
<script src="<?= base_url('assets/js/reports.js?v=' . time()) ?>"></script>

