// --- МОДУЛЬ ЖУРНАЛА АКТИВНОСТИ И ИСТОРИИ СЕССИЙ (HISTORY) ---

// Проверяем, был ли модуль уже загружен (защита от повторной инициализации при AJAX SPA переходах)
if (!window.loadedHistoryModule) {
    // Устанавливаем флаг загрузки модуля
    window.loadedHistoryModule = true;

    /**
     * Открывает модальное окно добавления ручной сессии (только для админов)
     */
    window.openAddSessionModal = function() {
        $('#add_task_id').val('');
        $('#add_start_time').val('');
        $('#add_end_time').val('');
        $('#add_note').val('');
        $('#addSessionModal').removeClass('hidden');
    };

    /**
     * Закрывает модальное окно добавления ручной сессии
     */
    window.closeAddSessionModal = function() {
        $('#addSessionModal').addClass('hidden');
    };

    /**
     * Открывает модальное окно редактирования выбранной сессии с предзаполнением
     * @param {number|string} sessionId - ID сессии
     * @param {number|string} taskId - ID привязанной задачи
     * @param {string} startTime - Дата/время начала сессии
     * @param {string} endTime - Дата/время окончания сессии
     * @param {string} note - Заметка о проделанной работе
     */
    window.openEditSessionModal = function(sessionId, taskId, startTime, endTime, note) {
        $('#edit_session_id').val(sessionId);
        $('#edit_task_id').val(taskId);
        $('#edit_start_time').val(startTime);
        $('#edit_end_time').val(endTime);
        $('#edit_note').val(note);
        $('#editSessionModal').removeClass('hidden');
    };

    /**
     * Закрывает модальное окно редактирования сессии
     */
    window.closeEditSessionModal = function() {
        $('#editSessionModal').addClass('hidden');
    };

    /**
     * AJAX-отправка данных новой ручной сессии на сервер
     */
    window.submitAddSession = function() {
        var taskId = $('#add_task_id').val();
        var startTime = $('#add_start_time').val();
        var endTime = $('#add_end_time').val();
        var note = $('#add_note').val();

        if (!taskId || !startTime || !endTime) {
            alert('Пожалуйста, заполните все обязательные поля.');
            return;
        }

        $.post(window.globalApi.add_manual, {
            task_id: taskId,
            start_time: startTime,
            end_time: endTime,
            note: note
        }, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    window.closeAddSessionModal();
                    window.location.reload(); // Перезагружаем страницу для обновления списка сессий
                } else {
                    alert(res.message);
                }
            } catch(e) {
                console.error(e);
                alert('Произошла системная ошибка при сохранении сессии.');
            }
        });
    };

    /**
     * AJAX-отправка отредактированных данных сессии на обновление
     */
    window.submitEditSession = function() {
        var sessionId = $('#edit_session_id').val();
        var taskId = $('#edit_task_id').val();
        var startTime = $('#edit_start_time').val();
        var endTime = $('#edit_end_time').val();
        var note = $('#edit_note').val();

        if (!sessionId || !taskId || !startTime || !endTime) {
            alert('Пожалуйста, заполните все обязательные поля.');
            return;
        }

        $.post(window.globalApi.edit_session, {
            session_id: sessionId,
            task_id: taskId,
            start_time: startTime,
            end_time: endTime,
            note: note
        }, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    window.closeEditSessionModal();
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            } catch(e) {
                console.error(e);
                alert('Произошла системная ошибка при изменении сессии.');
            }
        });
    };

    /**
     * AJAX-запрос на удаление выбранной сессии с подтверждением
     * @param {number|string} sessionId - ID удаляемой сессии
     */
    window.deleteSession = function(sessionId) {
        if (!confirm('Вы уверены, что хотите удалить эту сессию активности?')) {
            return;
        }

        $.post(window.globalApi.delete_session, {
            session_id: sessionId
        }, function(response) {
            try {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            } catch(e) {
                console.error(e);
                alert('Не удалось удалить сессию.');
            }
        });
    };

    // Настраиваем бесконечный скролл журнала
    initInfiniteScrollHistory();
}

/**
 * Инициализирует скролл-слушатель для подгрузки записей журнала по AJAX
 */
function initInfiniteScrollHistory() {
    let historyOffset = window.globalPerPage || 25;
    let historyLimit = window.globalPerPage || 25;
    let historyHasMore = true;
    let historyIsLoading = false;

    // Снимаем старый скролл-обработчик перед навешиванием нового
    $(window).off('scroll.history').on('scroll.history', function() {
        // Проверяем, что таблица журнала присутствует на текущей странице
        if ($('#historyTableBody').length === 0) return;
        if (!historyHasMore || historyIsLoading) return;

        // Если прокрутили до 200px до нижнего края
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
            historyIsLoading = true;
            
            // Используем вынесенный URL из window.globalApi
            $.post(window.globalApi.load_more_history, { offset: historyOffset }, function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    if (res.html && res.html.trim() !== '') {
                        $('#historyTableBody').append(res.html);
                        historyOffset += historyLimit;
                    }
                    historyHasMore = res.has_more;
                }
                historyIsLoading = false;
            }).fail(function() {
                historyIsLoading = false;
            });
        }
    });
}
