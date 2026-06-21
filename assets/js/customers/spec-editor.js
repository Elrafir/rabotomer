/**
 * Модуль инициализации Quill WYSIWYG-редактора для формы ТЗ.
 * Создаёт единственный экземпляр редактора window.specQuill.
 * Обеспечивает фоллбэк на textarea если Quill недоступен (оффлайн).
 */

/**
 * Инициализирует Quill-редактор для единственной формы ТЗ.
 * Вызывается при первой загрузке и при повторной (SPA-переход).
 */
window.initQuillEditor = function() {
    // Проверяем, доступна ли библиотека Quill
    if (typeof Quill !== 'undefined') {

        // Регистрируем плагин ресайза картинок (если подключён)
        // Даёт ручки для перетягивания размера + панель выравнивания (лево/центр/право)
        if (typeof ImageResize !== 'undefined') {
            Quill.register('modules/imageResize', ImageResize.default || ImageResize);
        }

        // Конфигурация тулбара Quill: полный «статейный» набор для описания ТЗ
        var options = {
            // Используем тему «снег» (верхний тулбар)
            theme: 'snow',
            modules: {
                // Набор инструментов тулбара
                toolbar: [
                    // Выбор уровня заголовка (H1, H2, H3, обычный текст)
                    [{ 'header': [1, 2, 3, false] }],
                    // Размер шрифта (мелкий, обычный, крупный, огромный)
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    // Жирный, курсив, подчёркивание, зачёркнутый
                    ['bold', 'italic', 'underline', 'strike'],
                    // Цвет текста и цвет фона (палитра по умолчанию)
                    [{ 'color': [] }, { 'background': [] }],
                    // Верхний и нижний индекс (надстрочный / подстрочный)
                    [{ 'script': 'sub' }, { 'script': 'super' }],
                    // Нумерованный и маркированный списки
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    // Увеличение / уменьшение отступа (вложенность)
                    [{ 'indent': '-1' }, { 'indent': '+1' }],
                    // Выравнивание текста (лево, центр, право, по ширине)
                    [{ 'align': [] }],
                    // Цитата (blockquote) и блок кода (code-block)
                    ['blockquote', 'code-block'],
                    // Вставка ссылки, изображения (по URL) и видео (embed)
                    ['link', 'image', 'video'],
                    // Кнопка очистки форматирования
                    ['clean']
                ],
                // Модуль ресайза картинок: ручки + выравнивание
                imageResize: {}
            },
            // Плейсхолдер внутри пустого редактора
            placeholder: 'Детальное описание проекта, требования, объёмы и задачи...'
        };

        // Проверяем наличие контейнера редактора в DOM
        if ($('#spec-editor').length > 0) {
            // Создаём единственный экземпляр Quill-редактора
            window.specQuill = new Quill('#spec-editor', options);

            // Переопределяем обработчик кнопки «Изображение»:
            // Стандартный Quill вставляет файл как base64 (гигантская строка, ломает БД).
            // Вместо этого — показываем мини-диалог с двумя вариантами:
            // 1) Загрузить файл с компьютера (AJAX-аплоад на сервер)
            // 2) Вставить по URL-ссылке
            var toolbar = window.specQuill.getModule('toolbar');
            toolbar.addHandler('image', function() {
                // Удаляем предыдущий диалог если он был открыт
                $('#quill-image-dialog').remove();

                // Создаём HTML мини-диалога (оверлей + окно)
                var dialogHtml =
                    '<div id="quill-image-dialog" style="position:fixed;inset:0;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;">' +
                        '<div style="background:#fff;border-radius:16px;padding:24px;width:420px;max-width:90vw;box-shadow:0 20px 60px rgba(0,0,0,0.3);">' +
                            // Заголовок диалога
                            '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">' +
                                '<span style="font-size:18px;font-weight:700;color:#1f2937;">Вставить изображение</span>' +
                                '<button id="quill-img-close" style="background:none;border:none;font-size:22px;cursor:pointer;color:#9ca3af;line-height:1;">&times;</button>' +
                            '</div>' +
                            // Вкладка 1: Загрузка файла
                            '<div style="margin-bottom:14px;">' +
                                '<label style="display:block;font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">Загрузить с компьютера</label>' +
                                '<label id="quill-img-upload-label" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;border:2px dashed #d1d5db;border-radius:12px;cursor:pointer;color:#6b7280;font-size:14px;transition:border-color 0.2s,background 0.2s;">' +
                                    '<span style="font-size:20px;">📁</span> Выбрать файл...' +
                                    '<input type="file" id="quill-img-file" accept="image/*" style="display:none;">' +
                                '</label>' +
                                // Прогресс-бар (скрыт по умолчанию)
                                '<div id="quill-img-progress" style="display:none;margin-top:8px;">' +
                                    '<div style="background:#e5e7eb;border-radius:8px;height:6px;overflow:hidden;">' +
                                        '<div id="quill-img-bar" style="background:#3b82f6;height:100%;width:0%;transition:width 0.3s;border-radius:8px;"></div>' +
                                    '</div>' +
                                    '<span id="quill-img-status" style="font-size:12px;color:#9ca3af;margin-top:4px;display:block;">Загрузка...</span>' +
                                '</div>' +
                            '</div>' +
                            // Разделитель
                            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">' +
                                '<div style="flex:1;height:1px;background:#e5e7eb;"></div>' +
                                '<span style="font-size:12px;color:#9ca3af;font-weight:600;">ИЛИ</span>' +
                                '<div style="flex:1;height:1px;background:#e5e7eb;"></div>' +
                            '</div>' +
                            // Вкладка 2: Вставка по URL
                            '<div>' +
                                '<label style="display:block;font-size:13px;font-weight:600;color:#6b7280;margin-bottom:6px;">Вставить по ссылке</label>' +
                                '<div style="display:flex;gap:8px;">' +
                                    '<input type="text" id="quill-img-url" placeholder="https://example.com/image.png" style="flex:1;padding:10px 14px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;outline:none;">' +
                                    '<button id="quill-img-url-btn" style="padding:10px 18px;background:#3b82f6;color:#fff;border:none;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;white-space:nowrap;">Вставить</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>';

                // Добавляем диалог в DOM
                $('body').append(dialogHtml);

                // --- Обработчик: закрытие диалога ---
                $('#quill-img-close, #quill-image-dialog').on('click', function(e) {
                    // Закрываем только при клике на оверлей или крестик
                    if (e.target === this) {
                        $('#quill-image-dialog').remove();
                    }
                });

                // --- Обработчик: загрузка файла ---
                $('#quill-img-file').on('change', function() {
                    // Получаем выбранный файл
                    var file = this.files[0];
                    if (!file) return;

                    // Показываем прогресс-бар
                    $('#quill-img-progress').show();
                    $('#quill-img-status').text('Загрузка...');
                    $('#quill-img-bar').css('width', '0%');

                    // Формируем FormData для AJAX-запроса
                    var formData = new FormData();
                    formData.append('image', file);

                    // Отправляем файл на сервер через AJAX с отслеживанием прогресса
                    $.ajax({
                        url: window.globalApi.upload_editor_image,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        // Принудительно текст — не даём jQuery парсить автоматически
                        dataType: 'text',
                        xhr: function() {
                            // Создаём объект XHR с обработчиком прогресса
                            var xhr = new XMLHttpRequest();
                            xhr.upload.addEventListener('progress', function(e) {
                                if (e.lengthComputable) {
                                    // Обновляем ширину прогресс-бара
                                    var pct = Math.round((e.loaded / e.total) * 100);
                                    $('#quill-img-bar').css('width', pct + '%');
                                    $('#quill-img-status').text(pct + '%');
                                }
                            });
                            return xhr;
                        },
                        success: function(response) {
                            // Безопасно парсим ответ (оборачиваем в try/catch)
                            var res;
                            try {
                                res = CustomersUtils.safeParseJson(response);
                            } catch (e) {
                                // Если парсинг не удался — показываем сырой ответ (может быть PHP-ошибка)
                                $('#quill-img-status').text('Ошибка сервера');
                                $('#quill-img-bar').css({'width': '100%', 'background': '#ef4444'});
                                console.error('Ответ сервера:', response);
                                return;
                            }
                            if (res.status === 'success' && res.url) {
                                // Получаем текущую позицию курсора в редакторе
                                var range = window.specQuill.getSelection(true);
                                // Вставляем тег изображения с URL от сервера
                                window.specQuill.insertEmbed(range.index, 'image', res.url);
                                // Закрываем диалог после успешной вставки
                                $('#quill-image-dialog').remove();
                            } else {
                                // Показываем ошибку от сервера
                                $('#quill-img-status').text('Ошибка: ' + (res.message || 'неизвестная'));
                                $('#quill-img-bar').css({'width': '100%', 'background': '#ef4444'});
                            }
                        },
                        error: function(xhr) {
                            // Ошибка сети или HTTP-ошибка
                            $('#quill-img-status').text('Ошибка: ' + (xhr.status ? xhr.status + ' ' + xhr.statusText : 'сеть'));
                            $('#quill-img-bar').css({'width': '100%', 'background': '#ef4444'});
                            console.error('Ошибка загрузки:', xhr.responseText);
                        }
                    });
                });

                // --- Обработчик: вставка по URL ---
                $('#quill-img-url-btn').on('click', function() {
                    // Считываем введённый URL
                    var url = $('#quill-img-url').val().trim();
                    if (url) {
                        // Получаем текущую позицию курсора в редакторе
                        var range = window.specQuill.getSelection(true);
                        // Вставляем тег изображения с указанным URL
                        window.specQuill.insertEmbed(range.index, 'image', url);
                        // Закрываем диалог после вставки
                        $('#quill-image-dialog').remove();
                    }
                });

                // --- Обработчик: Enter в поле URL ---
                $('#quill-img-url').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        // Имитируем клик на кнопку «Вставить»
                        $('#quill-img-url-btn').click();
                    }
                });
            });
        }
    } else {
        // Quill недоступен — подставляем стандартный textarea как фоллбэк
        if ($('#spec-editor-container').length > 0) {
            // Заменяем контейнер на textarea для ввода текста
            $('#spec-editor-container').html(
                '<textarea id="spec-editor-fallback" ' +
                'class="w-full h-full p-4 border border-gray-200 rounded-xl text-sm focus:outline-none" ' +
                'placeholder="Детальное описание проекта, требования, объемы и задачи...">' +
                '</textarea>'
            );
        }
    }
};

/**
 * Обработчик сабмита формы ТЗ: переносит контент из Quill в скрытое поле.
 * Вызывается перед отправкой формы #specForm.
 * @param {Event} event — событие submit формы
 */
window.submitSpecForm = function(event) {
    // Переменная для HTML-контента
    var content = '';
    // Если Quill-редактор доступен — берём контент оттуда
    if (window.specQuill) {
        // Получаем HTML из корневого элемента редактора
        content = window.specQuill.root.innerHTML;
        // Если редактор пуст (только пробелы) — оставляем пустую строку
        if (window.specQuill.getText().trim() === '') {
            content = '';
        }
    } else {
        // Фоллбэк: берём контент из textarea
        content = $('#spec-editor-fallback').val() || '';
    }
    // Записываем контент в скрытое поле формы для отправки на сервер
    $('#specFormContent').val(content);
};

/**
 * Инициализирует обработчик сабмита формы ТЗ (делегированный).
 * Привязывается к форме #specForm.
 */
window.initSpecFormSubmit = function() {
    // Снимаем старый обработчик (защита от дублирования при реинициализации)
    $(document).off('submit', '#specForm');
    // Привязываем делегированный обработчик submit
    $(document).on('submit', '#specForm', function(event) {
        // Переносим контент Quill в скрытое поле перед отправкой
        window.submitSpecForm(event);
    });
};
