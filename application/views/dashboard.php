<?php
// Загружаем шаблон с рекурсивными функциями дерева задач
$this->load->view('templates/task_list_loop');
?>

<div class="relative min-h-[80vh] pb-32">
    <!-- Блок добавления корневого проекта удален и перенесен в глобальное модальное окно (body.php) -->

    <!-- Список задач -->
    <div class="flex justify-between items-end mb-4">
        <div class="flex items-center gap-6 w-1/3">
            <img src="<?= base_url('assets/img/time_tree.png') ?>" alt="Time Tree" class="w-16 h-16 object-cover rounded-2xl shadow-sm">
            <h2 class="text-3xl font-black text-gray-800"><?= lang('dash_tree_title'); ?></h2>
        </div>
        
        <!-- Кнопка добавления по центру -->
        <div class="flex-1 flex justify-center pb-2">
            <button onclick="openGlobalAddModal()" class="bg-green-600 flex items-center gap-2 hover:opacity-90 text-white font-black py-2 px-8 rounded-full text-lg shadow-lg transition-transform transform hover:scale-105 active:scale-95" title="<?= lang('btn_add'); ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                <?= lang('btn_add'); ?>
            </button>
        </div>

        <!-- Живой поиск -->
        <div class="w-1/3 flex justify-end">
            <input type="text" id="searchTaskInput" placeholder="<?= lang('dash_search_placeholder'); ?>" class="w-full max-w-sm px-5 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm">
        </div>
    </div>
    
    <?php if (empty($tasks_tree)): ?>
        <div class="bg-white p-12 rounded-2xl border border-gray-200 text-center shadow-sm">
            <span class="text-6xl mb-4 block">📋</span>
            <p class="text-2xl text-gray-500"><?= lang('dash_tree_empty'); ?></p>
        </div>
    <?php else: ?>
        <div class="task-tree-root">
            <?php 
                ob_start();
                render_task_tree($tasks_tree, 1, $active_session); 
                $tree_html = ob_get_clean();
                // Раскрываем только корневой UL
                echo str_replace('hidden task-children', 'block', $tree_html);
            ?>
        </div>
        <div class="mt-8 text-center">
            <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-3 px-6 rounded-xl shadow-sm transition-colors" onclick="$('.hidden-completed-root').slideToggle(); $(this).text( $(this).text().indexOf('<?= lang('dash_show_completed_projects') ?>') !== -1 ? '<?= lang('dash_hide_completed_projects') ?>' : '<?= lang('dash_show_completed_projects') ?>' );"><?= lang('dash_show_completed_projects'); ?></button>
        </div>
    <?php endif; ?>
</div>



<!-- Модальное окно для ручной корректировки времени -->
<div id="editTimeModal" onclick="closeEditModal()" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4" style="z-index: 99999;">
    <div id="editModalBody" onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl p-6 md:p-8 transform transition-all max-h-[90vh] flex flex-col relative">
        <!-- Кнопка закрытия -->
        <button onclick="closeEditModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-700 transition-colors bg-white/50 backdrop-blur-md rounded-full p-2 shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xs uppercase tracking-wider font-bold mb-1 text-gray-500 flex-shrink-0"><?= lang('modal_history_title'); ?></h3>
        <p class="text-gray-800 mb-4 text-xl flex-shrink-0 truncate w-full block overflow-hidden text-ellipsis whitespace-nowrap"><?= lang('modal_edit_task'); ?> <span id="modalTaskTitle" class="font-bold"></span></p>
        
        <input type="hidden" id="modalTaskId">

        <!-- Форма добавления -->
        <div class="flex-shrink-0 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-1"><?= lang('modal_edit_start'); ?></label>
                    <input type="datetime-local" id="modalStartTime" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-1"><?= lang('modal_edit_end'); ?></label>
                    <input type="datetime-local" id="modalEndTime" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-1"><?= lang('modal_edit_note'); ?></label>
                <input type="text" id="modalNote" placeholder="<?= lang('modal_note_placeholder'); ?>" class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div class="flex gap-3">
                <button onclick="saveManualSession()" class="flex-grow bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition-colors">
                    <?= lang('modal_edit_save'); ?>
                </button>
                <button onclick="closeEditModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition-colors">
                    <?= lang('btn_cancel'); ?>
                </button>
            </div>
        </div>

        <!-- Список последних сессий -->
        <h4 class="text-xl font-bold mb-2 text-gray-700 border-b pb-2 flex-shrink-0"><?= lang('modal_recent_sessions'); ?></h4>
        <div class="overflow-y-auto flex-grow max-h-[40vh] border border-gray-200 rounded-lg">
            <table class="w-full text-left text-sm text-gray-600 min-w-[600px]">
                <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-2 w-1/4 border-b"><?= lang('modal_col_start'); ?></th>
                        <th class="px-4 py-2 w-1/4 border-b"><?= lang('modal_col_end'); ?></th>
                        <th class="px-4 py-2 w-1/6 border-b"><?= lang('modal_col_duration'); ?></th>
                        <th class="px-4 py-2 w-1/3 border-b"><?= lang('modal_col_note'); ?></th>
                        <th class="px-4 py-2 w-16 text-center border-b"></th>
                    </tr>
                </thead>
                <tbody id="modalSessionsList" class="divide-y divide-gray-100 bg-white">
                    <!-- Сюда подгружаются сессии -->
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400"><?= lang('modal_loading'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Панель выбора цвета (Color Picker) -->
<div id="colorPickerPopover" class="hidden absolute bg-white rounded-lg shadow-xl border border-gray-200 p-1.5 grid grid-cols-5 gap-1.5 w-max" style="z-index: 99999; background-color: #ffffff !important;">
    <button onclick="saveColor('#ef4444')" style="background-color: #ef4444;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#f97316')" style="background-color: #f97316;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#f59e0b')" style="background-color: #f59e0b;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#10b981')" style="background-color: #10b981;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#06b6d4')" style="background-color: #06b6d4;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#3b82f6')" style="background-color: #3b82f6;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#6366f1')" style="background-color: #6366f1;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#a855f7')" style="background-color: #a855f7;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#ec4899')" style="background-color: #ec4899;" class="w-5 h-5 rounded-full hover:scale-125 transition-transform shadow-sm"></button>
    <button onclick="saveColor('')" style="background-color: #e5e7eb;" class="w-5 h-5 rounded-full border border-gray-400 hover:scale-125 transition-transform shadow-sm flex items-center justify-center" title="<?= lang('reports_no_color'); ?>"><svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
</div>

<!-- Модальное окно для Каскадной Истории -->
<div id="cascadeHistoryModal" onclick="closeCascadeModal()" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4" style="z-index: 99999;">
    <div id="cascadeModalBody" onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl p-8 transform transition-all relative flex flex-col h-[85vh]">
        <!-- Кнопка закрытия -->
        <button onclick="closeCascadeModal()" class="absolute top-6 right-6 text-gray-400 hover:text-gray-700 transition-colors bg-white/50 backdrop-blur-md rounded-full p-2 shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
        <h3 class="text-xs uppercase tracking-wider font-bold mb-1 text-gray-500 flex-shrink-0"><?= lang('cascade_history_title'); ?></h3>
        <p class="text-gray-800 mb-4 text-xl flex-shrink-0 truncate w-full block overflow-hidden text-ellipsis whitespace-nowrap"><span id="cascadeModalTaskTitle" class="font-bold"></span></p>
        
        <div class="mb-4">
            <input type="text" id="cascadeSearchInput" placeholder="<?= lang('cascade_search_placeholder'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>
        
        <div class="overflow-y-auto flex-grow border border-gray-200 rounded-lg">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10 shadow-sm">
                    <tr>
                        <th class="px-4 py-2 border-b"><?= lang('modal_col_start'); ?> - <?= lang('modal_col_end'); ?></th>
                        <th class="px-4 py-2 border-b"><?= lang('cascade_col_task'); ?></th>
                        <th class="px-4 py-2 border-b"><?= lang('cascade_col_duration'); ?></th>
                        <th class="px-4 py-2 border-b"><?= lang('cascade_col_note'); ?></th>
                    </tr>
                </thead>
                <tbody id="cascadeModalSessionsList" class="divide-y divide-gray-100 bg-white">
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400"><?= lang('modal_loading'); ?></td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-6 flex-shrink-0 flex justify-end">
            <button onclick="closeCascadeModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-6 rounded-lg transition-colors">
                <?= lang('btn_cancel'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования задачи -->
<div id="editTaskModal" class="hidden fixed inset-0 z-[120] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-8 transform transition-all">
        <h3 class="text-2xl font-bold mb-6 text-gray-800"><?= lang('btn_edit'); ?></h3>
        <input type="hidden" id="editTaskId">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('modal_title_label'); ?></label>
            <input type="text" id="editTaskTitleInput" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>
        <div class="mb-4 flex gap-4">
            <div class="w-1/2">
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('nav_customers'); ?></label>
                <select id="editTaskCustomer" class="customer-select w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="updateRate(this)">
                    <option value=""><?= lang('finance_no_customer'); ?></option>
                    <?php // Проверяем, что список клиентов не пуст, и обходим его в цикле ?>
                    <?php if(!empty($customers)): foreach($customers as $c): ?>
                        <?php // Выводим опцию выбора клиента с подстановкой ID, дефолтной цены (вместо hourly_rate) и имени ?>
                        <option value="<?= $c['id']; ?>" data-rate="<?= htmlspecialchars($c['default_price'] ?? '0.00'); ?>"><?= htmlspecialchars($c['name'] ?? ''); ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="w-1/4">
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('finance_type'); ?></label>
                <select id="editTaskIsFixed" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="0"><?= lang('finance_hourly'); ?></option>
                    <option value="1"><?= lang('finance_fixed'); ?></option>
                </select>
            </div>
            <div class="w-1/4">
                <label class="block text-gray-700 text-sm font-bold mb-2"><?= lang('finance_price'); ?></label>
                <input type="number" step="0.01" id="editTaskPrice" class="rate-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
            <button onclick="closeEditTaskModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-3 px-6 rounded-xl transition-colors">
                <?= lang('btn_cancel'); ?>
            </button>
            <button onclick="saveTaskTitle()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-colors">
                <?= lang('btn_save'); ?>
            </button>
        </div>
    </div>
</div>

<script>
    // URL-ы для AJAX (оставлены для совместимости локальных функций дашборда)
    window.api = window.globalApi;

    // Функция для обновления ставки при выборе клиента в модальном окне редактирования задачи
    function updateRate(selectElem) {
        // Находим выбранную опцию в селекте клиента
        var selectedOption = $(selectElem).find('option:selected');
        // Извлекаем значение ставки из data-rate или используем 0.00 по умолчанию
        var rate = selectedOption.data('rate') || '0.00';
        // Присваиваем ставку полю ввода цены/тарифа задачи
        $('#editTaskPrice').val(rate);
    }

    // Скрипт для подстановки ставки
    function updateRateSubtask(selectElem) {
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
    }

    // Скрипт для живого поиска в модалке каскадной истории
    $('#cascadeSearchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#cascadeModalSessionsList tr').filter(function() {
            // Если это не строка с ошибкой/пусто
            if ($(this).find('td').length > 1) {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            }
        });
    });

    function toggleAddForm(taskId) {
        $('#add-form-' + taskId).slideToggle();
    }

    $(document).on('click', '.toggle-children', function() {
        var li = $(this).closest('li');
        var taskId = li.data('task-id');
        var icon = $(this).find('.icon-expand');
        var childrenList = li.find('> ul.task-children');
        
        childrenList.slideToggle(200, function() {
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

    $(document).ready(function() {
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
    });

    // Логика кнопок старт, стоп и пауза теперь работает через main.js
    // Обработчики pauseTimer, stopTimer, startTimer определены глобально


    function completeTask(taskId) {
        // Окно подтверждения (на всякий случай, чтобы случайно стилусом не закрыть проект)
        if (!confirm("<?= lang('js_confirm_complete'); ?>")) {
            return;
        }
        
        // Если задача была на паузе, очищаем её локальное состояние
        let pausedInfo = localStorage.getItem('pausedTimerInfo');
        if (pausedInfo) {
            try {
                let parsed = JSON.parse(pausedInfo);
                if (parsed.task_id == taskId) {
                    localStorage.removeItem('pausedTimerInfo');
                }
            } catch(e) {}
        }

        $.post(api.complete, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    }

    /**
     * AJAX-обработчик кнопки "Восстановить"
     */
    function restoreTask(taskId) {
        if (!confirm("<?= htmlspecialchars(lang('js_confirm_restore_task'), ENT_QUOTES); ?>")) {
            return;
        }

        $.post(api.restore, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    }

    /**
     * Открытие модального окна для корректировки времени
     */
    function openEditModal(taskId, title, hexColor = '#ffffff') {
        $('#modalTaskId').val(taskId);
        $('#modalTaskTitle').text(title);
        if (hexColor === '') hexColor = '#ffffff';
        // Устанавливаем красивый градиентный фон с легким оттенком цвета задачи (opacity ~15%)
        $('#editModalBody').css('background', 'linear-gradient(135deg, #ffffff 30%, ' + hexColor + '33 100%)');
        $('#modalStartTime').val('');
        $('#modalEndTime').val('');
        $('#modalNote').val('');
        $('#modalSessionsList').html('<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400"><?= lang('modal_loading'); ?></td></tr>');
        $('#editTimeModal').removeClass('hidden');

        // Загружаем сессии этой задачи через AJAX
        $.post(api.get_sessions, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                var html = '';
                if (res.data.length === 0) {
                    html = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400"><?= lang('modal_no_records'); ?></td></tr>';
                } else {
                    res.data.forEach(s => {
                        html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 border-b border-gray-100">${s.start_formatted}</td>
                            <td class="px-4 py-3 border-b border-gray-100">${s.end_formatted}</td>
                            <td class="px-4 py-3 border-b border-gray-100 font-mono">${s.duration}</td>
                            <td class="px-4 py-3 border-b border-gray-100 text-gray-500 text-xs">${s.note_safe}</td>
                            <td class="px-4 py-3 border-b border-gray-100 text-center">
                                <button data-id="${s.id}" class="delete-session-btn text-red-400 hover:text-red-600 transition-colors" title="<?= lang('modal_btn_delete_title'); ?>">
                                    <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>
                        </tr>`;
                    });
                }
                $('#modalSessionsList').html(html);
            }
        });
    }

    /**
     * Закрытие модального окна
     */
    function closeEditModal() {
        $('#editTimeModal').addClass('hidden');
    }

    /**
     * Сохранение новой ручной сессии
     */
    function saveManualSession() {
        var taskId = $('#modalTaskId').val();
        var start = $('#modalStartTime').val();
        var end = $('#modalEndTime').val();
        var note = $('#modalNote').val();

        if (!start || !end) {
            alert('<?= lang('js_alert_fill_fields'); ?>');
            return;
        }

        $.post(api.add_manual, { task_id: taskId, start_time: start, end_time: end, note: note }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                // Если успешно, перезагружаем данные в окне
                openEditModal(taskId, $('#modalTaskTitle').text());
                // И перезагружаем страницу через секунду, чтобы обновить общую статистику
                setTimeout(() => window.location.reload(), 1000);
            } else {
                alert(res.message);
            }
        });
    }

    /**
     * Логика для Каскадного Аккордеона
     */
    function toggleInlineHistory(taskId, btn) {
        var container = $('#inline-history-' + taskId);
        var svg = $(btn).find('.inline-arrow');
        
        // Если контейнер уже открыт, просто скрываем его
        if (!container.hasClass('hidden') && container.html().trim() !== '') {
            container.slideUp(150, function() {
                container.addClass('hidden').html(''); // Очищаем после скрытия
            });
            svg.removeClass('rotate-180');
            return;
        }
        
        svg.addClass('rotate-180');
        container.html('<div class="py-2 px-4 text-gray-400 italic"><?= lang('modal_loading'); ?></div>').removeClass('hidden').hide().slideDown(150);

        $.post(api.get_cascading, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                if (res.data.length === 0) {
                    container.html('<div class="py-2 px-4 text-gray-500 font-medium"><?= lang('cascade_no_sessions'); ?></div>');
                    return;
                }
                
                var html = '<table class="w-full text-left border-collapse text-xs md:text-sm text-gray-600"><thead class="bg-gray-100 text-gray-500 border-b border-gray-200 font-semibold tracking-wide"><tr><th class="px-3 py-1"><?= lang('modal_col_start'); ?> - <?= lang('modal_col_end'); ?></th><th class="px-3 py-1"><?= lang('cascade_col_task'); ?></th><th class="px-3 py-1"><?= lang('cascade_col_duration'); ?></th><th class="px-3 py-1 w-1/3"><?= lang('cascade_col_note'); ?></th></tr></thead><tbody class="divide-y divide-gray-100">';
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
                
                if (res.data.length > showLimit) {
                    var moreCount = res.data.length - showLimit;
                    var moreText = '<?= lang('cascade_show_more'); ?>'.replace('%d', moreCount);
                    html += `<div class="text-center py-2 text-purple-600 text-xs md:text-sm cursor-pointer hover:underline" onclick="openCascadeModal(${taskId}, '')">${moreText}</div>`;
                }
                
                container.html(html);
            } else {
                container.html('<div class="py-2 px-4 text-red-500">Error loading data.</div>');
            }
        });
    }

    function openEditTaskModal(taskId, title, customerId, isFixedPrice, price) {
        $('#editTaskId').val(taskId);
        $('#editTaskTitleInput').val(title);
        $('#editTaskCustomer').val(customerId);
        $('#editTaskIsFixed').val(isFixedPrice);
        $('#editTaskPrice').val(price);
        $('#editTaskModal').removeClass('hidden');
    }

    function closeEditTaskModal() {
        $('#editTaskModal').addClass('hidden');
    }

    function saveTaskTitle() {
        var taskId = $('#editTaskId').val();
        var title = $('#editTaskTitleInput').val();
        var customerId = $('#editTaskCustomer').val();
        var isFixedPrice = $('#editTaskIsFixed').val();
        var price = $('#editTaskPrice').val();

        if (!title) {
            alert('<?= htmlspecialchars(lang('js_err_enter_task_title'), ENT_QUOTES); ?>');
            return;
        }

        $.post(api.edit_title, { 
            task_id: taskId, 
            title: title, 
            customer_id: customerId, 
            is_fixed_price: isFixedPrice, 
            price: price 
        }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                closeEditTaskModal();
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    }

    /**
     * Логика для Каскадной Модалки
     */
    function openCascadeModal(taskId, title, hexColor = '#ffffff') {
        $('#cascadeModalTaskTitle').text(title);
        if (hexColor === '') hexColor = '#ffffff';
        // Устанавливаем красивый градиентный фон с легким оттенком цвета задачи (opacity ~15%)
        $('#cascadeModalBody').css('background', 'linear-gradient(135deg, #ffffff 30%, ' + hexColor + '33 100%)');
        $('#cascadeModalSessionsList').html('<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400"><?= lang('modal_loading'); ?></td></tr>');
        $('#cascadeHistoryModal').removeClass('hidden');

        $.post(api.get_cascading, { task_id: taskId }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                var html = '';
                if (res.data.length === 0) {
                    html = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 font-medium"><?= lang('cascade_no_sessions'); ?></td></tr>';
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
    }

    function closeCascadeModal() {
        $('#cascadeHistoryModal').addClass('hidden');
    }

    /**
     * Удаление сессии (делегирование событий, так как кнопки создаются динамически)
     */
    $(document).on('click', '.delete-session-btn', function() {
        var sessionId = $(this).data('id');
        
        // Быстрое подтверждение от пользователя
        if (confirm("<?= lang('js_confirm_delete'); ?>")) {
            $.post(api.delete_session, { session_id: sessionId }, function(response) {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    // Перезагружаем страницу, чтобы пересчиталось время на главной
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    });

    /**
     * Редактирование названия задачи (простое нативное окно prompt)
     */
    function editTaskTitle(taskId, currentTitle) {
        var newTitle = prompt("<?= lang('btn_edit'); ?>:", currentTitle);
        
        if (newTitle !== null && newTitle.trim() !== "" && newTitle.trim() !== currentTitle) {
            $.post(api.edit_title, { task_id: taskId, title: newTitle.trim() }, function(response) {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    }

    /**
     * Каскадное удаление задачи
     */
    function deleteTaskCascade(taskId) {
        if (confirm("<?= lang('js_confirm_delete_task'); ?>")) {
            $.post(api.delete_task, { task_id: taskId }, function(response) {
                var res = JSON.parse(response);
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    alert(res.message);
                }
            });
        }
    }

    /**
     * Живой поиск по дереву задач
     */
    $(document).on('keyup', '#searchTaskInput', function() {
        var value = $(this).val().toLowerCase().trim();
        
        if (value === "") {
            // Если поле пустое, показываем все корневые задачи и скрываем подзадачи, как было по умолчанию
            $('.task-tree-root li').show();
            // Возвращаем изначальное свернутое состояние для дочерних списков (если нужно)
            // Но проще просто показать все элементы списка
        } else {
            // Сканируем все LI в дереве
            $('.task-tree-root li').each(function() {
                // Ищем span с классом task-title-text
                var taskName = $(this).find('.task-title-text').first().text().toLowerCase();
                
                if (taskName.indexOf(value) > -1) {
                    $(this).show();
                    // Чтобы показать найденного ребенка, нужно принудительно показать всех его родителей
                    $(this).parents('li').show();
                    $(this).parents('ul.task-children').show();
                } else {
                    $(this).hide();
                }
            });
        }
    });

    // --- Color Picker Logic ---
    var activeColorTaskId = null;

    function openColorPicker(e, taskId) {
        e.stopPropagation();
        activeColorTaskId = taskId;
        
        var dot = $(e.target);
        var offset = dot.offset();
        
        $('#colorPickerPopover').css({
            top: offset.top + 20 + 'px',
            left: offset.left + 'px'
        }).removeClass('hidden');
    }

    function saveColor(hexColor) {
        if (!activeColorTaskId) return;

        $.post(api.set_color, { task_id: activeColorTaskId, color: hexColor }, function(response) {
            var res = JSON.parse(response);
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message);
            }
        });
    }

    // Закрытие поповера при клике вне его
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#colorPickerPopover').length && !$(e.target).closest('.w-4.h-4.rounded-full').length) {
            $('#colorPickerPopover').addClass('hidden');
        }
    });

    // --- Бесконечный скролл задач ---
    let taskOffset = <?= isset($per_page) ? $per_page : 25; ?>;
    let taskLimit = <?= isset($per_page) ? $per_page : 25; ?>;
    let taskHasMore = true;
    let taskIsLoading = false;

    $(window).on('scroll', function() {
        if (!taskHasMore || taskIsLoading) return;

        // Если пользователь прокрутил страницу почти до конца (200 пикселей до низа)
        if ($(window).scrollTop() + $(window).height() >= $(document).height() - 200) {
            taskIsLoading = true;
            
            $.post('<?= site_url("tasks/load_more_tasks_ajax"); ?>', { offset: taskOffset }, function(response) {
                let res = JSON.parse(response);
                if (res.status === 'success') {
                    if (res.html && res.html.trim() !== '') {
                        $('.task-tree-root').append(res.html);
                        taskOffset += taskLimit;
                        
                        // Инициализируем свернутые/развернутые списки для новых подзадач из localStorage
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
                    taskHasMore = res.has_more;
                }
                taskIsLoading = false;
            }).fail(function() {
                taskIsLoading = false;
            });
        }
    });
</script>
