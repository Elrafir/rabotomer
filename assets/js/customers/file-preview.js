/**
 * Модуль предпросмотра файлов для раздела «Заказчики».
 * Реализует поповер при наведении мыши и полноэкранный просмотрщик по клику.
 * Поддерживает изображения (img) и текстовые файлы (AJAX-загрузка).
 */

/**
 * Инициализирует интерактивный предпросмотр файлов.
 * Создаёт поповер-контейнер и привязывает делегированные обработчики
 * для mouseenter, mouseleave, mousemove и click на .file-preview-trigger.
 */
window.initFilePreviews = function() {
    // Перемещаем модальные окна в body, чтобы они не перекрывались элементами интерфейса
    $('#specFormModal, #addCustomerModal, #editCustomerModal, #customerInfoModal, #docViewerModal').appendTo('body');

    // Создаём контейнер поповера в body, если его ещё нет
    if ($('#file-preview-popover').length === 0) {
        // Генерируем HTML поповера
        var popoverHtml =
            '<div id="file-preview-popover">' +
                '<div class="preview-title">' +
                    '<span id="preview-filename-title">Предпросмотр</span>' +
                    '<span id="preview-type-badge" class="bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded text-[10px]"></span>' +
                '</div>' +
                '<div id="preview-body-container" class="flex-grow flex items-center justify-center min-h-[50px]">' +
                '</div>' +
            '</div>';
        // Добавляем поповер в body
        $('body').append(popoverHtml);
    }

    // Кэшируем jQuery-ссылку на поповер
    var popover = $('#file-preview-popover');
    // Таймер задержки перед показом поповера
    var hoverTimeout = null;
    // Текущий активный AJAX-запрос (для отмены при уходе курсора)
    var activeRequest = null;
    // URL для AJAX-загрузки текстового превью (строим из URL загрузки файла)
    var ajaxUrl = window.globalApi.upload_file.replace('upload_file', 'get_text_preview_ajax');

    // --- Делегированный обработчик наведения мыши на файл ---
    $(document)
        .off('mouseenter', '.file-preview-trigger')
        .on('mouseenter', '.file-preview-trigger', function(e) {
            // Получаем jQuery-обёртку триггера
            var trigger = $(this);
            // Считываем имя файла из data-атрибута
            var fileName = trigger.data('file-name') || '';
            // Считываем URL файла
            var fileUrl = trigger.data('url') || '';
            // Считываем ID файла (для обычных файлов)
            var fileId = trigger.data('file-id') || '';
            // Считываем ID ТЗ (для внешних файлов)
            var specId = trigger.data('spec-id') || '';
            // Считываем флаг «это ссылка»
            var isLink = parseInt(trigger.data('is-link') || 0);
            // Считываем флаг «внешний файл»
            var isExternal = parseInt(trigger.data('is-external') || 0);

            // Для ссылок и пустых имён — не показываем поповер
            if (!fileName || isLink) return;

            // Получаем расширение файла
            var ext = CustomersUtils.getFileExt(fileName);
            // Проверяем, является ли файл изображением
            var isImage = CustomersUtils.isImageFile(ext);
            // Проверяем, является ли файл текстовым
            var isText = CustomersUtils.isTextFile(ext);

            // Если файл ни изображение, ни текст — не показываем поповер
            if (!isImage && !isText) return;

            // Отменяем предыдущий таймер показа (если курсор быстро перешёл)
            if (hoverTimeout) clearTimeout(hoverTimeout);
            // Отменяем предыдущий AJAX-запрос
            if (activeRequest) activeRequest.abort();

            // Запускаем таймер задержки 250мс перед показом поповера
            hoverTimeout = setTimeout(function() {
                // Устанавливаем имя файла в заголовок поповера
                $('#preview-filename-title').text(fileName);
                // Устанавливаем бейдж расширения
                $('#preview-type-badge').text(ext.toUpperCase());

                // Получаем контейнер тела поповера
                var bodyContainer = $('#preview-body-container');
                // Очищаем предыдущее содержимое
                bodyContainer.empty();

                if (isImage) {
                    // Для изображений — показываем тег img
                    bodyContainer.html('<img src="' + fileUrl + '" alt="' + fileName + '" />');
                    // Показываем поповер
                    showPopover(e);
                } else if (isText) {
                    // Для текстовых файлов — показываем спиннер пока грузится контент
                    bodyContainer.html(
                        '<div class="preview-spinner">' +
                            '<svg class="w-6 h-6 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">' +
                                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                                '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>' +
                            '</svg>' +
                        '</div>'
                    );
                    // Показываем поповер со спиннером
                    showPopover(e);

                    // Формируем параметры AJAX-запроса в зависимости от типа файла
                    var ajaxData = {};
                    if (isExternal) {
                        // Для внешних файлов — передаём specId и имя файла
                        ajaxData = { spec_id: specId, file: fileName };
                    } else {
                        // Для обычных файлов — передаём ID файла
                        ajaxData = { file_id: fileId };
                    }

                    // Загружаем текстовое превью через AJAX
                    activeRequest = $.ajax({
                        url: ajaxUrl,
                        type: 'GET',
                        data: ajaxData,
                        dataType: 'json',
                        // Обработчик успешного ответа
                        success: function(res) {
                            if (res.status === 'success') {
                                // Экранируем HTML в контенте для безопасного отображения
                                var safeContent = $('<div/>').text(res.content).html();
                                // Отображаем текстовое содержимое в pre-блоке
                                bodyContainer.html('<pre>' + safeContent + (res.truncated ? '\n...' : '') + '</pre>');
                            } else {
                                // Показываем ошибку из ответа сервера
                                bodyContainer.html('<div class="text-red-500 text-xs p-2">' + res.message + '</div>');
                            }
                        },
                        // Обработчик ошибки AJAX
                        error: function(xhr, status, error) {
                            // Не показываем ошибку если запрос был отменён пользователем
                            if (status !== 'abort') {
                                bodyContainer.html('<div class="text-red-500 text-xs p-2">Ошибка загрузки</div>');
                            }
                        }
                    });
                }
            }, 250);
        });

    // --- Делегированный обработчик ухода курсора с файла ---
    $(document)
        .off('mouseleave', '.file-preview-trigger')
        .on('mouseleave', '.file-preview-trigger', function() {
            // Отменяем таймер показа поповера
            if (hoverTimeout) clearTimeout(hoverTimeout);
            // Отменяем текущий AJAX-запрос
            if (activeRequest) activeRequest.abort();
            // Скрываем поповер
            hidePopover();
        });

    // --- Делегированный обработчик движения мыши (для позиционирования поповера) ---
    $(document)
        .off('mousemove', '.file-preview-trigger')
        .on('mousemove', '.file-preview-trigger', function(e) {
            // Обновляем позицию поповера при движении мыши
            positionPopover(e);
        });

    // --- Делегированный обработчик клика по файлу (полноэкранный просмотр) ---
    $(document)
        .off('click', '.file-preview-trigger')
        .on('click', '.file-preview-trigger', function(e) {
            // Если клик по управляющим кнопкам (скачивание/удаление) — не перехватываем
            if ($(e.target).closest('.delete-file-btn, .download-icon-btn, .download-external-link').length) {
                return;
            }

            // Получаем jQuery-обёртку триггера
            var trigger = $(this);
            // Считываем имя файла
            var fileName = trigger.data('file-name') || '';
            // Считываем флаг «это ссылка»
            var isLink = parseInt(trigger.data('is-link') || 0);

            // Для ссылок — открываем в новой вкладке
            if (isLink) {
                var url = trigger.data('url');
                window.open(url, '_blank');
                return;
            }

            // Предотвращаем стандартное поведение (переход по ссылке)
            e.preventDefault();
            // Останавливаем всплытие события
            e.stopPropagation();

            // Скрываем поповер при открытии полноэкранного просмотра
            hidePopover();

            // Устанавливаем заголовок просмотрщика
            $('#docViewerTitle').text(fileName);
            // Показываем полноэкранный просмотрщик
            $('#docViewerModal').removeClass('hidden');

            // Определяем тип файла по расширению
            var ext = CustomersUtils.getFileExt(fileName);
            // Проверяем тип файла
            var isImage = CustomersUtils.isImageFile(ext);
            var isText = CustomersUtils.isTextFile(ext);

            // Получаем контейнер контента просмотрщика
            var contentContainer = $('#docViewerContent');
            // Очищаем предыдущее содержимое
            contentContainer.empty();

            if (isImage) {
                // Для изображений — показываем полноэкранное изображение
                contentContainer.html(
                    '<div class="flex-grow flex items-center justify-center min-h-0">' +
                        '<img src="' + trigger.data('url') + '" class="max-w-full max-h-full object-contain rounded-2xl shadow-md" />' +
                    '</div>'
                );
            } else if (isText) {
                // Для текстовых файлов — показываем спиннер и загружаем AJAX
                var spinner = $('#docViewerSpinner');
                spinner.removeClass('hidden');

                // Считываем параметры файла
                var fileId = trigger.data('file-id') || '';
                var specId = trigger.data('spec-id') || '';
                var isExternal = parseInt(trigger.data('is-external') || 0);

                // Формируем параметры AJAX-запроса
                var ajaxData = {};
                if (isExternal) {
                    ajaxData = { spec_id: specId, file: fileName };
                } else {
                    ajaxData = { file_id: fileId };
                }

                // Загружаем полный контент файла
                $.ajax({
                    url: ajaxUrl,
                    type: 'GET',
                    data: ajaxData,
                    dataType: 'json',
                    // Обработчик успешного ответа
                    success: function(res) {
                        // Скрываем спиннер
                        spinner.addClass('hidden');
                        if (res.status === 'success') {
                            // Экранируем HTML для безопасности
                            var safeContent = $('<div/>').text(res.content).html();
                            // Отображаем в pre-блоке с моноширинным шрифтом
                            contentContainer.html(
                                '<pre class="w-full flex-grow min-h-0 p-6 bg-gray-50 border border-gray-100 rounded-2xl overflow-auto text-xs md:text-sm font-mono text-gray-800 whitespace-pre-wrap word-break-all">' +
                                    safeContent +
                                    (res.truncated ? '\n[Содержимое обрезано...] Полная версия будет доступна после интеграции парсеров.' : '') +
                                '</pre>'
                            );
                        } else {
                            // Показываем ошибку
                            contentContainer.html('<div class="flex-grow flex items-center justify-center text-red-500 font-semibold min-h-0">' + res.message + '</div>');
                        }
                    },
                    // Обработчик ошибки AJAX
                    error: function() {
                        // Скрываем спиннер
                        spinner.addClass('hidden');
                        // Показываем ошибку
                        contentContainer.html('<div class="flex-grow flex items-center justify-center text-red-500 font-semibold min-h-0">Ошибка загрузки файла</div>');
                    }
                });
            } else {
                // Для неподдерживаемых форматов — заглушка со ссылкой на скачивание
                contentContainer.html(
                    '<div class="flex-grow flex flex-col items-center justify-center p-8 text-center min-h-0">' +
                        '<span class="text-5xl mb-4 block">📁</span>' +
                        '<p class="text-gray-700 text-lg font-bold mb-2">Просмотрщик этого формата находится в разработке</p>' +
                        '<p class="text-gray-400 text-sm mb-4">Вы можете скачать этот файл на свое устройство</p>' +
                        '<a href="' + trigger.data('url') + '" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl transition-all shadow-md">' +
                            '📥 Скачать файл' +
                        '</a>' +
                    '</div>'
                );
            }
        });

    /**
     * Показывает поповер с анимацией появления.
     * @param {Event} e — Событие мыши для позиционирования
     */
    function showPopover(e) {
        // Показываем поповер через flex-display
        popover.css('display', 'flex');
        // Позиционируем поповер у курсора
        positionPopover(e);
        // Добавляем класс видимости с небольшой задержкой для CSS-анимации
        setTimeout(function() {
            popover.addClass('visible');
        }, 10);
    }

    /**
     * Скрывает поповер.
     */
    function hidePopover() {
        // Убираем класс видимости (CSS-анимация исчезновения)
        popover.removeClass('visible');
        // Полностью скрываем поповер
        popover.hide();
    }

    /**
     * Позиционирует поповер рядом с курсором мыши.
     * Учитывает границы экрана для предотвращения выхода за пределы viewport.
     * @param {Event} e — Событие мыши с координатами clientX/clientY
     */
    function positionPopover(e) {
        // Координаты курсора мыши
        var mouseX = e.clientX;
        var mouseY = e.clientY;

        // Размеры поповера (с дефолтными значениями)
        var popoverWidth = popover.outerWidth() || 320;
        var popoverHeight = popover.outerHeight() || 200;

        // Отступы от курсора
        var offsetX = 15;
        var offsetY = 15;

        // Позиция поповера (справа-снизу от курсора по умолчанию)
        var posX = mouseX + offsetX;
        var posY = mouseY + offsetY;

        // Если поповер выходит за правую границу — ставим слева от курсора
        if (posX + popoverWidth > window.innerWidth) {
            posX = mouseX - popoverWidth - offsetX;
        }

        // Если поповер выходит за нижнюю границу — ставим сверху от курсора
        if (posY + popoverHeight > window.innerHeight) {
            posY = mouseY - popoverHeight - offsetY;
        }

        // Защита от отрицательных координат
        if (posX < 0) posX = 10;
        if (posY < 0) posY = 10;

        // Устанавливаем CSS-позицию поповера
        popover.css({
            left: posX + 'px',
            top: posY + 'px'
        });
    }
};
