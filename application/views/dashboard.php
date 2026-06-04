<?php
if (!function_exists('hex2rgba')) {
    function hex2rgba($color, $opacity = 0.05) {
        if (empty($color)) return 'rgba(229, 231, 235, ' . $opacity . ')';
        $color = ltrim($color, '#');
        if (strlen($color) == 6) {
            $r = hexdec(substr($color, 0, 2));
            $g = hexdec(substr($color, 2, 2));
            $b = hexdec(substr($color, 4, 2));
            return "rgba($r, $g, $b, $opacity)";
        }
        return 'rgba(229, 231, 235, ' . $opacity . ')';
    }
}

// Рекурсивная функция для вывода дерева задач (максимум 3 уровня)
if (!function_exists('render_task_tree')) {
    function render_task_tree($tasks, $level = 1, $active_session = null) {
        if (empty($tasks) || $level > 3) return;
        
        echo '<ul class="space-y-2 ' . ($level > 1 ? 'ml-4 pl-4 border-l-2 border-gray-200 mt-2 hidden task-children' : '') . '">';
        $completed_subtasks_count = 0;
        
        foreach ($tasks as $task) {
            $has_children = !empty($task['children']);
            $is_active = ($active_session && $active_session['task_id'] == $task['id']);
            $is_completed = ($task['status'] === 'completed');
            
            // Если задача завершена, приглушаем её цвет (прозрачность)
            $task_classes = 'p-3 rounded-xl shadow-sm border border-gray-100 transition-shadow hover:shadow-md';
            if ($is_completed) {
                $task_classes .= ' opacity-60';
                if ($level == 1) {
                    $task_classes .= ' hidden-completed-root hidden';
                } else {
                    $task_classes .= ' hidden-completed-subtask hidden';
                    $completed_subtasks_count++;
                }
            }
            
            $pale_bg = hex2rgba($task['color'], 0.05);
            echo '<li class="' . $task_classes . '" style="background-color: ' . $pale_bg . ';">';
            echo '<div class="flex justify-between items-center">';
            
            // Левая часть: Название + Время (Стрелки раскрытия убраны, клик всё еще работает)
            echo '<div class="flex items-center gap-3 cursor-pointer select-none ' . ($has_children ? 'toggle-children' : '') . '">';

            // Название задачи
            $color_bg = !empty($task['color']) ? "background-color: {$task['color']};" : "background-color: #e5e7eb;";
            echo '<div class="tree-dot w-4 h-4 rounded-full shadow-sm border border-gray-200 flex-shrink-0" style="' . $color_bg . '" onclick="openColorPicker(event, ' . $task['id'] . ')" title="' . lang('reports_color_task') . '"></div>';
            echo '<span class="text-lg font-medium ' . ($is_completed ? 'text-gray-500 line-through' : 'text-gray-800') . '">' . htmlspecialchars($task['title'] ?? '') . '</span>';
            
            if (!empty($task['customer_name'])) {
                echo '<span class="text-sm text-gray-400 ml-2">[' . htmlspecialchars($task['customer_name'] ?? '') . ']</span>';
            }
            echo '</div>'; // Конец левой части
            
            // Вывод времени
            echo '<span class="text-base font-bold text-gray-400 ml-4">[' . htmlspecialchars($task['formatted_time'] ?? '') . ']</span>';
            
            // Правая часть: Кнопка Старт, Готово и Подзадача
            echo '<div class="flex items-center gap-2 btn-group-fixed">';
            
            // Если задача НЕ завершена, показываем элементы управления
            if (!$is_completed) {
                if ($level < 3) {
                    echo '<button onclick="toggleAddForm(' . $task['id'] . ')" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-3 rounded-lg text-sm transition-colors" title="' . lang('btn_add_subtask') . '">' . lang('btn_add_subtask') . '</button>';
                }
                
                // Кнопка СТАРТ (если таймер не тут)
                if (!$is_active) {
                    echo '<button onclick="startTimer(' . $task['id'] . ')" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg text-sm shadow-md transition-all active:scale-95 flex items-center gap-2">';
                    echo lang('btn_start');
                    echo '</button>';
                } else {
                    echo '<span class="text-green-500 font-bold text-sm px-3 py-1 border border-green-500 rounded-lg bg-green-50 animate-pulse">' . lang('status_active') . '</span>';
                }

                // Кнопка ГОТОВО
                echo '<button onclick="completeTask(' . $task['id'] . ')" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm shadow-md transition-all active:scale-95 flex items-center gap-2">';
                echo lang('btn_done');
                echo '</button>';

                // Кнопка РУЧНОЙ КОРРЕКТИРОВКИ
                echo '<button onclick="openEditModal(' . $task['id'] . ', \'' . htmlspecialchars($task['title'] ?? '') . '\')" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-3 rounded-lg text-sm shadow-md transition-all active:scale-95 flex items-center gap-2" title="Ручная корректировка времени">';
                echo '🕒';
                echo '</button>';

                // Кнопка РЕДАКТИРОВАТЬ
                echo '<button onclick="editTaskTitle(' . $task['id'] . ', \'' . htmlspecialchars($task['title'] ?? '') . '\')" class="bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 font-bold py-2 px-3 rounded-lg text-sm transition-colors" title="' . lang('btn_edit') . '">';
                echo '✏️';
                echo '</button>';

                // Кнопка УДАЛИТЬ
                echo '<button onclick="deleteTaskCascade(' . $task['id'] . ')" class="bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 font-bold py-2 px-3 rounded-lg text-sm transition-colors" title="' . lang('btn_delete') . '">';
                echo '🗑️';
                echo '</button>';
            } else {
                // Если завершена, просто выводим бейдж и кнопку восстановления
                echo '<span class="text-gray-500 font-bold text-sm px-3 py-1 border border-gray-300 rounded-lg bg-gray-100">' . lang('status_completed') . '</span>';
                echo '<button onclick="restoreTask(' . $task['id'] . ')" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-600 font-bold py-2 px-3 rounded-lg text-sm transition-colors" title="' . lang('btn_restore') . '">🔄</button>';
            }

            // Кнопка Каскадной Модалки (Доступна всегда, содержит логику аккордеона по ховеру)
            echo '<button onmouseenter="startHoverAccordion(this, ' . $task['id'] . ')" onmouseleave="cancelHoverAccordion()" onclick="openCascadeModal(' . $task['id'] . ', \'' . htmlspecialchars($task['title'] ?? '') . '\')" class="bg-purple-50 hover:bg-purple-100 text-purple-600 font-bold py-2 px-3 rounded-lg text-sm transition-colors" title="' . lang('cascade_history_title') . ' (Полная)">';
            echo '📜';
            echo '</button>';
            
            echo '</div>'; // Конец правой части
            echo '</div>'; // Конец строки задачи
            
            // Скрытый контейнер для аккордеона каскадной истории
            echo '<div id="inline-history-' . $task['id'] . '" class="hidden pl-8 bg-gray-50 border-l-2 border-gray-300 text-sm p-2 mb-2 rounded-b-xl shadow-inner"></div>';

            // Скрытая форма добавления подзадачи (только для незавершенных)
            if ($level < 3 && !$is_completed) {
                echo '<div id="add-form-' . $task['id'] . '" class="hidden mt-4 pt-4 border-t border-gray-100">';
                echo form_open('tasks/add', ['class' => 'flex flex-col gap-4']);
                echo '<input type="hidden" name="parent_id" value="' . $task['id'] . '">';
                
                echo '<div class="flex gap-4">';
                echo '<input type="text" name="title" placeholder="' . lang('dash_subtask_placeholder') . '" class="flex-grow px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" required>';
                
                $ci =& get_instance();
                $customers = $ci->load->get_var('customers');
                
                echo '<select name="customer_id" class="customer-select px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="updateRate(this)">';
                echo '<option value="">' . lang('finance_no_customer') . '</option>';
                if (!empty($customers)) {
                    foreach ($customers as $c) {
                        echo '<option value="' . $c['id'] . '" data-rate="' . $c['hourly_rate'] . '">' . htmlspecialchars($c['name'] ?? '') . '</option>';
                    }
                }
                echo '</select>';
                
                echo '<select name="is_fixed_price" class="px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">';
                echo '<option value="0">' . lang('finance_hourly') . '</option>';
                echo '<option value="1">' . lang('finance_fixed') . '</option>';
                echo '</select>';
                
                echo '<input type="number" step="0.01" name="price" placeholder="' . lang('finance_price') . '" class="rate-input w-24 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">';
                echo '<button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-xl text-sm shadow-md transition-colors">' . lang('btn_save') . '</button>';
                echo '</div>';
                
                echo form_close();
                echo '</div>';
            }

            // Вывод детей (с рекурсией)
            if ($has_children) {
                render_task_tree($task['children'], $level + 1, $active_session);
            }
            
            echo '</li>';
        }

        if ($level > 1 && $completed_subtasks_count > 0) {
            $hidden_text = sprintf(lang('dash_hidden_completed_subtasks'), $completed_subtasks_count);
            echo '<li class="text-center mt-2"><button type="button" class="text-sm text-gray-400 hover:text-gray-600 transition-colors bg-gray-50 hover:bg-gray-100 py-2 px-4 rounded-xl border border-gray-200" onclick="$(this).parent().siblings(\'.hidden-completed-subtask\').slideToggle();">' . $hidden_text . '</button></li>';
        }

        echo '</ul>';
    }
}
?>

<div class="relative min-h-[80vh] pb-32">
    <!-- Блок добавления корневого проекта (уровень 1) -->
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 mb-10">
        <h2 class="text-3xl font-bold mb-6 text-gray-800"><?= lang('dash_new_project_title'); ?></h2>
        <?php echo form_open('tasks/add', ['class' => 'flex flex-col gap-4']); ?>
            <div class="flex flex-col md:flex-row gap-4">
            <input type="text" name="title" placeholder="<?= lang('dash_new_project_placeholder'); ?>" class="flex-grow px-5 py-4 bg-gray-50 border border-gray-200 rounded-xl text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            
            <?php 
                $ci =& get_instance();
                $customers = $ci->load->get_var('customers');
            ?>
            <select name="customer_id" class="customer-select px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="updateRate(this)">
                <option value=""><?= lang('finance_no_customer'); ?></option>
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id']; ?>" data-rate="<?= $c['hourly_rate']; ?>"><?= htmlspecialchars($c['name'] ?? ''); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            
            <select name="is_fixed_price" class="px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option value="0"><?= lang('finance_hourly'); ?></option>
                <option value="1"><?= lang('finance_fixed'); ?></option>
            </select>
            
            <input type="number" step="0.01" name="price" placeholder="<?= lang('finance_price'); ?>" class="rate-input w-24 px-4 py-4 bg-gray-50 border border-gray-200 rounded-xl text-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-xl text-xl shadow-lg transition-transform hover:-translate-y-1">
                <?= lang('btn_add'); ?>
            </button>
            </div>
        <?php echo form_close(); ?>
    </div>

    <!-- Список задач -->
    <div class="flex justify-between items-end mb-6">
        <h2 class="text-4xl font-black text-gray-800"><?= lang('dash_tree_title'); ?></h2>
        <!-- Живой поиск -->
        <div class="w-1/3">
            <input type="text" id="searchTaskInput" placeholder="<?= lang('dash_search_placeholder'); ?>" class="w-full px-5 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xl focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm">
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

<!-- Плавающая панель активного таймера -->
<div id="activeTimerPanel" class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-7xl bg-white border border-gray-200 rounded-t-2xl shadow-[0_-10px_40px_rgba(0,0,0,0.1)] transform transition-transform duration-300 z-50 translate-y-full">
    <div class="px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-4">
        
        <div class="flex flex-row items-center gap-6 flex-grow overflow-hidden">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-gray-500 text-sm font-bold uppercase whitespace-nowrap"><?= lang('dash_timer_in_progress'); ?></span>
                <span id="activeTimerTitle" class="text-xl font-black text-emerald-600 truncate max-w-sm md:max-w-md lg:max-w-lg">
                    <!-- Title updated via JS -->
                </span>
            </div>
            
            <div class="flex items-center gap-4 ml-auto">
                <div class="flex flex-col items-end">
                    <div class="text-gray-500 text-xs font-bold uppercase"><?= lang('dash_timer_total'); ?></div>
                    <div id="totalTimerDisplay" class="text-green-600 text-2xl font-mono leading-none">00:00:00</div>
                </div>
                <div class="h-8 border-l border-gray-300 hidden sm:block"></div>
                <div class="flex flex-col items-end hidden sm:flex">
                    <div class="text-gray-500 text-xs font-bold uppercase"><?= lang('dash_timer_current_session'); ?></div>
                    <div id="timerDisplay" class="text-red-600 text-xl font-mono leading-none">00:00:00</div>
                </div>
            </div>
            
            <input type="hidden" id="activeTimerTotal" value="<?php echo $active_session ? (int)$active_session['total_accumulated'] : '0'; ?>">
            <input type="hidden" id="activeTimerElapsed" value="<?php echo $active_session ? (int)$active_session['current_elapsed'] : '0'; ?>">
        </div>
        
        <div class="flex items-center gap-3 ml-auto flex-shrink-0">
            <button id="btnPauseDashboard" onclick="pauseTimer()" class="bg-yellow-500 hover:bg-yellow-400 text-white font-black py-3 px-6 rounded-full text-xl shadow-sm transition-all transform hover:scale-105 active:scale-95 whitespace-nowrap">
                ⏸ <?= lang('btn_pause'); ?>
            </button>
            <button onclick="stopTimer()" class="bg-red-600 hover:bg-red-500 text-white font-black py-3 px-8 rounded-full text-xl shadow-sm transition-all transform hover:scale-105 active:scale-95 whitespace-nowrap">
                <?= lang('btn_stop'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Модальное окно для ручной корректировки времени -->
<div id="editTimeModal" class="hidden fixed inset-0 z-[100] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl p-6 md:p-8 transform transition-all max-h-[90vh] flex flex-col">
        <h3 class="text-2xl font-bold mb-2 text-gray-800 flex-shrink-0"><?= lang('modal_history_title'); ?></h3>
        <p class="text-gray-500 mb-4 text-lg flex-shrink-0"><?= lang('modal_edit_task'); ?> <span id="modalTaskTitle" class="font-bold text-gray-700"></span></p>
        
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
<div id="colorPickerPopover" class="hidden absolute z-[110] bg-white rounded-xl shadow-xl border border-gray-100 p-3 flex flex-wrap gap-2 w-48">
    <button onclick="saveColor('#ef4444')" style="background-color: #ef4444;" class="w-8 h-8 rounded-full hover:scale-110 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#f97316')" style="background-color: #f97316;" class="w-8 h-8 rounded-full hover:scale-110 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#f59e0b')" style="background-color: #f59e0b;" class="w-8 h-8 rounded-full hover:scale-110 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#10b981')" style="background-color: #10b981;" class="w-8 h-8 rounded-full hover:scale-110 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#06b6d4')" style="background-color: #06b6d4;" class="w-8 h-8 rounded-full hover:scale-110 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#3b82f6')" style="background-color: #3b82f6;" class="w-8 h-8 rounded-full hover:scale-110 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#6366f1')" style="background-color: #6366f1;" class="w-8 h-8 rounded-full hover:scale-110 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#a855f7')" style="background-color: #a855f7;" class="w-8 h-8 rounded-full hover:scale-110 transition-transform shadow-sm"></button>
    <button onclick="saveColor('#ec4899')" style="background-color: #ec4899;" class="w-8 h-8 rounded-full hover:scale-110 transition-transform shadow-sm"></button>
    <button onclick="saveColor('')" style="background-color: #e5e7eb;" class="w-8 h-8 rounded-full border-2 border-gray-400 border-dashed hover:scale-110 transition-transform shadow-sm" title="<?= lang('reports_no_color'); ?>"></button>
</div>

<!-- Модальное окно для Каскадной Истории -->
<div id="cascadeHistoryModal" class="hidden fixed inset-0 z-[110] bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl p-6 md:p-8 transform transition-all flex flex-col max-h-[70vh]">
        <h3 class="text-2xl font-bold mb-2 text-gray-800 flex-shrink-0"><?= lang('cascade_history_title'); ?></h3>
        <p class="text-gray-500 mb-4 text-lg flex-shrink-0"><span id="cascadeModalTaskTitle" class="font-bold text-gray-700"></span></p>
        
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
                    <?php if(!empty($customers)): foreach($customers as $c): ?>
                        <option value="<?= $c['id']; ?>" data-rate="<?= $c['hourly_rate']; ?>"><?= htmlspecialchars($c['name'] ?? ''); ?></option>
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
    const api = window.globalApi;

    // Скрипт для подстановки ставки
    function updateRate(selectElem) {
        var selectedOption = selectElem.options[selectElem.selectedIndex];
        var rate = selectedOption.getAttribute('data-rate');
        if (rate !== null && rate !== "") {
            var container = $(selectElem).closest('form');
            if (container.length === 0) {
                container = $(selectElem).parent().parent();
            }
            container.find('.rate-input').val(rate);
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
        const icon = $(this).find('.icon-expand');
        const childrenList = $(this).closest('li').find('> ul.task-children');
        
        childrenList.slideToggle(200);
        
        if (icon.hasClass('rotate-90')) {
            icon.removeClass('rotate-90');
        } else {
            icon.addClass('rotate-90');
        }
    });

    // Логика кнопок старт, стоп и пауза теперь работает через main.js
    // Обработчики pauseTimer, stopTimer, startTimer определены глобально


    /**
     * AJAX-обработчик кнопки "Готово"
     */
    function completeTask(taskId) {
        // Окно подтверждения (на всякий случай, чтобы случайно стилусом не закрыть проект)
        if (!confirm("<?= lang('js_confirm_complete'); ?>")) {
            return;
        }

        $.post(api.complete, { task_id: taskId }, function(response) {
            let res = JSON.parse(response);
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
        if (!confirm("Восстановить задачу?")) {
            return;
        }

        $.post(api.restore, { task_id: taskId }, function(response) {
            let res = JSON.parse(response);
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
    function openEditModal(taskId, title) {
        $('#modalTaskId').val(taskId);
        $('#modalTaskTitle').text(title);
        $('#modalStartTime').val('');
        $('#modalEndTime').val('');
        $('#modalNote').val('');
        $('#modalSessionsList').html('<tr><td colspan="5" class="px-4 py-8 text-center text-gray-400"><?= lang('modal_loading'); ?></td></tr>');
        $('#editTimeModal').removeClass('hidden');

        // Загружаем сессии этой задачи через AJAX
        $.post(api.get_sessions, { task_id: taskId }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                let html = '';
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
        const taskId = $('#modalTaskId').val();
        const start = $('#modalStartTime').val();
        const end = $('#modalEndTime').val();
        const note = $('#modalNote').val();

        if (!start || !end) {
            alert('<?= lang('js_alert_fill_fields'); ?>');
            return;
        }

        $.post(api.add_manual, { task_id: taskId, start_time: start, end_time: end, note: note }, function(response) {
            let res = JSON.parse(response);
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
    function toggleCascadeAccordion(taskId) {
        const container = $('#inline-history-' + taskId);
        
        // Если контейнер уже открыт, просто скрываем его
        if (!container.hasClass('hidden') && container.html().trim() !== '') {
            container.slideUp(200, function() {
                container.addClass('hidden').html(''); // Очищаем после скрытия
            });
            return;
        }
        
        container.html('<div class="py-2 px-4 text-gray-400 italic"><?= lang('modal_loading'); ?></div>').removeClass('hidden').hide().slideDown(200);

        $.post(api.get_cascading, { task_id: taskId }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                if (res.data.length === 0) {
                    container.html('<div class="py-2 px-4 text-gray-500 font-medium"><?= lang('cascade_no_sessions'); ?></div>');
                    return;
                }
                
                let html = '<table class="w-full text-left border-collapse text-xs md:text-sm text-gray-600"><thead class="bg-gray-100 text-gray-500 border-b border-gray-200 font-semibold tracking-wide"><tr><th class="px-3 py-1"><?= lang('modal_col_start'); ?> - <?= lang('modal_col_end'); ?></th><th class="px-3 py-1"><?= lang('cascade_col_task'); ?></th><th class="px-3 py-1"><?= lang('cascade_col_duration'); ?></th><th class="px-3 py-1 w-1/3"><?= lang('cascade_col_note'); ?></th></tr></thead><tbody class="divide-y divide-gray-100">';
                const showLimit = 10;
                const displayData = res.data.slice(0, showLimit);
                
                displayData.forEach(s => {
                    const colorStyle = s.color ? `background-color: ${s.color};` : 'background-color: #e5e7eb;';
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
                    const moreCount = res.data.length - showLimit;
                    const moreText = '<?= lang('cascade_show_more'); ?>'.replace('%d', moreCount);
                    html += `<div class="text-center py-2 text-purple-600 text-xs md:text-sm cursor-pointer hover:underline" onclick="openCascadeModal(${taskId}, '')">${moreText}</div>`;
                }
                
                container.html(html);
            } else {
                container.html('<div class="py-2 px-4 text-red-500">Error loading data.</div>');
            }
        });
    }

    /**
     * Логика для Каскадной Модалки
     */
    function openCascadeModal(taskId, title) {
        $('#cascadeModalTaskTitle').text(title);
        $('#cascadeModalSessionsList').html('<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400"><?= lang('modal_loading'); ?></td></tr>');
        $('#cascadeHistoryModal').removeClass('hidden');

        $.post(api.get_cascading, { task_id: taskId }, function(response) {
            let res = JSON.parse(response);
            if (res.status === 'success') {
                let html = '';
                if (res.data.length === 0) {
                    html = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400 font-medium"><?= lang('cascade_no_sessions'); ?></td></tr>';
                } else {
                    res.data.forEach(s => {
                        const colorStyle = s.color ? `background-color: ${s.color};` : 'background-color: #e5e7eb;';
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
        const sessionId = $(this).data('id');
        
        // Быстрое подтверждение от пользователя
        if (confirm("<?= lang('js_confirm_delete'); ?>")) {
            $.post(api.delete_session, { session_id: sessionId }, function(response) {
                let res = JSON.parse(response);
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
        const newTitle = prompt("<?= lang('btn_edit'); ?>:", currentTitle);
        
        if (newTitle !== null && newTitle.trim() !== "" && newTitle.trim() !== currentTitle) {
            $.post(api.edit_title, { task_id: taskId, title: newTitle.trim() }, function(response) {
                let res = JSON.parse(response);
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
                let res = JSON.parse(response);
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
    $('#searchTaskInput').on('keyup', function() {
        let value = $(this).val().toLowerCase().trim();
        
        if (value === "") {
            // Если поле пустое, показываем все корневые задачи и скрываем подзадачи, как было по умолчанию
            $('.task-tree-root li').show();
            // Возвращаем изначальное свернутое состояние для дочерних списков (если нужно)
            // Но проще просто показать все элементы списка
        } else {
            // Сканируем все LI в дереве
            $('.task-tree-root li').each(function() {
                // Ищем span, который содержит название задачи
                // Он находится внутри первого .flex div, имеет текст
                let taskName = $(this).find('> div:first-child > div:first-child > span.text-2xl').text().toLowerCase();
                
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
    let activeColorTaskId = null;

    function openColorPicker(e, taskId) {
        e.stopPropagation();
        activeColorTaskId = taskId;
        
        const dot = $(e.target);
        const offset = dot.offset();
        
        $('#colorPickerPopover').css({
            top: offset.top + 20 + 'px',
            left: offset.left + 'px'
        }).removeClass('hidden');
    }

    function saveColor(hexColor) {
        if (!activeColorTaskId) return;

        $.post(api.set_color, { task_id: activeColorTaskId, color: hexColor }, function(response) {
            let res = JSON.parse(response);
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
</script>
