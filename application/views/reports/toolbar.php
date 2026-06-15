<!-- Верхняя панель управления (Toolbar) -->
<div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
    <!-- Первый ряд: выбор дат (слева) и сортировка (справа) -->
    <div class="flex flex-wrap items-center justify-between gap-4 w-full">
        
        <!-- Блок быстрых периодов и календарей (слева) -->
        <div class="flex flex-wrap items-center gap-4">
            <?php 
            // Проверяем, нужно ли показывать выбор дат для данного отчета
            if (isset($show_dates) && $show_dates === true): 
            ?>
                <!-- Кнопки быстрого переключения временных интервалов -->
                <div class="flex gap-2">
                    <?php
                    // Вычисляем CSS-класс для кнопки «Сегодня» (выделяем синим цветом, если период совпадает)
                    $today_btn_class = (isset($today_start) && $start_date === $today_start && $end_date === $today_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';
                    
                    // Вычисляем CSS-класс для кнопки «Вчера»
                    $yesterday_btn_class = (isset($yesterday_start) && $start_date === $yesterday_start && $end_date === $yesterday_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';

                    // Вычисляем CSS-класс для кнопки «Неделя»
                    $week_btn_class = (isset($week_start) && $start_date === $week_start && $end_date === $week_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';

                    // Вычисляем CSS-класс для кнопки «Месяц»
                    $month_btn_class = (isset($month_start) && $start_date === $month_start && $end_date === $month_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';
                    ?>
                    <!-- Кнопка быстрого выбора сегодняшней даты -->
                    <button data-range="today" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $today_btn_class; ?>">
                        Сегодня
                    </button>
                    <!-- Кнопка быстрого выбора вчерашней даты -->
                    <button data-range="yesterday" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $yesterday_btn_class; ?>">
                        Вчера
                    </button>
                    <!-- Кнопка быстрого выбора текущей недели -->
                    <button data-range="week" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $week_btn_class; ?>">
                        Неделя
                    </button>
                    <!-- Кнопка быстрого выбора текущего месяца -->
                    <button data-range="month" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $month_btn_class; ?>">
                        Месяц
                    </button>
                </div>

                <!-- Форма ручного ввода диапазона дат через календари -->
                <div class="flex items-center gap-3">
                    <!-- Поле выбора даты начала -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 text-xs font-bold uppercase tracking-wider">С</span>
                        <input type="date" id="stat-date-start" value="<?= htmlspecialchars($start_date ?? ''); ?>" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <!-- Поле выбора даты окончания -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 text-xs font-bold uppercase tracking-wider">По</span>
                        <input type="date" id="stat-date-end" value="<?= htmlspecialchars($end_date ?? ''); ?>" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
            <?php 
            // Завершаем проверку показа дат
            endif; 
            ?>
        </div>

        <!-- Блок выбора сортировки данных (справа, иконка со стрелками вместо текста) -->
        <div class="flex items-center gap-2 ml-auto">
            <!-- Кнопка переключения направления сортировки -->
            <button type="button" id="btn-toggle-sort-dir" class="text-gray-400 hover:text-gray-600 transition-all duration-200 focus:outline-none <?= (isset($sort_dir) && $sort_dir === 'asc') ? 'transform rotate-180' : ''; ?>" title="Изменить направление сортировки">
                <!-- Иконка сортировки (двунаправленные стрелки SVG) -->
                <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                </svg>
            </button>
            <!-- Скрытый инпут для передачи направления сортировки -->
            <input type="hidden" id="filter-sort-dir" value="<?= htmlspecialchars($sort_dir ?? 'desc'); ?>">
            <!-- Селектор типа сортировки (сужен до w-36 для предотвращения сползания строки) -->
            <select id="filter-sort" class="w-36 px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <!-- Опция сортировки по дате создания -->
                <option value="created" <?= (isset($sort_by) && $sort_by === 'created') ? 'selected' : ''; ?>>По дате</option>
                <!-- Опция сортировки по последней активности -->
                <option value="activity" <?= (isset($sort_by) && $sort_by === 'activity') ? 'selected' : ''; ?>>По активности</option>
                <!-- Опция сортировки по алфавиту -->
                <option value="title" <?= (isset($sort_by) && $sort_by === 'title') ? 'selected' : ''; ?>>По имени</option>
                <!-- Опция сортировки по имени заказчика -->
                <option value="customer" <?= (isset($sort_by) && $sort_by === 'customer') ? 'selected' : ''; ?>>По заказчику</option>
            </select>
        </div>
    </div>

    <!-- Контейнер для отображения активных фильтров (чипсов). Контейнер виден, если применены фильтры архивации или выбраны другие фильтры -->
    <div id="chips-container" class="flex flex-wrap gap-2 mt-4 pt-3 border-t border-gray-100 <?= (
        ((isset($show_archived) && in_array($show_archived, ['active', 'archived']))) ||
        (!empty($customer_filters)) ||
        (!empty($calculation_filters)) ||
        (!empty($spec_filters))
    ) ? '' : 'hidden' ?>">
        
        <!-- Чипс "Без архивных" (показывается, когда включена фильтрация без архивных) -->
        <?php if (isset($show_archived) && $show_archived === 'active'): ?>
            <span id="chip-archive-active" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 cursor-pointer transition-colors hover:bg-amber-100 select-none" title="Сбросить фильтр">
                <span>Без архивных</span>
                <span class="text-amber-500 font-black hover:text-amber-900 ml-1">✕</span>
            </span>
        <?php endif; ?>

        <!-- Чипс "Только архивные" (показывается, когда включена фильтрация только архивных) -->
        <?php if (isset($show_archived) && $show_archived === 'archived'): ?>
            <span id="chip-archive-archived" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 cursor-pointer transition-colors hover:bg-amber-100 select-none" title="Сбросить фильтр">
                <span>Только архивные</span>
                <span class="text-amber-500 font-black hover:text-amber-900 ml-1">✕</span>
            </span>
        <?php endif; ?>


        <!-- Чипсы Заказчиков -->
        <?php if (!empty($customer_filters)): foreach($customer_filters as $cf): ?>
            <?php
            // Определяем название заказчика по умолчанию, если привязка отсутствует
            $label = 'Без заказчика';
            if ($cf !== 'none' && !empty($customers)) {
                foreach ($customers as $c) {
                    if ((string)$c['id'] === (string)$cf) {
                        $label = $c['name'];
                        break;
                    }
                }
            }
            ?>
            <!-- Чипс конкретного выбранного заказчика -->
            <span class="chip-customer-item inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 cursor-pointer transition-colors hover:bg-indigo-100 select-none" data-value="<?= htmlspecialchars($cf); ?>">
                <span>Заказчик: <?= htmlspecialchars($label); ?></span>
                <span class="text-indigo-500 font-black hover:text-indigo-900 ml-1">✕</span>
            </span>
        <?php endforeach; endif; ?>

        <!-- Чипсы Калькуляций -->
        <?php if (!empty($calculation_filters)): foreach($calculation_filters as $calf): ?>
            <?php
            // Определяем название калькуляции по умолчанию
            $label = 'Вне калькуляций';
            if ($calf !== 'none' && !empty($calculations)) {
                foreach ($calculations as $calc) {
                    if ((string)$calc['id'] === (string)$calf) {
                        $label = $calc['title'];
                        break;
                    }
                }
            }
            ?>
            <!-- Чипс конкретной выбранной калькуляции -->
            <span class="chip-calculation-item inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-pointer transition-colors hover:bg-emerald-100 select-none" data-value="<?= htmlspecialchars($calf); ?>">
                <span>Калькуляция: <?= htmlspecialchars($label); ?></span>
                <span class="text-emerald-500 font-black hover:text-emerald-900 ml-1">✕</span>
            </span>
        <?php endforeach; endif; ?>

        <!-- Чипсы ТЗ -->
        <?php if (!empty($spec_filters)): foreach($spec_filters as $sf): ?>
            <?php
            // Определяем название ТЗ по умолчанию
            $label = 'Вне ТЗ';
            if ($sf !== 'none' && !empty($specs)) {
                foreach ($specs as $spec) {
                    if ((string)$spec['id'] === (string)$sf) {
                        $label = $spec['title'];
                        break;
                    }
                }
            }
            ?>
            <!-- Чипс конкретного выбранного ТЗ -->
            <span class="chip-spec-item inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 cursor-pointer transition-colors hover:bg-purple-100 select-none" data-value="<?= htmlspecialchars($sf); ?>">
                <span>ТЗ: <?= htmlspecialchars($label); ?></span>
                <span class="text-purple-500 font-black hover:text-purple-900 ml-1">✕</span>
            </span>
        <?php endforeach; endif; ?>
    </div>
</div>
