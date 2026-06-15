// --- МОДУЛЬ УПРАВЛЕНИЯ КОРЗИНОЙ (TRASH) ---

// Проверяем, был ли модуль уже загружен (защита от повторной инициализации при AJAX SPA переходах)
if (!window.loadedTrashModule) {
    // Устанавливаем флаг загрузки модуля
    window.loadedTrashModule = true;

    // Восстановление задачи из корзины
    $(document).on('click', '.restore-trash-btn', function() {
        var taskId = $(this).data('task-id');
        
        // Подтверждение восстановления
        if (!confirm(window.globalLang.js_confirm_restore)) {
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).addClass('opacity-50');
        
        $.ajax({
            url: window.globalApi.restore_trash,
            method: 'POST',
            data: { task_id: taskId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    // Перезагружаем страницу корзины через SPA
                    loadAjaxPage(window.location.href);
                } else {
                    alert(response.message || 'Ошибка восстановления');
                    btn.prop('disabled', false).removeClass('opacity-50');
                }
            },
            error: function() {
                alert('Ошибка сети при восстановлении');
                btn.prop('disabled', false).removeClass('opacity-50');
            }
        });
    });

    // Полное безвозвратное удаление из корзины
    $(document).on('click', '.hard-delete-btn', function() {
        var taskId = $(this).data('task-id');
        
        // Предупреждение о безвозвратном удалении
        if (!confirm(window.globalLang.js_confirm_hard_delete)) {
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).addClass('opacity-50');
        
        $.ajax({
            url: window.globalApi.hard_delete,
            method: 'POST',
            data: { task_id: taskId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadAjaxPage(window.location.href);
                } else {
                    alert(response.message || 'Ошибка удаления');
                    btn.prop('disabled', false).removeClass('opacity-50');
                }
            },
            error: function() {
                alert('Ошибка сети при удалении');
                btn.prop('disabled', false).removeClass('opacity-50');
            }
        });
    });
}
