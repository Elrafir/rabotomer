// --- МОДУЛЬ ОТЧЕТОВ И СТАТИСТИКИ (REPORTS) ---

// Проверяем, был ли модуль уже загружен (защита от повторной инициализации при AJAX SPA переходах)
if (!window.loadedReportsModule) {
    // Устанавливаем флаг загрузки модуля
    window.loadedReportsModule = true;

    /**
     * Форматирует объект даты в строку YYYY-MM-DD
     * @param {Date} date - Объект даты
     * @returns {string} Строка вида YYYY-MM-DD
     */
    function formatJsDateForStats(date) {
        const year = date.getFullYear();
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Выбирает параметры фильтрации из DOM, строит URL и обновляет контент отчетов через SPA
     */
    window.refreshStatistics = function() {
        let showArchived = 'all';
        if ($('#filter-archive-active').is(':checked')) {
            showArchived = 'active';
        } else if ($('#filter-archive-archived').is(':checked')) {
            showArchived = 'archived';
        }
        
        const params = [];
        params.push('show_archived=' + showArchived);
        
        if ($('#stat-date-start').length) {
            params.push('start=' + $('#stat-date-start').val());
        }
        if ($('#stat-date-end').length) {
            params.push('end=' + $('#stat-date-end').val());
        }

        // Фильтры по заказчикам
        $('input[name="customer_filters[]"]:checked').each(function() {
            params.push('customer_filters[]=' + encodeURIComponent($(this).val()));
        });

        // Фильтры по калькуляциям
        $('input[name="calculation_filters[]"]:checked').each(function() {
            params.push('calculation_filters[]=' + encodeURIComponent($(this).val()));
        });

        // Фильтры по ТЗ
        $('input[name="spec_filters[]"]:checked').each(function() {
            params.push('spec_filters[]=' + encodeURIComponent($(this).val()));
        });

        if ($('#filter-sort').length) {
            params.push('sort_by=' + $('#filter-sort').val());
        }
        if ($('#filter-sort-dir').length) {
            params.push('sort_dir=' + $('#filter-sort-dir').val());
        }
        
        const targetUrl = window.location.pathname + '?' + params.join('&');
        loadAjaxPage(targetUrl, true); // Загружаем страницу через SPA
    };

    /**
     * Устанавливает быстрый фильтр дат на форме отчетов (вкладка времени)
     * @param {string} type - Тип периода (today, yesterday, week, month)
     */
    window.setFilter = function(type) {
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

        $('#dateStart').val(formatJsDateForStats(start));
        $('#dateEnd').val(formatJsDateForStats(end));
        $('#filterForm').submit(); // Отправляем форму (она перехвачена SPA ссылками/AJAX)
    };

    /**
     * Переключает вкладки (Время / Архив) на странице отчетов
     * @param {string} tabName - Имя вкладки (time или archive)
     */
    window.switchTab = function(tabName) {
        $('#tab-time, #tab-archive')
            .removeClass('text-blue-600 border-blue-600')
            .addClass('text-gray-500 border-transparent hover:text-gray-700');
        $('#content-time, #content-archive').addClass('hidden');

        if (tabName === 'time') {
            $('#tab-time').removeClass('text-gray-500 border-transparent hover:text-gray-700').addClass('text-blue-600 border-blue-600');
            $('#content-time').removeClass('hidden');
        } else {
            $('#tab-archive').removeClass('text-gray-500 border-transparent hover:text-gray-700').addClass('text-blue-600 border-blue-600');
            $('#content-archive').removeClass('hidden');
        }
    };

    // Делегированные обработчики событий статистики и фильтров

    // Чекбокс "Без архивных"
    $(document).on('change', '#filter-archive-active', function() {
        if ($(this).is(':checked')) {
            $('#filter-archive-archived').prop('checked', false);
        }
        window.refreshStatistics();
    });

    // Чекбокс "Только архивные"
    $(document).on('change', '#filter-archive-archived', function() {
        if ($(this).is(':checked')) {
            $('#filter-archive-active').prop('checked', false);
        }
        window.refreshStatistics();
    });

    // Изменение других чекбоксов фильтрации
    $(document).on('change', 'input[name="customer_filters[]"], input[name="calculation_filters[]"], input[name="spec_filters[]"]', function() {
        window.refreshStatistics();
    });

    // Смена типа сортировки
    $(document).on('change', '#filter-sort', function() {
        window.refreshStatistics();
    });

    // Смена дат в календариках боковой панели
    $(document).on('change', '#stat-date-start, #stat-date-end', function() {
        window.refreshStatistics();
    });

    // Сброс чипса "Без архивных"
    $(document).on('click', '#chip-archive-active', function() {
        $('#filter-archive-active').prop('checked', false);
        window.refreshStatistics();
    });

    // Сброс чипса "Только архивные"
    $(document).on('click', '#chip-archive-archived', function() {
        $('#filter-archive-archived').prop('checked', false);
        window.refreshStatistics();
    });

    // Сброс чипса конкретного клиента
    $(document).on('click', '.chip-customer-item', function() {
        const val = $(this).data('value');
        $('input[name="customer_filters[]"][value="' + val + '"]').prop('checked', false);
        window.refreshStatistics();
    });

    // Сброс чипса конкретной калькуляции
    $(document).on('click', '.chip-calculation-item', function() {
        const val = $(this).data('value');
        $('input[name="calculation_filters[]"][value="' + val + '"]').prop('checked', false);
        window.refreshStatistics();
    });

    // Сброс чипса конкретного ТЗ
    $(document).on('click', '.chip-spec-item', function() {
        const val = $(this).data('value');
        $('input[name="spec_filters[]"][value="' + val + '"]').prop('checked', false);
        window.refreshStatistics();
    });

    // Смена направления сортировки (кнопка вверх/вниз)
    $(document).on('click', '#btn-toggle-sort-dir', function(e) {
        e.preventDefault();
        const currentDir = $('#filter-sort-dir').val() || 'desc';
        const newDir = (currentDir === 'asc') ? 'desc' : 'asc';
        
        $('#filter-sort-dir').val(newDir);
        
        if (newDir === 'asc') {
            $(this).addClass('transform rotate-180');
        } else {
            $(this).removeClass('transform rotate-180');
        }
        window.refreshStatistics();
    });

    // Быстрый выбор дат в боковом меню
    $(document).on('click', '.btn-fast-date', function(e) {
        e.preventDefault();
        const range = $(this).data('range');
        const now = new Date();
        const hour = now.getHours();
        
        let baseDate = new Date();
        // Если время до 5 утра, сдвигаем базовый день на вчера (ночная смена)
        if (hour < 5) {
            baseDate.setDate(baseDate.getDate() - 1);
        }
        
        let start = new Date(baseDate);
        let end = new Date(baseDate);

        if (range === 'today') {
            // Сегодня
        } else if (range === 'yesterday') {
            start.setDate(baseDate.getDate() - 1);
            end.setDate(baseDate.getDate() - 1);
        } else if (range === 'week') {
            const day = baseDate.getDay() || 7;
            start.setDate(baseDate.getDate() - (day - 1));
        } else if (range === 'month') {
            start.setDate(1);
        }

        $('#stat-date-start').val(formatJsDateForStats(start));
        $('#stat-date-end').val(formatJsDateForStats(end));
        window.refreshStatistics();
    });

    // Раскрытие/сворачивание дерева статистики (аккордеон)
    $(document).on('click', '.toggle-stats-children', function(e) {
        const li = $(this).closest('li');
        const childrenUl = li.children('.stats-children');
        const arrowIcon = $(this).find('.icon-stats-expand');
        
        childrenUl.slideToggle(200);
        arrowIcon.toggleClass('rotate-180');
    });
}
