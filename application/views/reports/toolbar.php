<!-- Верхняя панель управления (Toolbar) со встроенными выдвижными фильтрами -->
<div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
    <!-- Первый ряд: кнопки управления, выбор дат и сортировка -->
    <div class="flex flex-wrap items-center justify-between gap-4 w-full">
        
        <!-- Левая часть: кнопка открытия панели фильтров -->
        <div>
            <button id="btn-open-filters" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold py-2.5 px-4 rounded-lg transition-colors focus:outline-none">
                <span>🛠️</span>
                <span>Фильтры</span>
            </button>
        </div>
        
        <!-- Правая часть: быстрые периоды, календари и Сортировка -->
        <div class="flex flex-wrap items-center gap-4 ml-auto">
            <?php if (isset($show_dates) && $show_dates === true): ?>
                <!-- Кнопки быстрого переключения временных интервалов -->
                <div class="flex gap-2">
                    <?php
                    $today_btn_class = (isset($today_start) && $start_date === $today_start && $end_date === $today_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';
                    
                    $yesterday_btn_class = (isset($yesterday_start) && $start_date === $yesterday_start && $end_date === $yesterday_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';

                    $week_btn_class = (isset($week_start) && $start_date === $week_start && $end_date === $week_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';

                    $month_btn_class = (isset($month_start) && $start_date === $month_start && $end_date === $month_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';
                    ?>
                    <button data-range="today" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $today_btn_class; ?>">
                        Сегодня
                    </button>
                    <button data-range="yesterday" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $yesterday_btn_class; ?>">
                        Вчера
                    </button>
                    <button data-range="week" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $week_btn_class; ?>">
                        Неделя
                    </button>
                    <button data-range="month" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $month_btn_class; ?>">
                        Месяц
                    </button>
                </div>

                <!-- Форма ручного ввода диапазона дат через календари -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 text-xs font-bold uppercase tracking-wider">С</span>
                        <input type="date" id="stat-date-start" value="<?= htmlspecialchars($start_date ?? ''); ?>" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 text-xs font-bold uppercase tracking-wider">По</span>
                        <input type="date" id="stat-date-end" value="<?= htmlspecialchars($end_date ?? ''); ?>" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
            <?php endif; ?>

            <!-- Блок Сортировки -->
            <div class="flex items-center gap-2">
                <span class="text-gray-500 text-xs font-bold uppercase tracking-wider">Сортировка</span>
                <select id="filter-sort" class="px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="time" <?= (isset($sort_by) && $sort_by === 'time') ? 'selected' : ''; ?>>По времени</option>
                    <option value="title" <?= (isset($sort_by) && $sort_by === 'title') ? 'selected' : ''; ?>>По алфавиту</option>
                    <option value="customer" <?= (isset($sort_by) && $sort_by === 'customer') ? 'selected' : ''; ?>>По заказчику</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Встроенная выдвижная панель фильтров -->
    <div id="filters-drawer" class="hidden w-full mt-4 p-4 bg-gray-50 rounded-xl border border-gray-100 transition-all duration-200">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            
            <!-- Колонна 1: Опции -->
            <div class="flex flex-col gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Опции</span>
                <label class="flex items-center gap-2 cursor-pointer mt-1 group">
                    <input type="checkbox" id="filter-show-archived" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($show_archived) && (int)$show_archived === 1) ? 'checked' : ''; ?>>
                    <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">Показывать архивные</span>
                </label>
            </div>

            <!-- Колонна 2: Фильтр по заказчикам -->
            <div class="flex flex-col gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Заказчики</span>
                <div class="max-h-40 overflow-y-auto space-y-1.5 pr-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="customer_filters[]" value="none" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($customer_filters) && in_array('none', $customer_filters)) ? 'checked' : ''; ?>>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">Без заказчика</span>
                    </label>
                    <?php if (!empty($customers)): foreach($customers as $c): ?>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" name="customer_filters[]" value="<?= $c['id']; ?>" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($customer_filters) && in_array((string)$c['id'], $customer_filters)) ? 'checked' : ''; ?>>
                            <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors"><?= htmlspecialchars($c['name']); ?></span>
                        </label>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Колонна 3: Фильтр по калькуляциям -->
            <div class="flex flex-col gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Калькуляция</span>
                <div class="max-h-40 overflow-y-auto space-y-1.5 pr-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="calculation_filters[]" value="none" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($calculation_filters) && in_array('none', $calculation_filters)) ? 'checked' : ''; ?>>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">Вне калькуляций</span>
                    </label>
                    <?php if (!empty($calculations)): foreach($calculations as $calc): ?>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" name="calculation_filters[]" value="<?= $calc['id']; ?>" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($calculation_filters) && in_array((string)$calc['id'], $calculation_filters)) ? 'checked' : ''; ?>>
                            <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors"><?= htmlspecialchars($calc['title']); ?></span>
                        </label>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- Колонна 4: Фильтр по ТЗ -->
            <div class="flex flex-col gap-2">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Техзадания (ТЗ)</span>
                <div class="max-h-40 overflow-y-auto space-y-1.5 pr-2">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" name="spec_filters[]" value="none" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($spec_filters) && in_array('none', $spec_filters)) ? 'checked' : ''; ?>>
                        <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors">Вне ТЗ</span>
                    </label>
                    <?php if (!empty($specs)): foreach($specs as $spec): ?>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" name="spec_filters[]" value="<?= $spec['id']; ?>" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($spec_filters) && in_array((string)$spec['id'], $spec_filters)) ? 'checked' : ''; ?>>
                            <span class="text-sm text-gray-700 group-hover:text-gray-900 transition-colors"><?= htmlspecialchars($spec['title']); ?> <span class="text-[10px] text-gray-400 font-medium">(<?= htmlspecialchars($spec['customer_name']); ?>)</span></span>
                        </label>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Контейнер для отображения активных фильтров (чипсов) -->
    <div id="chips-container" class="flex flex-wrap gap-2 mt-4 pt-3 border-t border-gray-100 <?= (
        ((isset($show_archived) && (int)$show_archived === 0)) ||
        (!empty($customer_filters)) ||
        (!empty($calculation_filters)) ||
        (!empty($spec_filters))
    ) ? '' : 'hidden' ?>">
        
        <!-- Чипс "Скрыты архивные" -->
        <?php if (isset($show_archived) && (int)$show_archived === 0): ?>
            <span id="chip-hide-archived" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 cursor-pointer transition-colors hover:bg-amber-100 select-none">
                <span>Скрыты архивные</span>
                <span class="text-amber-500 font-black hover:text-amber-900 ml-1">✕</span>
            </span>
        <?php endif; ?>

        <!-- Чипсы Заказчиков -->
        <?php if (!empty($customer_filters)): foreach($customer_filters as $cf): ?>
            <?php
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
            <span class="chip-customer-item inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 cursor-pointer transition-colors hover:bg-indigo-100 select-none" data-value="<?= htmlspecialchars($cf); ?>">
                <span>Заказчик: <?= htmlspecialchars($label); ?></span>
                <span class="text-indigo-500 font-black hover:text-indigo-900 ml-1">✕</span>
            </span>
        <?php endforeach; endif; ?>

        <!-- Чипсы Калькуляций -->
        <?php if (!empty($calculation_filters)): foreach($calculation_filters as $calf): ?>
            <?php
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
            <span class="chip-calculation-item inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-pointer transition-colors hover:bg-emerald-100 select-none" data-value="<?= htmlspecialchars($calf); ?>">
                <span>Калькуляция: <?= htmlspecialchars($label); ?></span>
                <span class="text-emerald-500 font-black hover:text-emerald-900 ml-1">✕</span>
            </span>
        <?php endforeach; endif; ?>

        <!-- Чипсы ТЗ -->
        <?php if (!empty($spec_filters)): foreach($spec_filters as $sf): ?>
            <?php
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
            <span class="chip-spec-item inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 cursor-pointer transition-colors hover:bg-purple-100 select-none" data-value="<?= htmlspecialchars($sf); ?>">
                <span>ТЗ: <?= htmlspecialchars($label); ?></span>
                <span class="text-purple-500 font-black hover:text-purple-900 ml-1">✕</span>
            </span>
        <?php endforeach; endif; ?>

    </div>
</div>
