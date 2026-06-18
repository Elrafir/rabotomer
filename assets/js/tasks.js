// --- МОДУЛЬ ДАШБОРДА И УПРАВЛЕНИЯ ЗАДАЧАМИ (TASKS) ---

// Проверяем, был ли модуль уже загружен (защита от повторной инициализации при AJAX SPA переходах)
if (window.loadedTasksModule) {
    // Если модуль уже загружен, просто выходим
    // Но при этом инициализируем состояние раскрытых веток для свежезагруженного HTML
    initExpandedTasksTree();
    initTaskQuillEditor();
} else {
    // Устанавливаем флаг загрузки модуля
    window.loadedTasksModule = true;

    // Ссылка на глобальный API
    window.api = window.globalApi;

    window.editTaskEditor = null;

    /**
     * Инициализирует Quill редактор для подробного описания задачи
     */
    window.initTaskQuillEditor = function() {
        if (typeof Quill !== 'undefined') {
            var options = {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            };
            if ($('#editTaskDescriptionEditor').length > 0) {
                // Если редактор уже был инициализирован ранее, очистим контейнер во избежание дублирования тулбаров
                if (window.editTaskEditor) {
                    $('#editTaskDescriptionContainer').html('<div id="editTaskDescriptionEditor" class="flex-grow bg-white"></div>');
                }
                window.editTaskEditor = new Quill('#editTaskDescriptionEditor', options);
            }
        } else {
            // Фолбэк на обычную текстовую область
            if ($('#editTaskDescriptionContainer').length > 0) {
                $('#editTaskDescriptionContainer').html('<textarea id="editTaskDescriptionFallback" class="w-full h-full p-4 border border-gray-200 bg-gray-50 rounded-xl text-sm focus:outline-none resize-none" placeholder="Детальное описание задачи / требований..."></textarea>');
            }
        }
    };

    /**
     * Выбирает пресет цвета в модальном окне редактирования задачи
     */
    window.selectPresetColorInModal = function(color) {
        $('#editTaskColor').val(color);
        $('.modal-color-preset-btn').removeClass('ring-4 ring-blue-500 ring-offset-2');
        if (color) {
            $('.modal-color-preset-btn[data-color="' + color + '"]').addClass('ring-4 ring-blue-500 ring-offset-2');
        } else {
            $('.modal-color-preset-btn[data-color=""]').addClass('ring-4 ring-blue-500 ring-offset-2');
        }
    };

    /**
     * Обновляет тариф/ставку при выборе клиента в модальном окне редактирования задачи
     * @param {HTMLSelectElement} selectElem - Селектор выбора клиента
     */
    window.updateRate = function(selectElem) {
        var selectedOption = $(selectElem).find('option:selected');
        // Извлекаем значение ставки из дата-атрибута option
        var rate = selectedOption.data('rate') || '0.00';
        $('#editTaskPrice').val(rate);
    };

    /**
     * Показывает или скрывает поля ввода нового клиента при создании подзадачи
     * @param {HTMLSelectElement} selectElem - Селектор выбора клиента
     */
    window.updateRateSubtask = function(selectElem) {
        var val = $(selectElem).val();
        var container = $(selectElem).closest('form');
        if (container.length === 0) {
            container = $(selectElem).parent().parent();
        }
        
        if (val === 'new') {
            container.find('.new-customer-fields').removeClass('hidden');
        } else {
            container.find('.new-customer-fields').addClass('hidden');
        }
    };

    /**
     * Переключает (сворачивает/разворачивает) форму быстрого добавления подзадачи
     * @param {number|string} taskId - Идентификатор родительской задачи
     */
    window.toggleAddForm = function(taskId) {
        $('#add-form-' + taskId).slideToggle();
    };

    /**
     * Завершает задачу/проект с подтверждением
     * @param {number|string} taskId - Идентификатор задачи
     */
    window.completeTask = function(taskId) {
        if (!confirm(window.globalLang.js_confirm_complete)) {
            return;
        }
        
        // Очищаем локальное состояние паузы таймера
        let pausedInfo = localStorage.getItem('pausedTimerInfo');
        if (pausedInfo) {
            try {
                let parsed = JSON.parse(pausedInfo);
                if (parsed.task_id == taskId) {
                    localStorage.removeItem('pausedTimerInfo');
                }
            } catch(e) {}
        }

        $.post(window.api.complete, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    };

    /**
     * Восстанавливает завершенную задачу/проект из архива с подтверждением
     * @param {number|string} taskId - Идентификатор задачи
     */
    window.restoreTask = function(taskId) {
        if (!confirm(window.globalLang.js_confirm_restore)) {
            return;
        }

        $.post(window.api.restore, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    };

    /**
     * Открывает модальное окно корректировки времени сессий (ручной ввод времени)
     * @param {number|string} taskId - Идентификатор задачи
     * @param {string} title - Название задачи
     * @param {string} hexColor - Цвет задачи
     */
    window.openEditModal = function(taskId, title, hexColor = '#ffffff') {
        $('#modalTaskId').val(taskId);
        $('#modalTaskTitle').text(title);
        if (hexColor === '') hexColor = '#ffffff';
        // Назначаем градиентный фон с легким оттенком задачи (прозрачность 20%)
        $('#editModalBody').css('background', 'linear-gradient(135deg, #ffffff 30%, ' + hexColor + '33 100%)');
        $('#modalStartTime').val('');
        $('#modalEndTime').val('');
        $('#modalNote').val('');
        $('#modalSessionsList').html('<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">' + window.globalLang.js_confirm_complete + '...</td></tr>');
        $('#editTimeModal').removeClass('hidden');

        // Подгружаем список сессий этой задачи
        $.post(window.api.get_sessions, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                var html = '';
                if (res.data.length === 0) {
                    html = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Нет записей времени для этой задачи.</td></tr>';
                } else {
                    res.data.forEach(s => {
                        html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 border-b border-gray-100">${s.start_formatted}</td>
                            <td class="px-4 py-3 border-b border-gray-100">${s.end_formatted}</td>
                            <td class="px-4 py-3 border-b border-gray-100 font-mono">${s.duration}</td>
                            <td class="px-4 py-3 border-b border-gray-100 text-gray-500 text-xs">${s.note_safe}</td>
                            <td class="px-4 py-3 border-b border-gray-100 text-center">
                                <button data-id="${s.id}" class="delete-session-btn text-red-400 hover:text-red-600 transition-colors" title="Удалить сессию">
                                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>`;
                    });
                }
                $('#modalSessionsList').html(html);
            }
        });
    };

    /**
     * Закрывает модальное окно корректировки времени
     */
    window.closeEditModal = function() {
        $('#editTimeModal').addClass('hidden');
    };

    /**
     * Добавляет сессию времени вручную через AJAX
     */
    window.saveManualSession = function() {
        var taskId = $('#modalTaskId').val();
        var start = $('#modalStartTime').val();
        var end = $('#modalEndTime').val();
        var note = $('#modalNote').val();

        if (!start || !end) {
            alert('Пожалуйста, заполните дату начала и окончания.');
            return;
        }

        $.post(window.api.add_manual, { task_id: taskId, start_time: start, end_time: end, note: note }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                // Обновляем список сессий в окне
                window.openEditModal(taskId, $('#modalTaskTitle').text());
                // Перезагружаем страницу через секунду для обновления общего дерева времени
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert(res.message);
            }
        });
    };

    /**
     * Разворачивает/сворачивает историю последних сессий подзадачи прямо в дереве
     * @param {number|string} taskId - Идентификатор задачи
     * @param {HTMLButtonElement} btn - Нажатая кнопка стрелочки
     */
    window.toggleInlineHistory = function(taskId, btn) {
        var container = $('#inline-history-' + taskId);
        var svg = $(btn).find('.inline-arrow');
        
        // Если контейнер уже раскрыт — скрываем его и очищаем содержимое
        if (!container.hasClass('hidden') && container.html().trim() !== '') {
            container.slideUp(150, function() {
                container.addClass('hidden').html('');
            });
            svg.removeClass('rotate-180');
            return;
        }
        
        svg.addClass('rotate-180');
        container.html('<div class="py-2 px-4 text-gray-400 italic">Загрузка...</div>').removeClass('hidden').hide().slideDown(150);

        // Запрашиваем историю сессий (включая дочерние)
        $.post(window.api.get_cascading, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                if (res.data.length === 0) {
                    container.html('<div class="py-2 px-4 text-gray-500 font-medium">Нет сессий активности.</div>');
                    return;
                }
                
                var html = '<table class="w-full text-left border-collapse text-xs md:text-sm text-gray-600"><thead class="bg-gray-100 text-gray-500 border-b border-gray-200 font-semibold tracking-wide"><tr><th class="px-3 py-1">Начало - Окончание</th><th class="px-3 py-1">Задача</th><th class="px-3 py-1">Длительность</th><th class="px-3 py-1 w-1/3">Заметка</th></tr></thead><tbody class="divide-y divide-gray-100">';
                var showLimit = 10;
                var displayData = res.data.slice(0, showLimit);
                
                displayData.forEach(s => {
                    var colorStyle = s.color ? `background-color: ${s.color};` : 'background-color: #e5e7eb;';
                    html += `
                        <tr class="hover:bg-gray-100 transition-colors">
                            <td class="px-3 py-1.5 whitespace-nowrap"><span class="font-medium text-gray-700">${s.start_formatted}</span> <span class="text-gray-400">&rarr; ${s.end_formatted}</span></td>
                            <td class="px-3 py-1.5"><div class="flex items-center gap-2"><div class="w-2 h-2 rounded-full shadow-sm" style="${colorStyle}"></div><span class="font-semibold text-gray-800">${s.task_title}</span></div></td>
                            <td class="px-3 py-1.5 font-mono font-bold text-blue-600 bg-blue-50/50 rounded inline-block px-2">${s.duration}</td>
                            <td class="px-3 py-1.5 italic text-gray-500 bg-gray-50/50 rounded">${s.note_safe}</td>
                        </tr>
                    `;
                });
                html += '</tbody></table>';
                
                // Если сессий больше 10, добавляем ссылку на полную историю
                if (res.data.length > showLimit) {
                    var moreCount = res.data.length - showLimit;
                    var moreText = 'Показать еще ' + moreCount + '...';
                    html += `<div class="text-center py-2 text-purple-600 text-xs md:text-sm cursor-pointer hover:underline" onclick="openCascadeModal(${taskId}, '')">${moreText}</div>`;
                }
                
                container.html(html);
            } else {
                container.html('<div class="py-2 px-4 text-red-500">Ошибка при получении данных.</div>');
            }
        });
    };

    /**
     * Открывает модальное окно редактирования настроек задачи с подгрузкой данных по AJAX
     */
    window.openEditTaskModal = function(taskId) {
        // Сбрасываем поля формы перед загрузкой новых данных
        $('#editTaskId').val(taskId);
        $('#editTaskTitleInput').val('Загрузка...');
        $('#editTaskCustomer').val('');
        $('#editTaskIsFixed').val('0');
        $('#editTaskPrice').val('');
        $('#editTaskSpec').val('');
        $('#editTaskSpecContainer').addClass('hidden');
        window.selectPresetColorInModal('');

        if (window.editTaskEditor) {
            window.editTaskEditor.root.innerHTML = '';
        } else if ($('#editTaskDescriptionFallback').length > 0) {
            $('#editTaskDescriptionFallback').val('');
        }

        $('#editTaskModal').removeClass('hidden');

        // Отправляем AJAX запрос за деталями задачи
        $.post(window.location.origin + '/index.php/tasks/get_task_ajax', { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                var task = res.data;
                $('#editTaskTitleInput').val(task.title);
                $('#editTaskCustomer').val(task.customer_id || '');
                $('#editTaskIsFixed').val(task.is_fixed_price || '0');
                $('#editTaskPrice').val(task.price || '');
                
                // Подгружаем список ТЗ клиента и делаем автовыбор
                if (task.customer_id) {
                    $('#editTaskSpec').data('pending-select', task.spec_id);
                    $('#editTaskCustomer').trigger('change');
                }

                // Задаем цвет в палитре
                window.selectPresetColorInModal(task.color || '');

                // Задаем описание
                if (window.editTaskEditor) {
                    window.editTaskEditor.root.innerHTML = task.description || '';
                } else if ($('#editTaskDescriptionFallback').length > 0) {
                    $('#editTaskDescriptionFallback').val(task.description || '');
                }
            } else {
                alert(res.message);
            }
        });
    };

    /**
     * Закрывает модальное окно редактирования задачи
     */
    window.closeEditTaskModal = function() {
        $('#editTaskModal').addClass('hidden');
    };

    /**
     * Сохраняет отредактированные параметры задачи через AJAX
     */
    window.saveTaskTitle = function() {
        var taskId = $('#editTaskId').val();
        var title = $('#editTaskTitleInput').val();
        var customerId = $('#editTaskCustomer').val();
        var isFixedPrice = $('#editTaskIsFixed').val();
        var price = $('#editTaskPrice').val();
        var specId = $('#editTaskSpec').val() || null;
        var color = $('#editTaskColor').val() || '';

        var description = '';
        if (window.editTaskEditor) {
            description = window.editTaskEditor.root.innerHTML;
            if (window.editTaskEditor.getText().trim() === '') {
                description = '';
            }
        } else if ($('#editTaskDescriptionFallback').length > 0) {
            description = $('#editTaskDescriptionFallback').val();
        }

        if (!title) {
            alert('Пожалуйста, введите название задачи.');
            return;
        }

        $.post(window.api.edit_title, { 
            task_id: taskId, 
            title: title, 
            customer_id: customerId, 
            is_fixed_price: isFixedPrice, 
            price: price,
            spec_id: specId,
            color: color,
            description: description
        }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                window.closeEditTaskModal();
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    };

    /**
     * Открывает модальное окно каскадной (полной рекурсивной) истории проекта
     */
    window.openCascadeModal = function(taskId, title, hexColor = '#ffffff') {
        $('#cascadeModalTaskTitle').text(title);
        if (hexColor === '') hexColor = '#ffffff';
        // Назначаем легкий оттенок проекта на задний фон модалки
        $('#cascadeModalBody').css('background', 'linear-gradient(135deg, #ffffff 30%, ' + hexColor + '33 100%)');
        $('#cascadeModalSessionsList').html('<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Загрузка...</td></tr>');
        $('#cascadeHistoryModal').removeClass('hidden');

        $.post(window.api.get_cascading, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                var html = '';
                if (res.data.length === 0) {
                    html = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 font-medium">Нет сессий активности.</td></tr>';
                } else {
                    res.data.forEach(s => {
                        var colorStyle = s.color ? `background-color: ${s.color};` : 'background-color: #e5e7eb;';
                        html += `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-2 border-b border-gray-100 whitespace-nowrap"><span class="font-medium text-gray-700">${s.start_formatted}</span> <span class="text-gray-400 text-xs">&rarr; ${s.end_formatted}</span></td>
                            <td class="px-4 py-2 border-b border-gray-100"><div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full shadow-sm" style="${colorStyle}"></div><span class="font-bold text-gray-800 text-sm">${s.task_title}</span></div></td>
                            <td class="px-4 py-2 border-b border-gray-100 font-mono text-sm font-bold text-blue-600"><span class="bg-blue-50 px-2 py-0.5 rounded border border-blue-100">${s.duration}</span></td>
                            <td class="px-4 py-2 border-b border-gray-100 text-gray-600 text-sm italic bg-gray-50">${s.note_safe}</td>
                        </tr>`;
                    });
                }
                $('#cascadeModalSessionsList').html(html);
            }
        });
    };

    /**
     * Закрывает модальное окно каскадной истории
     */
    window.closeCascadeModal = function() {
        $('#cascadeHistoryModal').addClass('hidden');
    };

    /**
     * Переименовывает задачу через быструю форму prompt
     */
    window.editTaskTitle = function(taskId, currentTitle) {
        var newTitle = prompt("Редактировать:", currentTitle);
        
        if (newTitle !== null && newTitle.trim() !== "" && newTitle.trim() !== currentTitle) {
            $.post(window.api.edit_title, { task_id: taskId, title: newTitle.trim() }, function(response) {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    };

    /**
     * Удаляет задачу и все ее подзадачи (каскадно) с подтверждением
     */
    window.deleteTaskCascade = function(taskId) {
        if (confirm(window.globalLang.js_confirm_delete_task)) {
            $.post(window.api.delete_task, { task_id: taskId }, function(response) {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    };

    // --- Color Picker Logic (Выбор цвета задачи) ---
    var activeColorTaskId = null;

    /**
     * Открывает всплывающее окно выбора цвета рядом с цветной точкой задачи
     */
    window.openColorPicker = function(e, taskId) {
        e.stopPropagation();
        activeColorTaskId = taskId;
        
        var dot = $(e.target);
        var offset = dot.offset();
        
        // Позиционируем поповер цвета
        $('#colorPickerPopover').css({
            top: offset.top + 20 + 'px',
            left: offset.left + 'px'
        }).removeClass('hidden');
    };

    /**
     * Сохраняет выбранный цвет задачи в БД
     */
    window.saveColor = function(hexColor) {
        if (!activeColorTaskId) return;

        $.post(window.api.set_color, { task_id: activeColorTaskId, color: hexColor }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    };

    // Делегированные обработчики событий (привязываются один раз)
    
    // Раскрытие/сворачивание подзадач в дереве
    $(document).on('click', '.toggle-children', function() {
        var li = $(this).closest('li');
        var taskId = li.data('task-id');
        var icon = $(this).find('.icon-expand');
        var childrenList = li.find('> ul.task-children');
        
        childrenList.slideToggle(200, function() {
            // Запоминаем состояние раскрытия в localStorage
            var expandedTasks = JSON.parse(localStorage.getItem('expandedTasks') || '{}');
            expandedTasks[taskId] = childrenList.is(':visible');
            localStorage.setItem('expandedTasks', JSON.stringify(expandedTasks));
        });
        
        if (icon.hasClass('rotate-180')) {
            icon.removeClass('rotate-180');
        } else {
            icon.addClass('rotate-180');
        }
    });

    // Живой поиск по дереву задач на дашборде
    $(document).on('keyup', '#searchTaskInput', function() {
        var value = $(this).val().toLowerCase().trim();
        
        if (value === "") {
            // Если поиск пустой, возвращаем изначальное дерево
            $('.task-tree-root li').show();
            initExpandedTasksTree();
        } else {
            // Показываем совпадения и их родителей, скрывая остальные
            $('.task-tree-root li').each(function() {
                var taskName = $(this).find('.task-title-text').first().text().toLowerCase();
                
                if (taskName.indexOf(value) > -1) {
                    $(this).show();
                    $(this).parents('li').show();
                    $(this).parents('ul.task-children').show();
                } else {
                    $(this).hide();
                }
            });
        }
    });

    // Живой поиск внутри каскадной истории сессий
    $(document).on('keyup', '#cascadeSearchInput', function() {
        var value = $(this).val().toLowerCase();
        $('#cascadeModalSessionsList tr').filter(function() {
            if ($(this).find('td').length > 1) {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            }
        });
    });

    // Удаление сессии в ручном редакторе
    $(document).on('click', '.delete-session-btn', function() {
        var sessionId = $(this).data('id');
        
        if (confirm(window.globalLang.js_confirm_delete)) {
            $.post(window.api.delete_session, { session_id: sessionId }, function(response) {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    });

    // Скрытие поповера выбора цвета при клике вне его
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#colorPickerPopover').length && !$(e.target).closest('.w-4.h-4.rounded-full').length) {
            $('#colorPickerPopover').addClass('hidden');
        }
    });

    // --- Динамический тултип для отображения описания задач при наведении и клике ---
    let tooltipShowTimeout;
    let tooltipHideTimeout;
    let currentTooltipTaskId = null;
    
    // Создаем контейнер тултипа в body, если его нет
    if ($('#task-description-tooltip').length === 0) {
        $('body').append(`
            <div id="task-description-tooltip" class="hidden fixed p-4 rounded-2xl shadow-2xl z-[99999] pointer-events-none transition-all duration-300 transform scale-95 opacity-0 max-w-sm text-sm text-gray-700" style="transition-property: opacity, transform;">
                <button type="button" class="tooltip-close">&times;</button>
                <div class="tooltip-arrow absolute w-3 h-3 rotate-45"></div>
                <div class="tooltip-content max-h-60 overflow-y-auto pr-6"></div>
            </div>
        `);
    }

    const tooltip = $('#task-description-tooltip');
    const content = tooltip.find('.tooltip-content');
    const arrow = tooltip.find('.tooltip-arrow');

    function showTooltip(target, isPinned) {
        var titleElement = target.hasClass('task-title-text') ? target : target.siblings('.task-title-text');
        var descHtml = titleElement.data('description');
        if (!descHtml) return;

        var parentLi = target.closest('li[data-task-id]');
        var taskId = parentLi.data('task-id');
        
        currentTooltipTaskId = taskId;

        // Наполняем контент
        content.html(descHtml);
        
        if (isPinned) {
            tooltip.addClass('pinned');
            tooltip.data('pinned-task-id', taskId);
        } else {
            tooltip.removeClass('pinned');
            tooltip.removeData('pinned-task-id');
        }

        // Показываем тултип для расчета размеров
        tooltip.removeClass('hidden').css({
            visibility: 'hidden',
            display: 'block'
        });

        // Расчет геометрии
        var targetOffset = target.offset();
        var targetWidth = target.outerWidth();
        var targetHeight = target.outerHeight();
        
        var tooltipWidth = tooltip.outerWidth();
        var tooltipHeight = tooltip.outerHeight();
        
        // Позиция по умолчанию: сверху по центру
        var top = targetOffset.top - tooltipHeight - 12;
        var left = targetOffset.left + (targetWidth / 2) - (tooltipWidth / 2);
        
        var scrollTop = $(window).scrollTop();
        if (top < scrollTop + 10) {
            // Если не помещается сверху, выводим снизу
            top = targetOffset.top + targetHeight + 12;
            arrow.removeClass('bottom-[-6px] border-r border-b').addClass('top-[-6px] border-l border-t').css({
                left: 'calc(50% - 6px)',
                top: '-6px',
                bottom: 'auto'
            });
        } else {
            // Выводим сверху
            arrow.removeClass('top-[-6px] border-l border-t').addClass('bottom-[-6px] border-r border-b').css({
                left: 'calc(50% - 6px)',
                bottom: '-6px',
                top: 'auto'
            });
        }
        
        // Защита от вылета за границы экрана по горизонтали
        var scrollLeft = $(window).scrollLeft();
        var windowWidth = $(window).width();
        if (left < scrollLeft + 10) {
            left = scrollLeft + 10;
            var arrowLeft = (targetOffset.left + targetWidth / 2) - left - 6;
            arrow.css('left', arrowLeft + 'px');
        } else if (left + tooltipWidth > scrollLeft + windowWidth - 10) {
            left = scrollLeft + windowWidth - tooltipWidth - 10;
            var arrowLeft = (targetOffset.left + targetWidth / 2) - left - 6;
            arrow.css('left', arrowLeft + 'px');
        } else {
            arrow.css('left', 'calc(50% - 6px)');
        }

        tooltip.css({
            visibility: 'visible',
            top: top + 'px',
            left: left + 'px'
        }).removeClass('opacity-0 scale-95').addClass('opacity-100 scale-100');
    }

    function hideTooltip(force) {
        clearTimeout(tooltipShowTimeout);
        clearTimeout(tooltipHideTimeout);
        
        if (tooltip.hasClass('pinned') && !force) {
            return;
        }

        tooltip.removeClass('opacity-100 scale-100').addClass('opacity-0 scale-95');
        tooltipHideTimeout = setTimeout(function() {
            tooltip.addClass('hidden').removeClass('pinned');
            tooltip.removeData('pinned-task-id');
            currentTooltipTaskId = null;
        }, 300);
    }

    $(document).on('mouseenter', '.task-title-text.has-description, .task-desc-indicator', function(e) {
        if (tooltip.hasClass('pinned')) {
            return;
        }
        clearTimeout(tooltipHideTimeout);
        var target = $(this);
        var parentLi = target.closest('li[data-task-id]');
        var taskId = parentLi.data('task-id');

        if (currentTooltipTaskId === taskId && !tooltip.hasClass('hidden')) {
            return;
        }

        clearTimeout(tooltipShowTimeout);
        tooltipShowTimeout = setTimeout(function() {
            showTooltip(target, false);
        }, 400); // 400мс задержка для предотвращения случайных срабатываний
    });

    $(document).on('mouseleave', '.task-title-text.has-description, .task-desc-indicator', function() {
        if (tooltip.hasClass('pinned')) {
            return;
        }
        clearTimeout(tooltipShowTimeout);
        tooltipHideTimeout = setTimeout(function() {
            hideTooltip(false);
        }, 200);
    });

    $(document).on('click', '.task-desc-indicator', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var target = $(this);
        var parentLi = target.closest('li[data-task-id]');
        var taskId = parentLi.data('task-id');

        if (tooltip.hasClass('pinned') && tooltip.data('pinned-task-id') === taskId) {
            hideTooltip(true);
        } else {
            clearTimeout(tooltipShowTimeout);
            clearTimeout(tooltipHideTimeout);
            showTooltip(target, true);
        }
    });

    $(document).on('click', '#task-description-tooltip .tooltip-close', function(e) {
        e.preventDefault();
        e.stopPropagation();
        hideTooltip(true);
    });

    $(document).on('click', function(e) {
        if (tooltip.hasClass('pinned')) {
            if (!$(e.target).closest('#task-description-tooltip').length && !$(e.target).closest('.task-desc-indicator').length) {
                hideTooltip(true);
            }
        }
    });

    // Инициализация бесконечного скролла задач на дашборде
    initInfiniteScrollTasks();
    initExpandedTasksTree();
    initTaskQuillEditor();
}

/**
 * Инициализирует свернутое/развернутое состояние дерева задач из localStorage
 */
function initExpandedTasksTree() {
    var expandedTasks = JSON.parse(localStorage.getItem('expandedTasks') || '{}');
    $('li[data-task-id]').each(function() {
        var taskId = $(this).data('task-id');
        if (expandedTasks[taskId] === true) {
            var childrenList = $(this).find('> ul.task-children');
            if (childrenList.length > 0) {
                childrenList.show();
                $(this).find('> div .toggle-children .icon-expand').addClass('rotate-180');
            }
        }
    });
}

/**
 * Настройка бесконечного скролла для подгрузки корневых проектов по AJAX
 */
function initInfiniteScrollTasks() {
    let taskOffset = window.globalPerPage || 25;
    let taskLimit = window.globalPerPage || 25;
    let taskHasMore = true;
    let taskIsLoading = false;

    $(window).on('scroll', function() {
        // Проверяем, что мы находимся именно на странице дашборда (контейнер дерева задач существует)
        if ($('.task-tree-root').length === 0) return;
        if (!taskHasMore || taskIsLoading) return;

        // Если до конца страницы осталось меньше 200px
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
            taskIsLoading = true;
            
            $.post(window.api.load_more_tasks, { offset: taskOffset }, function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    if (res.html && res.html.trim() !== '') {
                        $('.task-tree-root').append(res.html);
                        taskOffset += taskLimit;
                        
                        // Применяем сохраненное состояние раскрытия к новым подгруженным веткам
                        initExpandedTasksTree();
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
