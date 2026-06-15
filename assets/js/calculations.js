// --- МОДУЛЬ КАЛЬКУЛЯЦИЙ И РАСЧЕТОВ (CALCULATIONS) ---

// Проверяем, был ли модуль уже загружен (защита от повторной инициализации при AJAX SPA переходах)
if (!window.loadedCalculationsModule) {
    // Устанавливаем флаг загрузки модуля
    window.loadedCalculationsModule = true;

    /**
     * Выполняет AJAX-поиск невыставленных задач за выбранный период дат
     */
    window.searchTasks = function() {
        var start = $('#searchDateStart').val();
        var end = $('#searchDateEnd').val();
        var container = $('#availableTasksContainer');
        container.html('<div class="text-center py-4 text-gray-400">Поиск...</div>');
        
        $.post(window.globalApi.search_tasks, { start_date: start, end_date: end }, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    // Обновляем HTML контейнера доступных задач
                    container.html(res.html);
                    // Навешиваем обработчик начала перетаскивания на новые элементы
                    container.find('.task-draggable').each(function() {
                        this.ondragstart = window.drag;
                    });
                }
            } catch (e) {
                console.error(e);
            }
        });
    };

    /**
     * Разрешает сброс (drop) перетаскиваемого элемента в зону
     * @param {DragEvent} ev - Событие dragover
     */
    window.allowDrop = function(ev) {
        ev.preventDefault();
    };

    /**
     * Обрабатывает старт перетаскивания задачи
     * @param {DragEvent} ev - Событие dragstart
     */
    window.drag = function(ev) {
        ev.dataTransfer.setData("taskId", ev.target.getAttribute('data-id'));
        ev.dataTransfer.setData("source", $(ev.target).parent().attr('id'));
    };

    /**
     * Обрабатывает сброс задачи в пакет калькуляции
     * @param {DragEvent} ev - Событие drop
     */
    window.dropToPackage = function(ev) {
        ev.preventDefault();
        var taskId = ev.dataTransfer.getData("taskId");
        var source = ev.dataTransfer.getData("source");
        // Добавляем в пакет, если источник не был самим пакетом
        if (source !== 'packageTasksContainer' && taskId) {
            addTaskToPackage(taskId);
        }
    };

    /**
     * Обрабатывает сброс задачи обратно в доступный список (исключение из пакета)
     * @param {DragEvent} ev - Событие drop
     */
    window.dropToAvailable = function(ev) {
        ev.preventDefault();
        var taskId = ev.dataTransfer.getData("taskId");
        var source = ev.dataTransfer.getData("source");
        // Удаляем из пакета, если источником был пакет
        if (source === 'packageTasksContainer' && taskId) {
            removeTaskFromPackage(taskId);
        }
    };

    /**
     * Привязывает задачу к пакету через AJAX
     * @param {number|string} taskId - Идентификатор задачи
     */
    function addTaskToPackage(taskId) {
        var packageId = $('#currentPackageId').val();
        $.post(window.globalApi.add_calculation_task, { package_id: packageId, task_id: taskId }, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    // Перезагружаем страницу для обновления финансовых показателей пакета
                    window.location.reload();
                } else {
                    alert('Ошибка при добавлении задачи в пакет');
                }
            } catch (e) {
                console.error(e);
            }
        });
    }

    /**
     * Отвязывает задачу от пакета через AJAX
     * @param {number|string} taskId - Идентификатор задачи
     */
    function removeTaskFromPackage(taskId) {
        var packageId = $('#currentPackageId').val();
        $.post(window.globalApi.remove_calculation_task, { package_id: packageId, task_id: taskId }, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert('Ошибка при удалении задачи из пакета');
                }
            } catch (e) {
                console.error(e);
            }
        });
    }
}
