/**
 * Модуль бесконечного скролла и аккордеонов для раздела «Заказчики».
 * Обеспечивает подгрузку заказчиков и задач при прокрутке,
 * переключение видимости закрытых задач и сворачивание/разворачивание ТЗ.
 */

/**
 * Настраивает бесконечный скролл списка заказчиков в сайдбаре.
 * При прокрутке до конца — подгружает следующую порцию через AJAX.
 */
window.initInfiniteScrollCustomers = function() {
    // Текущий offset для пагинации (начинаем с первой страницы)
    var custOffset = window.globalPerPage || 25;
    // Размер порции загрузки
    var custLimit = window.globalPerPage || 25;
    // Флаг наличия ещё записей
    var custHasMore = true;
    // Флаг текущей загрузки (защита от параллельных запросов)
    var custIsLoading = false;

    // Привязываем делегированный обработчик scroll к списку заказчиков
    $('#customersSidebarList').off('scroll').on('scroll', function() {
        // Проверяем наличие контейнера в DOM
        if ($('#customersSidebarList').length === 0) return;
        // Проверяем, есть ли ещё записи и не идёт ли уже загрузка
        if (!custHasMore || custIsLoading) return;

        // Текущая позиция прокрутки
        var scrollTop = $(this).scrollTop();
        // Полная высота контента
        var scrollHeight = $(this)[0].scrollHeight;
        // Видимая высота контейнера
        var innerHeight = $(this).innerHeight();

        // Если до конца осталось менее 50px — подгружаем
        if (scrollTop + innerHeight >= scrollHeight - 50) {
            // Блокируем повторную загрузку
            custIsLoading = true;
            // Получаем ID активного заказчика
            var activeCustomerId = window.activeCustomerId || null;

            // Отправляем AJAX-запрос на подгрузку следующей порции
            $.post(window.globalApi.load_more_customers, { offset: custOffset, active_customer_id: activeCustomerId }, function(response) {
                // Парсим JSON-ответ
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    // Если есть новый HTML — добавляем в контейнер
                    if (res.html && res.html.trim() !== '') {
                        $('#customersSidebarList').append(res.html);
                        // Увеличиваем offset для следующей порции
                        custOffset += custLimit;
                    }
                    // Обновляем флаг наличия записей
                    custHasMore = res.has_more;
                }
                // Разблокируем загрузку
                custIsLoading = false;
            }).fail(function() {
                // При ошибке — разблокируем загрузку
                custIsLoading = false;
            });
        }
    });
};

/**
 * Настраивает бесконечный скролл списка задач (проектов) заказчика.
 * При прокрутке до конца — подгружает следующую порцию дерева задач.
 */
window.initInfiniteScrollTasks = function() {
    // Текущий offset для пагинации задач
    var taskOffset = window.globalPerPage || 25;
    // Размер порции загрузки
    var taskLimit = window.globalPerPage || 25;
    // Читаем флаг has_more из data-атрибута контейнера
    var taskHasMore = $('#customerTasksContainer').data('has-more') == '1';
    // Флаг текущей загрузки
    var taskIsLoading = false;

    /**
     * Сбрасывает пагинацию задач (вызывается после переключения фильтров).
     * @param {boolean} hasMore — Есть ли ещё записи
     */
    window.resetTasksScroll = function(hasMore) {
        // Сбрасываем offset на начало
        taskOffset = window.globalPerPage || 25;
        // Обновляем флаг наличия записей
        taskHasMore = hasMore;
        // Разблокируем загрузку
        taskIsLoading = false;
    };

    // Привязываем обработчик scroll к контейнеру задач
    $('#customerTasksContainer').off('scroll').on('scroll', function() {
        // Проверяем наличие контейнера в DOM
        if ($('#customerTasksContainer').length === 0) return;
        // Проверяем условия загрузки
        if (!taskHasMore || taskIsLoading) return;

        // Текущая позиция прокрутки
        var scrollTop = $(this).scrollTop();
        // Полная высота контента
        var scrollHeight = $(this)[0].scrollHeight;
        // Видимая высота контейнера
        var innerHeight = $(this).innerHeight();

        // Если до конца осталось менее 30px — подгружаем
        if (scrollTop + innerHeight >= scrollHeight - 30) {
            // Блокируем повторную загрузку
            taskIsLoading = true;
            // Получаем ID активного заказчика
            var activeCustomerId = window.activeCustomerId || null;
            // Проверяем, включён ли показ закрытых задач
            var showClosed = $('#showClosedTasksToggle').is(':checked') ? 1 : 0;

            // Отправляем AJAX-запрос на подгрузку задач
            $.post(window.globalApi.load_customer_tasks, {
                customer_id: activeCustomerId,
                offset: taskOffset,
                show_closed: showClosed
            }, function(response) {
                // Парсим JSON-ответ
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    // Если есть новый HTML — добавляем в дерево
                    if (res.html && res.html.trim() !== '') {
                        // Удаляем заглушку «Нет задач» если была
                        $('#customerTasksContainer').find('.empty-tasks-label').remove();

                        // Ищем корневой ul дерева задач
                        var ul = $('#customerTasksContainer').find('> ul.task-tree-root');
                        if (ul.length > 0) {
                            // Если дерево уже есть — добавляем элементы li
                            var newItems = $(res.html).find('> li');
                            ul.append(newItems);
                        } else {
                            // Если дерева нет — заменяем весь контент
                            $('#customerTasksContainer').html(res.html);
                        }
                        // Увеличиваем offset для следующей порции
                        taskOffset += taskLimit;
                    }
                    // Обновляем флаг наличия записей
                    taskHasMore = res.has_more;
                }
                // Разблокируем загрузку
                taskIsLoading = false;
            }).fail(function() {
                // При ошибке — разблокируем загрузку
                taskIsLoading = false;
            });
        }
    });
};

/**
 * Инициализирует переключатель отображения закрытых/актуальных задач
 * и обработчик сворачивания/разворачивания подзадач в дереве.
 */
window.initClosedTasksToggle = function() {
    // --- Обработчик переключателя «Показывать закрытые заказы» ---
    $('#showClosedTasksToggle').off('change').on('change', function() {
        // Получаем ID активного заказчика
        var activeCustomerId = window.activeCustomerId || null;
        // Если заказчик не выбран — выходим
        if (!activeCustomerId) return;

        // Получаем состояние чекбокса
        var showClosed = $(this).is(':checked') ? 1 : 0;
        // Получаем контейнер задач
        var container = $('#customerTasksContainer');

        // Показываем индикатор загрузки
        container.html('<div class="py-4 text-center text-gray-400 italic">Загрузка...</div>');

        // Загружаем задачи с новым фильтром (с offset 0)
        $.post(window.globalApi.load_customer_tasks, {
            customer_id: activeCustomerId,
            offset: 0,
            show_closed: showClosed
        }, function(response) {
            // Парсим JSON-ответ
            var res = JSON.parse(response);
            if (res.status === 'success') {
                // Если есть HTML — вставляем в контейнер
                if (res.html && res.html.trim() !== '') {
                    container.html(res.html);
                } else {
                    // Если пусто — показываем заглушку
                    container.html('<p class="text-sm text-gray-400 italic empty-tasks-label">Нет задач</p>');
                }
                // Сбрасываем пагинацию бесконечного скролла
                if (typeof window.resetTasksScroll === 'function') {
                    window.resetTasksScroll(res.has_more);
                }
            } else {
                // Показываем ошибку
                container.html('<p class="text-sm text-red-500 italic">Ошибка загрузки задач</p>');
            }
        }).fail(function() {
            // Показываем ошибку сети
            container.html('<p class="text-sm text-red-500 italic">Ошибка отправки запроса</p>');
        });
    });

    // --- Делегированный обработчик сворачивания/разворачивания подзадач ---
    $(document).off('click', '.toggle-children').on('click', '.toggle-children', function() {
        // Находим родительский li (текущую задачу)
        var li = $(this).closest('li');
        // Находим иконку раскрытия
        var icon = $(this).find('.icon-expand');
        // Находим список дочерних задач
        var childrenList = li.find('> ul.task-children');

        // Плавно показываем/скрываем дочерние задачи (150мс)
        childrenList.slideToggle(150);

        // Переключаем поворот иконки
        if (icon.hasClass('rotate-90')) {
            // Убираем поворот (свернуть)
            icon.removeClass('rotate-90');
        } else {
            // Добавляем поворот (развернуть)
            icon.addClass('rotate-90');
        }
    });
};

/**
 * Инициализирует аккордеоны для карточек технических заданий (ТЗ).
 * Клик по шапке карточки раскрывает/сворачивает содержимое.
 */
window.initSpecAccordions = function() {
    // Делегированный обработчик клика по шапке карточки ТЗ
    $(document).off('click', '.toggle-spec').on('click', '.toggle-spec', function(e) {
        // Защита от кликов по элементам управления (кнопки, ссылки, инпуты)
        if ($(e.target).closest('button, a, input, textarea').length) {
            return;
        }

        // Находим карточку ТЗ
        var specCard = $(this).closest('.spec-card');
        // Находим сворачиваемое тело карточки
        var body = specCard.find('.spec-body');
        // Находим иконку раскрытия
        var icon = $(this).find('.icon-expand');

        // Плавно показываем/скрываем тело карточки (150мс)
        body.slideToggle(150);

        // Переключаем поворот иконки
        if (icon.hasClass('rotate-180')) {
            // Убираем поворот (свернуть)
            icon.removeClass('rotate-180');
        } else {
            // Добавляем поворот (развернуть)
            icon.addClass('rotate-180');
        }
    });
};
