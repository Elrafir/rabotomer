/**
 * Модуль загрузки файлов для раздела «Заказчики».
 * Обеспечивает AJAX-загрузку файлов, удаление, прикрепление ссылок,
 * скачивание по URL, обработку drag-and-drop и file input.
 */

/**
 * Загружает файлы на сервер через AJAX и FormData.
 * Поддерживает прогресс-бар и рекурсивную мультизагрузку.
 * @param {FileList|Array} files — Список файлов для загрузки
 * @param {number|string} specId — ID технического задания
 */
window.uploadFiles = function(files, specId) {
    // Создаём объект FormData для multipart-загрузки
    var formData = new FormData();
    // Добавляем ID ТЗ в форму
    formData.append('spec_id', specId);
    // Загружаем первый файл из списка (остальные — рекурсивно)
    formData.append('file', files[0]);

    // Получаем элементы прогресс-бара для текущего ТЗ
    var progressContainer = $('#upload-progress-container-' + specId);
    var progressBar = $('#upload-progress-' + specId);

    // Показываем контейнер прогресса
    progressContainer.removeClass('hidden');
    // Сбрасываем прогресс-бар на 0%
    progressBar.css('width', '0%');

    // Отправляем AJAX-запрос с загрузкой файла
    $.ajax({
        // URL для загрузки файла из глобальной конфигурации
        url: window.globalApi.upload_file,
        // HTTP-метод
        type: 'POST',
        // Данные формы
        data: formData,
        // Не устанавливать Content-Type (FormData сделает это сам)
        contentType: false,
        // Не обрабатывать данные (FormData — бинарные)
        processData: false,
        // Получаем ответ как текст (для safeParseJson)
        dataType: 'text',
        // Настраиваем XMLHttpRequest для отслеживания прогресса
        xhr: function() {
            // Создаём новый XMLHttpRequest
            var xhr = new window.XMLHttpRequest();
            // Подписываемся на событие progress загрузки
            xhr.upload.addEventListener('progress', function(evt) {
                // Проверяем, можно ли вычислить прогресс
                if (evt.lengthComputable) {
                    // Вычисляем процент загрузки
                    var percentComplete = (evt.loaded / evt.total) * 100;
                    // Обновляем ширину прогресс-бара
                    progressBar.css('width', percentComplete + '%');
                }
            }, false);
            // Возвращаем настроенный XHR
            return xhr;
        },
        // Обработчик успешной загрузки
        success: function(response) {
            // Скрываем прогресс-бар после завершения
            progressContainer.addClass('hidden');
            try {
                // Парсим JSON-ответ с очисткой от PHP-нотисов
                var res = CustomersUtils.safeParseJson(response);

                // Проверяем статус ответа
                if (res.status === 'success') {
                    // Получаем контейнер списка файлов текущего ТЗ
                    var fileList = $('#file-list-' + specId);
                    // Удаляем заглушку «Нет файлов» если она есть
                    fileList.find('.empty-files-label').remove();

                    // Вычисляем размер файла в KB
                    var sizeKb = Math.round(files[0].size / 102.4) / 10;
                    // Генерируем HTML нового элемента файла
                    // ВАЖНО: класс file-preview-trigger + data-атрибуты обязательны
                    // для работы предпросмотра при наведении (делегированные обработчики)
                    var fileHtml =
                        '<div id="file-item-' + res.file_id + '" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">' +
                            '<span>' + res.icon + '</span>' +
                            '<span class="file-preview-trigger text-gray-700 font-medium cursor-help hover:text-blue-600 transition-colors"' +
                                  ' data-file-id="' + res.file_id + '"' +
                                  ' data-file-name="' + res.orig_name + '"' +
                                  ' data-url="' + res.download_url + '"' +
                                  ' data-is-link="0"' +
                                  ' data-is-external="0">' + res.orig_name + '</span>' +
                            '<span class="text-gray-400 font-mono">(' + sizeKb + ' KB)</span>' +
                            '<a href="' + res.download_url + '" class="download-icon-btn text-blue-500 hover:text-blue-700" title="Скачать файл">📥</a>' +
                            '<button class="delete-file-btn text-red-400 hover:text-red-600" data-file-id="' + res.file_id + '" title="Удалить">✖</button>' +
                        '</div>';
                    // Добавляем элемент в список файлов
                    fileList.append(fileHtml);

                    // Если в очереди остались файлы — загружаем рекурсивно
                    if (files.length > 1) {
                        // Создаём массив из оставшихся файлов (без первого)
                        var remainingFiles = Array.prototype.slice.call(files, 1);
                        // Рекурсивно вызываем загрузку для оставшихся
                        window.uploadFiles(remainingFiles, specId);
                    }
                } else {
                    // Показываем ошибку из ответа сервера
                    alert('Ошибка при загрузке: ' + res.message);
                }
            } catch (err) {
                // Логируем ошибку парсинга в консоль
                console.error(err);
                // Показываем пользователю системную ошибку
                alert('Произошла системная ошибка при загрузке файла');
            }
        },
        // Обработчик ошибки сети/сервера
        error: function(xhr, status, error) {
            // Скрываем прогресс-бар
            progressContainer.addClass('hidden');
            // Показываем ошибку с HTTP-статусом
            alert('Ошибка отправки файла на сервер: ' + xhr.status + ' ' + error);
        }
    });
};

/**
 * Удаляет прикреплённый файл ТЗ через AJAX с подтверждением.
 * @param {number|string} fileId — ID файла для удаления
 */
window.deleteSpecFile = function(fileId) {
    // Запрашиваем подтверждение у пользователя
    if (!confirm('Вы уверены, что хотите удалить этот файл?')) {
        // Пользователь отменил — выходим
        return;
    }

    // Отправляем POST-запрос на удаление файла
    $.post(window.globalApi.delete_spec_file + fileId, {}, function(response) {
        try {
            // Парсим JSON-ответ с очисткой от PHP-нотисов
            var res = CustomersUtils.safeParseJson(response);

            // Проверяем статус ответа
            if (res.status === 'success') {
                // Находим DOM-элемент файла по ID
                var item = $('#file-item-' + fileId);
                // Запоминаем родительский контейнер
                var parent = item.parent();
                // Удаляем элемент из DOM
                item.remove();

                // Если файлов не осталось — показываем заглушку
                if (parent.children().length === 0) {
                    parent.html('<span class="text-xs text-gray-400 italic empty-files-label">Нет прикрепленных файлов</span>');
                }
            } else {
                // Показываем ошибку из ответа сервера
                alert(res.message);
            }
        } catch(e) {
            // Логируем ошибку парсинга
            console.error(e);
            // Показываем системную ошибку
            alert('Произошла ошибка при удалении файла');
        }
    }, 'text');
};

/**
 * Прикрепляет внешнюю ссылку к ТЗ через AJAX.
 * @param {number|string} specId — ID технического задания
 */
window.attachLink = function(specId) {
    // Получаем поле ввода URL для текущего ТЗ
    var urlInput = $('#url-input-' + specId);
    // Получаем поле ввода заголовка ссылки
    var titleInput = $('#url-title-' + specId);
    // Считываем и обрезаем URL
    var url = urlInput.val().trim();
    // Считываем и обрезаем заголовок
    var title = titleInput.val().trim();

    // Проверяем, что URL не пустой
    if (url === '') {
        // Показываем предупреждение
        alert('Пожалуйста, введите URL-адрес ссылки.');
        return;
    }

    // Отправляем POST-запрос на добавление ссылки
    $.post(window.globalApi.add_link, { spec_id: specId, url: url, title: title }, function(response) {
        try {
            // Парсим JSON-ответ с очисткой от PHP-нотисов
            var res = CustomersUtils.safeParseJson(response);

            // Проверяем статус ответа
            if (res.status === 'success') {
                // Получаем контейнер списка файлов текущего ТЗ
                var fileList = $('#file-list-' + specId);
                // Удаляем заглушку «Нет файлов»
                fileList.find('.empty-files-label').remove();

                // Генерируем HTML элемента ссылки
                var itemHtml =
                    '<div id="file-item-' + res.file_id + '" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">' +
                        '<span>' + res.icon + '</span>' +
                        '<a href="' + res.url + '" target="_blank" class="text-blue-600 hover:underline font-medium">' + res.orig_name + '</a>' +
                        '<span class="text-gray-400 font-mono">(Ссылка)</span>' +
                        '<button class="delete-file-btn text-red-400 hover:text-red-600" data-file-id="' + res.file_id + '" title="Удалить">✖</button>' +
                    '</div>';
                // Добавляем элемент в список
                fileList.append(itemHtml);
                // Очищаем поля ввода
                urlInput.val('');
                titleInput.val('');
            } else {
                // Показываем ошибку из ответа сервера
                alert(res.message);
            }
        } catch(e) {
            // Логируем ошибку парсинга
            console.error(e);
            // Показываем системную ошибку
            alert('Не удалось добавить ссылку');
        }
    }, 'text');
};

/**
 * Скачивает файл по внешней ссылке и сохраняет на сервере.
 * @param {number|string} specId — ID технического задания
 */
window.downloadFromUrl = function(specId) {
    // Получаем поле ввода URL
    var urlInput = $('#url-input-' + specId);
    // Считываем и обрезаем URL
    var url = urlInput.val().trim();

    // Проверяем, что URL не пустой
    if (url === '') {
        // Показываем предупреждение
        alert('Пожалуйста, введите URL-адрес для скачивания.');
        return;
    }

    // Получаем элементы прогресс-бара для текущего ТЗ
    var progressContainer = $('#upload-progress-container-' + specId);
    var progressBar = $('#upload-progress-' + specId);

    // Показываем прогресс-бар (100% — бесконечная загрузка с сервера)
    progressContainer.removeClass('hidden');
    progressBar.css('width', '100%');

    // Отправляем POST-запрос на скачивание файла с внешнего URL
    $.post(window.globalApi.download_url, { spec_id: specId, url: url }, function(response) {
        // Скрываем прогресс-бар после получения ответа
        progressContainer.addClass('hidden');
        try {
            // Парсим JSON-ответ с очисткой от PHP-нотисов
            var res = CustomersUtils.safeParseJson(response);

            // Проверяем статус ответа
            if (res.status === 'success') {
                // Получаем контейнер списка файлов
                var fileList = $('#file-list-' + specId);
                // Удаляем заглушку «Нет файлов»
                fileList.find('.empty-files-label').remove();

                // Вычисляем размер файла в KB
                var sizeKb = Math.round(res.file_size / 102.4) / 10;
                // Генерируем HTML элемента файла
                // ВАЖНО: file-preview-trigger + data-атрибуты для предпросмотра
                var itemHtml =
                    '<div id="file-item-' + res.file_id + '" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">' +
                        '<span>' + res.icon + '</span>' +
                        '<span class="file-preview-trigger text-gray-700 font-medium cursor-help hover:text-blue-600 transition-colors"' +
                              ' data-file-id="' + res.file_id + '"' +
                              ' data-file-name="' + res.orig_name + '"' +
                              ' data-url="' + res.download_url + '"' +
                              ' data-is-link="0"' +
                              ' data-is-external="0">' + res.orig_name + '</span>' +
                        '<span class="text-gray-400 font-mono">(' + sizeKb + ' KB)</span>' +
                        '<a href="' + res.download_url + '" class="download-icon-btn text-blue-500 hover:text-blue-700" title="Скачать">📥</a>' +
                        '<button class="delete-file-btn text-red-400 hover:text-red-600" data-file-id="' + res.file_id + '" title="Удалить">✖</button>' +
                    '</div>';
                // Добавляем элемент в список файлов
                fileList.append(itemHtml);
                // Очищаем поля ввода URL и заголовка
                urlInput.val('');
                $('#url-title-' + specId).val('');
            } else {
                // Показываем ошибку из ответа сервера
                alert(res.message);
            }
        } catch(e) {
            // Логируем ошибку парсинга
            console.error(e);
            // Показываем системную ошибку
            alert('Не удалось скачать файл по ссылке.');
        }
    }, 'text');
};

/**
 * Обрабатывает перетаскивание файлов в дроп-зону ТЗ.
 * @param {DragEvent} e — Событие drop
 * @param {number|string} specId — ID технического задания
 */
window.handleFileDrop = function(e, specId) {
    // Предотвращаем стандартное поведение браузера (открытие файла)
    e.preventDefault();
    // Снимаем визуальное выделение дроп-зоны
    $(e.currentTarget).removeClass('border-blue-400');
    // Получаем список перетащенных файлов
    var files = e.dataTransfer.files;
    // Если есть файлы — запускаем загрузку
    if (files.length > 0) {
        window.uploadFiles(files, specId);
    }
};

/**
 * Обрабатывает выбор файлов через input[type=file].
 * @param {Event} e — Событие change input'а
 * @param {number|string} specId — ID технического задания
 */
window.handleFileSelect = function(e, specId) {
    // Получаем список выбранных файлов
    var files = e.target.files;
    // Если есть файлы — запускаем загрузку
    if (files.length > 0) {
        window.uploadFiles(files, specId);
    }
};

/**
 * Инициализирует делегированные обработчики для файловых операций.
 * Привязывает drag-and-drop, клик по дроп-зоне, удаление файлов,
 * прикрепление ссылок и скачивание по URL.
 */
window.initFileUploadHandlers = function() {
    // --- Делегированный обработчик dragover для дроп-зон ---
    $(document).off('dragover', '.file-dropzone').on('dragover', '.file-dropzone', function(e) {
        // Предотвращаем стандартное поведение (разрешаем drop)
        e.preventDefault();
        // Визуально выделяем дроп-зону
        $(this).addClass('border-blue-400');
    });

    // --- Делегированный обработчик dragleave для дроп-зон ---
    $(document).off('dragleave', '.file-dropzone').on('dragleave', '.file-dropzone', function(e) {
        // Снимаем визуальное выделение
        $(this).removeClass('border-blue-400');
    });

    // --- Делегированный обработчик drop для дроп-зон ---
    $(document).off('drop', '.file-dropzone').on('drop', '.file-dropzone', function(e) {
        // Считываем specId из data-атрибута зоны
        var specId = $(this).data('spec-id');
        // Обрабатываем перетаскивание файлов
        window.handleFileDrop(e.originalEvent, specId);
    });

    // --- Делегированный обработчик клика по дроп-зоне (открытие диалога выбора файлов) ---
    $(document).off('click', '.file-dropzone').on('click', '.file-dropzone', function(e) {
        // Если клик был не по input — открываем диалог выбора файлов
        if (e.target.tagName.toLowerCase() !== 'input') {
            // Считываем specId из data-атрибута зоны
            var specId = $(this).data('spec-id');
            // Программно кликаем по скрытому file input
            document.getElementById('file-input-' + specId).click();
        }
    });

    // --- Делегированный обработчик change для file input'ов ---
    $(document).off('change', 'input[type="file"][data-spec-id]').on('change', 'input[type="file"][data-spec-id]', function(e) {
        // Считываем specId из data-атрибута
        var specId = $(this).data('spec-id');
        // Обрабатываем выбор файлов
        window.handleFileSelect(e, specId);
    });

    // --- Делегированный обработчик удаления файлов ---
    $(document).off('click', '.delete-file-btn').on('click', '.delete-file-btn', function(e) {
        // Предотвращаем всплытие (чтобы не сработал обработчик карточки)
        e.preventDefault();
        e.stopPropagation();
        // Считываем ID файла из data-атрибута
        var fileId = $(this).data('file-id');
        // Вызываем удаление файла
        window.deleteSpecFile(fileId);
    });

    // --- Делегированный обработчик кнопки прикрепления ссылки ---
    $(document).off('click', '.attach-link-btn').on('click', '.attach-link-btn', function(e) {
        // Предотвращаем стандартное поведение кнопки
        e.preventDefault();
        // Считываем ID ТЗ из data-атрибута
        var specId = $(this).data('spec-id');
        // Прикрепляем ссылку
        window.attachLink(specId);
    });

    // --- Делегированный обработчик кнопки скачивания по URL ---
    $(document).off('click', '.download-from-url-btn').on('click', '.download-from-url-btn', function(e) {
        // Предотвращаем стандартное поведение кнопки
        e.preventDefault();
        // Считываем ID ТЗ из data-атрибута
        var specId = $(this).data('spec-id');
        // Скачиваем файл по URL
        window.downloadFromUrl(specId);
    });
};
