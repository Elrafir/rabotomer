<!-- Верхняя панель управления (Toolbar) и модальное окно фильтров -->
<div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
    <!-- Контейнер для выравнивания элементов управления -->
    <div class="flex flex-wrap justify-between items-center gap-4">
        
        <!-- Группа левых элементов: кнопка модалки и активные теги (чипсы) -->
        <div class="flex items-center gap-3">
            <!-- Кнопка открытия модального окна фильтров с эмодзи отвертки/ключа -->
            <button id="btn-open-filters" class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-bold py-2.5 px-4 rounded-lg transition-colors focus:outline-none">
                <span>🛠️</span>
                <!-- Текст кнопки открытия фильтров -->
                <span>Фильтры</span>
            </button>
            
            <!-- Контейнер для динамического отображения активных чипсов -->
            <div id="chips-container" class="flex flex-wrap gap-2">
                <?php 
                // Если чекбокс архивных снят (значение 0), выводим чипс "Скрыты архивные"
                if (isset($show_archived) && (int)$show_archived === 0){ 
                ?>
                    <!-- Чипс, при клике на который в JS произойдет сброс фильтра обратно на дефолт -->
                    <span id="chip-hide-archived" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 cursor-pointer transition-colors hover:bg-amber-100 select-none">
                        <span>Скрыты архивные</span>
                        <!-- Крестик для интерактивного закрытия -->
                        <span class="text-amber-500 font-black hover:text-amber-900 ml-1">✕</span>
                    </span>
                <?php 
                } 
                ?>

                <?php
                // Если выбран конкретный заказчик или "без заказчика", выводим чипс
                if (isset($customer_filter) && $customer_filter !== 'all') {
                    // Текст чипса по умолчанию
                    $chip_customer_label = 'Без заказчика';
                    // Если выбран конкретный ID, ищем имя в массиве заказчиков
                    if ($customer_filter !== 'none' && !empty($customers)) {
                        foreach ($customers as $c) {
                            if ((string)$c['id'] === (string)$customer_filter) {
                                $chip_customer_label = 'Заказчик: ' . $c['name'];
                                break;
                            }
                        }
                    }
                ?>
                    <!-- Интерактивный чипс заказчика для быстрого сброса -->
                    <span id="chip-customer" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 cursor-pointer transition-colors hover:bg-indigo-100 select-none">
                        <span><?= htmlspecialchars($chip_customer_label); ?></span>
                        <span class="text-indigo-500 font-black hover:text-indigo-900 ml-1">✕</span>
                    </span>
                <?php
                }
                ?>

                <?php
                // Если выбран не дефолтный тип сортировки (не по времени), выводим чипс
                if (isset($sort_by) && $sort_by !== 'time') {
                    // Переводим технический ключ сортировки в понятную текстовую строку
                    $sort_label = $sort_by === 'title' ? 'Сортировка: По алфавиту' : 'Сортировка: По заказчику';
                ?>
                    <!-- Интерактивный чипс сортировки для сброса на дефолт -->
                    <span id="chip-sort" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 cursor-pointer transition-colors hover:bg-purple-100 select-none">
                        <span><?= htmlspecialchars($sort_label); ?></span>
                        <span class="text-purple-500 font-black hover:text-purple-900 ml-1">✕</span>
                    </span>
                <?php
                }
                ?>
            </div>
        </div>

        <?php 
        // Проверяем, нужно ли выводить блок выбора дат (для временного среза)
        if (isset($show_dates) && $show_dates === true){ 
        ?>
            <!-- Блок с быстрыми кнопками выбора периодов и календарем -->
            <div class="flex flex-wrap items-center gap-4">
                <!-- Кнопки быстрого переключения временных интервалов с подсветкой активного -->
                <div class="flex gap-2">
                    <?php
                    // Вычисляем класс для кнопки "Сегодня" (проверяем равенство дат текущему рабочему дню)
                    $today_btn_class = (isset($today_start) && $start_date === $today_start && $end_date === $today_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';
                    
                    // Вычисляем класс для кнопки "Вчера"
                    $yesterday_btn_class = (isset($yesterday_start) && $start_date === $yesterday_start && $end_date === $yesterday_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';

                    // Вычисляем класс для кнопки "Неделя"
                    $week_btn_class = (isset($week_start) && $start_date === $week_start && $end_date === $week_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';

                    // Вычисляем класс для кнопки "Месяц"
                    $month_btn_class = (isset($month_start) && $start_date === $month_start && $end_date === $month_end)
                        ? 'bg-blue-600 text-white font-black'
                        : 'bg-gray-100 hover:bg-gray-200 text-gray-700';
                    ?>
                    <!-- Кнопка переключения на текущий день -->
                    <button data-range="today" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $today_btn_class; ?>">
                        Сегодня
                    </button>
                    <!-- Кнопка переключения на вчерашний день -->
                    <button data-range="yesterday" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $yesterday_btn_class; ?>">
                        Вчера
                    </button>
                    <!-- Кнопка переключения на текущую неделю -->
                    <button data-range="week" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $week_btn_class; ?>">
                        Неделя
                    </button>
                    <!-- Кнопка переключения на текущий месяц -->
                    <button data-range="month" class="btn-fast-date text-xs py-2 px-3.5 rounded-lg transition-colors <?= $month_btn_class; ?>">
                        Месяц
                    </button>
                </div>

                <!-- Форма ручного ввода диапазона дат через календари -->
                <div class="flex items-center gap-3">
                    <!-- Поле выбора даты "С" -->
                    <div class="flex items-center gap-2">
                        <!-- Подпись "С" -->
                        <span class="text-gray-500 text-xs font-bold uppercase tracking-wider">С</span>
                        <!-- Календарь даты начала -->
                        <input type="date" id="stat-date-start" value="<?= htmlspecialchars($start_date ?? ''); ?>" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <!-- Поле выбора даты "По" -->
                    <div class="flex items-center gap-2">
                        <!-- Подпись "По" -->
                        <span class="text-gray-500 text-xs font-bold uppercase tracking-wider">По</span>
                        <!-- Календарь даты окончания -->
                        <input type="date" id="stat-date-end" value="<?= htmlspecialchars($end_date ?? ''); ?>" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>
            </div>
        <?php 
        } 
        ?>
    </div>
</div>

<!-- Модальное окно фильтрации на Tailwind CSS -->
<!-- Бэкдроп с полупрозрачным фоном, центрирующий контент -->
<div id="modal-filters" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <!-- Карточка модального окна -->
    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 w-full max-w-md mx-4 overflow-hidden transform scale-95 transition-transform duration-300">
        <!-- Шапка модального окна -->
        <div class="p-6 border-b border-gray-50 bg-gray-50 flex justify-between items-center">
            <!-- Заголовок модалки -->
            <h3 class="font-black text-gray-800 text-lg">Настройки фильтрации</h3>
            <!-- Кнопка закрытия (крестик) -->
            <button id="btn-close-filters-modal" class="text-gray-400 hover:text-gray-600 text-xl font-bold focus:outline-none">
                ✕
            </button>
        </div>
        
        <!-- Контент модального окна -->
        <div class="p-6 space-y-4">
            <!-- Элемент выбора чекбокса "Показывать архивные" -->
            <label class="flex items-center gap-3 cursor-pointer group">
                <!-- Скрытый реальный чекбокс, значение берется из переданной переменной -->
                <input type="checkbox" id="filter-show-archived" class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer" <?= (isset($show_archived) && (int)$show_archived === 1) ? 'checked' : ''; ?>>
                <!-- Текстовая подпись чекбокса -->
                <span class="text-sm font-bold text-gray-700 group-hover:text-gray-900 transition-colors">
                    Показывать архивные
                </span>
            </label>

            <!-- Выпадающий список выбора заказчика -->
            <div class="flex flex-col gap-1.5">
                <label for="filter-customer" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Заказчик</label>
                <select id="filter-customer" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="all" <?= (isset($customer_filter) && $customer_filter === 'all') ? 'selected' : ''; ?>>Все заказчики</option>
                    <option value="none" <?= (isset($customer_filter) && $customer_filter === 'none') ? 'selected' : ''; ?>>Без заказчика</option>
                    <?php if (!empty($customers)): foreach($customers as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= (isset($customer_filter) && (string)$customer_filter === (string)$c['id']) ? 'selected' : ''; ?>><?= htmlspecialchars($c['name'] ?? ''); ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <!-- Выпадающий список выбора типа сортировки -->
            <div class="flex flex-col gap-1.5">
                <label for="filter-sort" class="text-xs font-bold text-gray-500 uppercase tracking-wider">Сортировка</label>
                <select id="filter-sort" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-700 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="time" <?= (isset($sort_by) && $sort_by === 'time') ? 'selected' : ''; ?>>По времени</option>
                    <option value="title" <?= (isset($sort_by) && $sort_by === 'title') ? 'selected' : ''; ?>>По алфавиту (названию)</option>
                    <option value="customer" <?= (isset($sort_by) && $sort_by === 'customer') ? 'selected' : ''; ?>>По заказчику</option>
                </select>
            </div>
        </div>
        
        <!-- Футер модального окна с кнопкой применения -->
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
            <!-- Кнопка применения фильтра, которая просто закрывает модалку (изменение чекбокса само вызовет AJAX) -->
            <button id="btn-apply-filters" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl text-sm shadow-sm transition-colors focus:outline-none">
                Применить
            </button>
        </div>
    </div>
</div>
