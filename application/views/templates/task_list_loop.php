<?php
if (!function_exists('hex2rgba')) {
    function hex2rgba($color, $opacity = 0.1) {
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
    function render_task_tree($tasks, $level = 1, $active_session = null, $parent_breadcrumb = '') {
        if (empty($tasks) || $level > 3) return;
        
        $ul_style = ($level > 1) ? 'style="display: none;"' : '';
        echo '<ul class="space-y-2 ' . ($level > 1 ? 'ml-4 pl-4 border-l-2 border-gray-200 mt-2 task-children' : '') . '" ' . $ul_style . '>';
        $completed_subtasks_count = 0;
        
        foreach ($tasks as $task) {
            $has_children = !empty($task['children']);
            $is_active = ($active_session && $active_session['task_id'] == $task['id']);
            $is_completed = ($task['status'] === 'completed');
            
            $current_title = htmlspecialchars($task['title'] ?? '');
            $breadcrumb = $parent_breadcrumb === '' ? $current_title : $parent_breadcrumb . ' / ' . $current_title;
            
            $modal_title = $breadcrumb;
            if ($has_children) {
                $modal_title .= ' 🗂️';
            }
            
            // Если задача завершена, приглушаем её цвет (прозрачность)
            $task_classes = 'p-3 rounded-xl shadow-sm border border-gray-100 transition-shadow hover:shadow-md';
            $li_display = '';
            if ($is_completed) {
                $task_classes .= ' opacity-60';
                if ($level == 1) {
                    $task_classes .= ' hidden-completed-root';
                    $li_display = 'display: none;';
                } else {
                    $task_classes .= ' hidden-completed-subtask';
                    $completed_subtasks_count++;
                    $li_display = 'display: none;';
                }
            }
            
            $pale_bg = hex2rgba($task['color'], 0.1);
            echo '<li class="' . $task_classes . '" data-task-id="' . $task['id'] . '" style="background-color: ' . $pale_bg . '; ' . $li_display . '">';
            echo '<div class="flex justify-between items-center">';
            
            // Левая часть: Название + Время
            echo '<div class="flex items-center gap-2 cursor-pointer select-none ' . ($has_children ? 'toggle-children' : '') . '">';

            // 1. Кружок выбора цвета
            $color_bg = !empty($task['color']) ? "background-color: {$task['color']};" : "background-color: #e5e7eb;";
            echo '<div class="tree-dot w-4 h-4 rounded-full shadow-sm border border-gray-200 flex-shrink-0 ml-1" style="' . $color_bg . '" onclick="event.stopPropagation(); openColorPicker(event, ' . $task['id'] . ')" title="' . lang('reports_color_task') . '"></div>';

            // 2. Кнопка "Добавить подзадачу"
            if ($level < 3 && !$is_completed) {
                echo '<button onclick="event.stopPropagation(); toggleAddForm(' . $task['id'] . ')" class="text-gray-400 hover:text-green-600 transition-colors w-6 h-6 flex items-center justify-center flex-shrink-0" title="' . lang('btn_add_subtask') . '">';
                echo '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>';
                echo '</button>';
            } else {
                echo '<div class="w-1 h-6"></div>'; // небольшой отступ для выравнивания если кнопки нет
            }

            // 3. Название задачи
            echo '<span class="task-title-text text-lg font-medium ' . ($is_completed ? 'text-gray-500 line-through' : 'text-gray-800') . '">' . htmlspecialchars($task['title'] ?? '') . '</span>';
            
            // 4. Заказчик
            if (!empty($task['customer_name'])) {
                echo '<span class="text-sm text-gray-400">[' . htmlspecialchars($task['customer_name'] ?? '') . ']</span>';
            }

            // 5. Счетчик подзадач и стрелочка
            if ($has_children) {
                $children_count = count($task['children']);
                echo '<div class="flex items-center gap-1 ml-3 text-gray-400 hover:text-gray-700 transition-colors" title="Показать/скрыть подзадачи">';
                echo '<span class="text-xs font-medium bg-gray-100 px-2 py-0.5 rounded-full border border-gray-200 shadow-sm">Подзадач: ' . $children_count . '</span>';
                echo '<div class="w-5 h-5 flex items-center justify-center">';
                echo '<svg class="w-4 h-4 transition-transform duration-200 icon-expand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>';
                echo '</div>';
                echo '</div>';
            }

            echo '</div>'; // Конец левой части
            
            // Контейнер для таймера и кнопок (прижат вправо)
            echo '<div class="flex items-center gap-4 ml-auto pl-4 flex-shrink-0">';
            
            // Вывод времени (фиксированная ширина для идеального выравнивания)
            $timer_classes = 'text-sm font-bold w-36 text-center px-4 py-1.5 rounded-full bg-white shadow-sm border block flex-shrink-0 ';
            if ($is_active) {
                $timer_classes .= 'text-green-500 border-green-200 animate-pulse';
            } else {
                $timer_classes .= 'text-[#800000] border-gray-200';
            }
            echo '<span class="' . $timer_classes . '">' . htmlspecialchars($task['formatted_time'] ?? '') . '</span>';
            
            // Правая часть (кнопки) - фиксированная ширина, чтобы таймер стоял как влитой
            echo '<div class="flex items-center justify-end gap-2 w-[290px] relative">';
            
            if (!$is_completed) {
                // Кнопка СТАРТ (если таймер не тут)
                if (!$is_active) {
                    echo '<button onclick="startTimer(' . $task['id'] . ')" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg text-sm shadow-md transition-all active:scale-95 flex items-center gap-2 flex-shrink-0">';
                    echo lang('btn_start');
                    echo '</button>';
                } else {
                    echo '<button onclick="stopTimer()" class="group text-green-500 hover-stop-blue font-bold text-sm px-3 py-1 border border-green-500 rounded-lg bg-green-50 hover:animate-none animate-pulse transition-colors shadow-sm flex-shrink-0" title="' . htmlspecialchars(lang('dash_stop_timer_title'), ENT_QUOTES) . '">';
                    echo '<span class="group-hover:hidden whitespace-nowrap">' . lang('status_active') . '</span>';
                    echo '<span class="hidden group-hover:inline-flex items-center gap-1.5 whitespace-nowrap"><svg class="w-4 h-4 drop-shadow-sm" viewBox="0 0 24 24"><path d="M8.4 2H15.6L22 8.4V15.6L15.6 22H8.4L2 15.6V8.4L8.4 2Z" fill="white"/><text x="12" y="14.5" fill="#dc2626" font-size="6.5" font-family="sans-serif" font-weight="900" text-anchor="middle">STOP</text></svg> ' . lang('dash_stop_timer_btn') . '</span>';
                    echo '</button>';
                }

                // Кнопка ГОТОВО с кастомным тултипом
                echo '<div class="relative group inline-block flex-shrink-0">';
                echo '<button onclick="completeTask(' . $task['id'] . ')" class="bg-gradient-to-br from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white p-2 rounded-lg shadow-md transition-all active:scale-95 flex items-center justify-center">';
                echo '<span class="text-xl leading-none">🏁</span>';
                echo '</button>';
                echo '<div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-3 hidden group-hover:block w-64 p-4 bg-white rounded-2xl shadow-2xl border border-gray-100 z-[9999] opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">';
                echo '<h4 class="font-black text-gray-800 mb-1 text-sm">Финализировать задачу</h4>';
                echo '<p class="text-xs text-gray-500 leading-tight">' . lang('dash_finalize_desc') . '</p>';
                // Треугольник (хвостик) тултипа
                echo '<div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-white border-b border-r border-gray-100 rotate-45"></div>';
                echo '</div>';
                echo '</div>';

                // Кнопка РЕДАКТИРОВАНИЯ СВОЙСТВ ЗАДАЧИ
                echo '<button onclick="openEditTaskModal(' . $task['id'] . ')" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-3 rounded-lg text-sm shadow-md transition-all active:scale-95 flex items-center gap-2 flex-shrink-0" title="' . htmlspecialchars(lang('dash_edit_properties_title'), ENT_QUOTES) . '">';
                echo '✏️';
                echo '</button>';

                // Кнопка РУЧНОЙ КОРРЕКТИРОВКИ
                echo '<button onclick="openEditModal(' . $task['id'] . ', \'' . addslashes($modal_title) . '\', \'' . htmlspecialchars($task['hex_color'] ?? '#ffffff') . '\')" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-3 rounded-lg text-sm shadow-md transition-all active:scale-95 flex items-center gap-2 flex-shrink-0" title="' . htmlspecialchars(lang('dash_manual_adjust_title'), ENT_QUOTES) . '">';
                echo '✍️';
                echo '</button>';


                // Удаление (в корзину)
                echo '<button onclick="deleteTaskCascade(' . $task['id'] . ')" class="bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 font-bold py-2 px-3 rounded-lg text-sm transition-colors flex-shrink-0" title="' . lang('btn_to_trash') . '">';
                echo '🗑️';
                echo '</button>';
            } else {
                // Если завершена, просто выводим бейдж и кнопку восстановления
                echo '<span class="text-gray-500 font-bold text-sm px-3 py-1 border border-gray-300 rounded-lg bg-gray-100 flex-shrink-0">' . lang('status_completed') . '</span>';
                echo '<button onclick="restoreTask(' . $task['id'] . ')" class="text-gray-400 hover:text-gray-600 font-bold py-2 px-3 rounded-lg text-sm transition-colors flex-shrink-0" title="' . lang('btn_restore') . '">';
                echo '♻️';
                echo '</button>';
                echo '<button onclick="deleteTaskCascade(' . $task['id'] . ')" class="text-red-400 hover:text-red-600 font-bold py-2 px-3 rounded-lg text-sm transition-colors flex-shrink-0" title="' . lang('btn_to_trash') . '">';
                echo '🗑️';
                echo '</button>';
            }

            // Кнопка Каскадной Модалки и стрелочка аккордеона
            echo '<div class="flex items-stretch shadow-sm rounded-lg overflow-hidden">';
            echo '<button onclick="openCascadeModal(' . $task['id'] . ', \'' . addslashes($modal_title) . '\', \'' . htmlspecialchars($task['hex_color'] ?? '#ffffff') . '\')" class="bg-purple-50 hover:bg-purple-100 text-purple-600 font-bold py-2 px-3 text-sm transition-colors border-r border-purple-100" title="' . lang('cascade_history_title') . ' (Полная)">';
            echo '📜';
            echo '</button>';
            echo '<button onclick="toggleInlineHistory(' . $task['id'] . ', this)" class="bg-purple-50 hover:bg-purple-100 text-purple-600 flex items-center justify-center px-1 transition-colors" title="Быстрый просмотр истории">';
            echo '<svg class="w-4 h-4 transition-transform duration-200 inline-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
            echo '</button>';
            echo '</div>';
            
            echo '</div>'; // Конец правой части (w-[290px])
            echo '</div>'; // Конец обертки Таймер+Кнопки
            
            echo '</div>'; // Конец строки задачи (justify-between)
            
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
                
                echo '<div class="flex flex-col gap-2">';
                echo '<select name="customer_id" class="customer-select px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="updateRateSubtask(this)">';
                echo '<option value="">' . lang('finance_no_customer') . '</option>';
                echo '<option value="new" class="font-bold text-blue-600">' . lang('dash_add_new_client') . '</option>';
                if (!empty($customers)) {
                    foreach ($customers as $c) {
                        echo '<option value="' . $c['id'] . '">' . htmlspecialchars($c['name'] ?? '') . '</option>';
                    }
                }
                echo '</select>';
                echo '<div class="new-customer-fields hidden flex gap-2">';
                echo '<input type="text" name="new_customer_name" placeholder="' . htmlspecialchars(lang('dash_client_name_placeholder'), ENT_QUOTES) . '" class="w-full px-4 py-2 bg-white border border-blue-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm">';
                echo '<input type="text" name="new_customer_notes" placeholder="' . htmlspecialchars(lang('dash_client_notes_placeholder'), ENT_QUOTES) . '" class="w-full px-4 py-2 bg-white border border-blue-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none shadow-sm">';
                echo '</div>';
                echo '</div>';
                
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
