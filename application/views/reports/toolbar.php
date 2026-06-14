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
            </div>
        </div>

        <?php 
        // Проверяем, нужно ли выводить блок выбора дат (для временного среза)
        if (isset($show_dates) && $show_dates === true){ 
        ?>
            <!-- Блок с быстрыми кнопками выбора периодов и календарем -->
            <div class="flex flex-wrap items-center gap-4">
                <!-- Кнопки быстрого переключения временных интервалов -->
                <div class="flex gap-2">
                    <!-- Кнопка переключения на текущий день -->
                    <button data-range="today" class="btn-fast-date bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3.5 rounded-lg transition-colors">
                        Сегодня
                    </button>
                    <!-- Кнопка переключения на вчерашний день -->
                    <button data-range="yesterday" class="btn-fast-date bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3.5 rounded-lg transition-colors">
                        Вчера
                    </button>
                    <!-- Кнопка переключения на текущую неделю -->
                    <button data-range="week" class="btn-fast-date bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3.5 rounded-lg transition-colors">
                        Неделя
                    </button>
                    <!-- Кнопка переключения на текущий месяц -->
                    <button data-range="month" class="btn-fast-date bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-3.5 rounded-lg transition-colors">
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
