// --- МОДУЛЬ УПРАВЛЕНИЯ ЗАКАЗЧИКАМИ И ТЗ (CUSTOMERS) ---

// Проверяем, был ли модуль уже загружен (защита от повторной инициализации при AJAX SPA переходах)
if (window.loadedCustomersModule) {
    // Переинициализируем Quill и бесконечный скролл для нового HTML
    initQuillEditors();
    initInfiniteScrollCustomers();
} else {
    // Устанавливаем флаг загрузки модуля
    window.loadedCustomersModule = true;

    // Глобальные объекты редакторов Quill
    window.addQuill = null;
    window.editQuill = null;

    /**
     * Инициализирует Quill WYSIWYG редакторы для красивого оформления ТЗ
     */
    window.initQuillEditors = function() {
        if (typeof Quill !== 'undefined') {
            var options = {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            };
            // Проверяем наличие контейнеров в DOM перед созданием редактора
            if ($('#add-editor').length > 0) {
                window.addQuill = new Quill('#add-editor', options);
            }
            if ($('#edit-editor').length > 0) {
                window.editQuill = new Quill('#edit-editor', options);
            }
        } else {
            // Если Quill недоступен (оффлайн режим), подставляем стандартные текстовые области
            if ($('#add-editor-container').length > 0) {
                $('#add-editor-container').html('<textarea id="add-editor-fallback" class="w-full h-full p-4 border border-gray-200 rounded-xl text-sm focus:outline-none" placeholder="Детальное описание проекта, требования, объемы и задачи..."></textarea>');
            }
            if ($('#edit-editor-container').length > 0) {
                $('#edit-editor-container').html('<textarea id="edit-editor-fallback" class="w-full h-full p-4 border border-gray-200 rounded-xl text-sm focus:outline-none"></textarea>');
            }
        }
    };

    /**
     * Открывает модальное окно добавления нового клиента
     */
    window.openAddCustomerModal = function() {
        $('#addCustomerModal').removeClass('hidden');
    };

    /**
     * Закрывает модальное окно добавления нового клиента
     */
    window.closeAddCustomerModal = function() {
        $('#addCustomerModal').addClass('hidden');
    };

    /**
     * Открывает модальное окно редактирования клиента с предзаполнением
     */
    window.openEditCustomerModal = function(id, name, notes, defaultPrice, defaultPrepayment, defaultPaymentType) {
        $('#editCustomerId').val(id);
        $('#editCustomerName').val(name);
        $('#editCustomerNotes').val(notes);
        $('#editCustomerDefaultPrice').val(defaultPrice || '0.00');
        $('#editCustomerDefaultPrepayment').val(defaultPrepayment || '0.00');
        $('#editCustomerDefaultPaymentType').val(defaultPaymentType || 'hourly');
        $('#editCustomerModal').removeClass('hidden');
    };

    /**
     * Закрывает модальное окно редактирования клиента
     */
    window.closeEditCustomerModal = function() {
        $('#editCustomerModal').addClass('hidden');
    };

    /**
     * Открывает модальное окно подробной информации клиента
     */
    window.openCustomerInfoModal = function() {
        $('#customerInfoModal').removeClass('hidden');
    };

    /**
     * Закрывает модальное окно подробной информации клиента
     */
    window.closeCustomerInfoModal = function() {
        $('#customerInfoModal').addClass('hidden');
    };

    /**
     * Открывает модальное окно добавления нового ТЗ для клиента
     */
    window.openAddSpecModal = function() {
        if (window.addQuill) {
            window.addQuill.setContents([]);
        } else {
            $('#add-editor-fallback').val('');
        }
        $('#addSpecModal').removeClass('hidden');
    };

    /**
     * Закрывает модальное окно добавления нового ТЗ
     */
    window.closeAddSpecModal = function() {
        $('#addSpecModal').addClass('hidden');
    };

    /**
     * Открывает модальное окно редактирования ТЗ клиента с предзаполнением
     */
    window.openEditSpecModal = function(id, title, content, price, prepayment, paymentType, linkedTaskIds, filesDir) {
        $('#editSpecId').val(id);
        $('#editSpecTitle').val(title);
        $('#editSpecPrice').val(price || '0.00');
        $('#editSpecPrepayment').val(prepayment || '0.00');
        $('#editSpecPaymentType').val(paymentType || 'hourly');
        $('#editSpecFilesDir').val(filesDir || '');

        // Снимаем отметки со всех чекбоксов привязки задач
        $('.edit-spec-task-checkbox').prop('checked', false);
        // Отмечаем привязанные
        if (linkedTaskIds && Array.isArray(linkedTaskIds)) {
            linkedTaskIds.forEach(function(taskId) {
                $('.edit-spec-task-checkbox[value="' + taskId + '"]').prop('checked', true);
            });
        }

        if (window.editQuill) {
            window.editQuill.clipboard.dangerouslyPasteHTML(content);
        } else {
            $('#edit-editor-fallback').val(content);
        }
        $('#editSpecModal').removeClass('hidden');
    };

    /**
     * Закрывает модальное окно редактирования ТЗ
     */
    window.closeEditSpecModal = function() {
        $('#editSpecModal').addClass('hidden');
    };

    /**
     * Переносит контент из WYSIWYG Quill в скрытое поле формы перед отправкой добавления ТЗ
     */
    window.submitAddSpecForm = function(e) {
        var content = '';
        if (window.addQuill) {
            content = window.addQuill.root.innerHTML;
            if (window.addQuill.getText().trim() === '') content = '';
        } else {
            content = $('#add-editor-fallback').val();
        }
        $('#addSpecContent').val(content);
    };

    /**
     * Переносит контент из WYSIWYG Quill в скрытое поле формы перед отправкой редактирования ТЗ
     */
    window.submitEditSpecForm = function(e) {
        var content = '';
        if (window.editQuill) {
            content = window.editQuill.root.innerHTML;
            if (window.editQuill.getText().trim() === '') content = '';
        } else {
            content = $('#edit-editor-fallback').val();
        }
        $('#editSpecContent').val(content);
    };

    /**
     * Обрабатывает перетаскивание файлов для загрузки в ТЗ
     */
    window.handleFileDrop = function(e, specId) {
        e.preventDefault();
        $(e.currentTarget).removeClass('border-blue-400');
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            window.uploadFiles(files, specId);
        }
    };

    /**
     * Обрабатывает выбор файлов с диска для загрузки в ТЗ
     */
    window.handleFileSelect = function(e, specId) {
        var files = e.target.files;
        if (files.length > 0) {
            window.uploadFiles(files, specId);
        }
    };

    /**
     * Загружает файлы на сервер через AJAX и FormData
     * @param {FileList} files - Список загружаемых файлов
     * @param {number|string} specId - ID ТЗ
     */
    window.uploadFiles = function(files, specId) {
        var formData = new FormData();
        formData.append('spec_id', specId);
        // Загружаем первый файл в пачке (последовательная мультизагрузка)
        formData.append('file', files[0]);

        var progressContainer = $('#upload-progress-container-' + specId);
        var progressBar = $('#upload-progress-' + specId);
        
        progressContainer.removeClass('hidden');
        progressBar.css('width', '0%');

        $.ajax({
            url: window.globalApi.upload_file,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = (evt.loaded / evt.total) * 100;
                        progressBar.css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                progressContainer.addClass('hidden');
                try {
                    var res = JSON.parse(response);
                    if (res.status === 'success') {
                        var fileList = $('#file-list-' + specId);
                        fileList.find('.empty-files-label').remove();

                        var sizeKb = Math.round(files[0].size / 102.4) / 10;
                        // Рендерим новый прикрепленный файл
                        var fileHtml = `
                            <div id="file-item-${res.file_id}" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">
                                <span>${res.icon}</span>
                                <span class="text-gray-700 font-medium">${res.orig_name}</span>
                                <span class="text-gray-400 font-mono">(${sizeKb} KB)</span>
                                <a href="${res.download_url}" class="text-blue-500 hover:text-blue-700" title="Скачать файл">📥</a>
                                <button onclick="deleteSpecFile(${res.file_id})" class="text-red-400 hover:text-red-600" title="Удалить">✖</button>
                            </div>
                        `;
                        fileList.append(fileHtml);

                        // Если есть еще файлы в очереди, загружаем их рекурсивно
                        if (files.length > 1) {
                            var remainingFiles = Array.prototype.slice.call(files, 1);
                            window.uploadFiles(remainingFiles, specId);
                        }
                    } else {
                        alert('Ошибка при загрузке: ' + res.message);
                    }
                } catch (err) {
                    console.error(err);
                    alert('Произошла системная ошибка при загрузке файла');
                }
            },
            error: function() {
                progressContainer.addClass('hidden');
                alert('Ошибка отправки файла на сервер');
            }
        });
    };

    /**
     * Удаляет прикрепленный файл ТЗ через AJAX с подтверждением
     */
    window.deleteSpecFile = function(fileId) {
        if (!confirm('Вы уверены, что хотите удалить этот файл?')) {
            return;
        }

        $.post(window.globalApi.delete_spec_file + fileId, {}, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    var item = $('#file-item-' + fileId);
                    var parent = item.parent();
                    item.remove();
                    
                    if (parent.children().length === 0) {
                        parent.html('<span class="text-xs text-gray-400 italic empty-files-label">Нет прикрепленных файлов</span>');
                    }
                } else {
                    alert(res.message);
                }
            } catch(e) {
                console.error(e);
                alert('Произошла ошибка при удалении файла');
            }
        });
    };

    /**
     * Прикрепляет внешнюю ссылку к ТЗ
     */
    window.attachLink = function(specId) {
        var urlInput = $('#url-input-' + specId);
        var titleInput = $('#url-title-' + specId);
        var url = urlInput.val().trim();
        var title = titleInput.val().trim();

        if (url === '') {
            alert('Пожалуйста, введите URL-адрес ссылки.');
            return;
        }

        $.post(window.globalApi.add_link, { spec_id: specId, url: url, title: title }, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    var fileList = $('#file-list-' + specId);
                    fileList.find('.empty-files-label').remove();

                    var itemHtml = `
                        <div id="file-item-${res.file_id}" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">
                            <span>${res.icon}</span>
                            <a href="${res.url}" target="_blank" class="text-blue-600 hover:underline font-medium">${res.orig_name}</a>
                            <span class="text-gray-400 font-mono">(Ссылка)</span>
                            <button onclick="deleteSpecFile(${res.file_id})" class="text-red-400 hover:text-red-600" title="Удалить">✖</button>
                        </div>
                    `;
                    fileList.append(itemHtml);
                    urlInput.val('');
                    titleInput.val('');
                } else {
                    alert(res.message);
                }
            } catch(e) {
                console.error(e);
                alert('Не удалось добавить ссылку');
            }
        });
    };

    /**
     * Скачивает файл по внешней ссылке и сохраняет его на сервере
     */
    window.downloadFromUrl = function(specId) {
        var urlInput = $('#url-input-' + specId);
        var url = urlInput.val().trim();

        if (url === '') {
            alert('Пожалуйста, введите URL-адрес для скачивания.');
            return;
        }

        var progressContainer = $('#upload-progress-container-' + specId);
        var progressBar = $('#upload-progress-' + specId);
        
        progressContainer.removeClass('hidden');
        progressBar.css('width', '100%');

        $.post(window.globalApi.download_url, { spec_id: specId, url: url }, function(response) {
            progressContainer.addClass('hidden');
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    var fileList = $('#file-list-' + specId);
                    fileList.find('.empty-files-label').remove();

                    var sizeKb = Math.round(res.file_size / 102.4) / 10;
                    var itemHtml = `
                        <div id="file-item-${res.file_id}" class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 text-xs shadow-sm">
                            <span>${res.icon}</span>
                            <span class="text-gray-700 font-medium">${res.orig_name}</span>
                            <span class="text-gray-400 font-mono">(${sizeKb} KB)</span>
                            <a href="${res.download_url}" class="text-blue-500 hover:text-blue-700" title="Скачать">📥</a>
                            <button onclick="deleteSpecFile(${res.file_id})" class="text-red-400 hover:text-red-600" title="Удалить">✖</button>
                        </div>
                    `;
                    fileList.append(itemHtml);
                    urlInput.val('');
                    $('#url-title-' + specId).val('');
                } else {
                    alert(res.message);
                }
            } catch(e) {
                console.error(e);
                alert('Не удалось скачать файл по ссылке.');
            }
        });
    };

    // Запускаем инициализацию Quill и скролла
    initQuillEditors();
    initInfiniteScrollCustomers();
    initInfiniteScrollTasks();
    initClosedTasksToggle();
}

/**
 * Настраивает бесконечный скролл списка заказчиков
 */
function initInfiniteScrollCustomers() {
    let custOffset = window.globalPerPage || 25;
    let custLimit = window.globalPerPage || 25;
    let custHasMore = true;
    let custIsLoading = false;

    $('#customersSidebarList').off('scroll').on('scroll', function() {
        if ($('#customersSidebarList').length === 0) return;
        if (!custHasMore || custIsLoading) return;

        let scrollTop = $(this).scrollTop();
        let scrollHeight = $(this)[0].scrollHeight;
        let innerHeight = $(this).innerHeight();

        if (scrollTop + innerHeight >= scrollHeight - 50) {
            custIsLoading = true;
            let activeCustomerId = window.activeCustomerId || null;

            $.post(window.globalApi.load_more_customers, { offset: custOffset, active_customer_id: activeCustomerId }, function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    if (res.html && res.html.trim() !== '') {
                        $('#customersSidebarList').append(res.html);
                        custOffset += custLimit;
                    }
                    custHasMore = res.has_more;
                }
                custIsLoading = false;
            }).fail(function() {
                custIsLoading = false;
            });
        }
    });
}

/**
 * Настраивает бесконечный скролл списка задач (проектов) заказчика
 */
function initInfiniteScrollTasks() {
    let taskOffset = window.globalPerPage || 25;
    let taskLimit = window.globalPerPage || 25;
    let taskHasMore = $('#customerTasksContainer').data('has-more') == '1';
    let taskIsLoading = false;

    // Сбрасываем offset и статус загрузки при каждом вызове
    window.resetTasksScroll = function(hasMore) {
        taskOffset = window.globalPerPage || 25;
        taskHasMore = hasMore;
        taskIsLoading = false;
    };

    $('#customerTasksContainer').off('scroll').on('scroll', function() {
        if ($('#customerTasksContainer').length === 0) return;
        if (!taskHasMore || taskIsLoading) return;

        let scrollTop = $(this).scrollTop();
        let scrollHeight = $(this)[0].scrollHeight;
        let innerHeight = $(this).innerHeight();

        if (scrollTop + innerHeight >= scrollHeight - 30) {
            taskIsLoading = true;
            let activeCustomerId = window.activeCustomerId || null;
            let showClosed = $('#showClosedTasksToggle').is(':checked') ? 1 : 0;

            $.post(window.globalApi.load_customer_tasks, { 
                customer_id: activeCustomerId, 
                offset: taskOffset,
                show_closed: showClosed
            }, function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    if (res.html && res.html.trim() !== '') {
                        // Если в контейнере была заглушка "Нет задач", удалим её
                        $('#customerTasksContainer').find('.empty-tasks-label').remove();

                        // Подгружаем HTML внутрь списка
                        let ul = $('#customerTasksContainer').find('> ul.task-tree-root');
                        if (ul.length > 0) {
                            let newItems = $(res.html).find('> li');
                            ul.append(newItems);
                        } else {
                            $('#customerTasksContainer').html(res.html);
                        }
                        taskOffset += taskLimit;
                    }
                    taskHasMore = res.has_more;
                }
                taskIsLoading = false;
            }).fail(function() {
                taskIsLoading = false;
            });
        }
    });
}

/**
 * Инициализирует переключатель отображения закрытых/актуальных задач и сворачивание
 */
function initClosedTasksToggle() {
    $('#showClosedTasksToggle').off('change').on('change', function() {
        let activeCustomerId = window.activeCustomerId || null;
        if (!activeCustomerId) return;

        let showClosed = $(this).is(':checked') ? 1 : 0;
        let container = $('#customerTasksContainer');

        container.html('<div class="py-4 text-center text-gray-400 italic">Загрузка...</div>');

        $.post(window.globalApi.load_customer_tasks, {
            customer_id: activeCustomerId,
            offset: 0,
            show_closed: showClosed
        }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                if (res.html && res.html.trim() !== '') {
                    container.html(res.html);
                } else {
                    container.html('<p class="text-sm text-gray-400 italic empty-tasks-label">Нет задач</p>');
                }
                // Сбрасываем пагинацию бесконечного скролла
                if (typeof window.resetTasksScroll === 'function') {
                    window.resetTasksScroll(res.has_more);
                }
            } else {
                container.html('<p class="text-sm text-red-500 italic">Ошибка загрузки задач</p>');
            }
        }).fail(function() {
            container.html('<p class="text-sm text-red-500 italic">Ошибка отправки запроса</p>');
        });
    });

    // Обработчик сворачивания/разворачивания подзадач в дереве
    $(document).off('click', '.toggle-children').on('click', '.toggle-children', function() {
        var li = $(this).closest('li');
        var icon = $(this).find('.icon-expand');
        var childrenList = li.find('> ul.task-children');
        
        childrenList.slideToggle(150);
        
        if (icon.hasClass('rotate-90')) {
            icon.removeClass('rotate-90');
        } else {
            icon.addClass('rotate-90');
        }
    });
}
