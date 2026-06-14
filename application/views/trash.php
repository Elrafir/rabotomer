<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-800">🗑️ Корзина</h2>
    <p class="text-gray-500 mt-2">Здесь находятся удаленные задачи. Вы можете восстановить их или удалить навсегда.</p>
</div>

<?php
// Вспомогательная функция для конвертации HEX в RGBA (если её нет в хелперах)
if (!function_exists('hex2rgba')) {
    function hex2rgba($color, $opacity = false) {
        $default = 'rgb(0,0,0)';
        if (empty($color)) return $default;
        if ($color[0] == '#') $color = substr($color, 1);
        if (strlen($color) == 6) $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        elseif (strlen($color) == 3) $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        else return $default;
        $rgb = array(hexdec($hex[0]), hexdec($hex[1]), hexdec($hex[2]));
        if ($opacity) {
            if (abs($opacity) > 1) $opacity = 1.0;
            $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(",", $rgb) . ')';
        }
        return $output;
    }
}

// Рекурсивная функция для вывода дерева удаленных задач
if (!function_exists('render_trash_tree')) {
    function render_trash_tree($tasks, $level = 1, $parent_breadcrumb = '') {
        if (empty($tasks) || $level > 3) return;
        
        $ul_style = ($level > 1) ? 'style="display: none;"' : '';
        echo '<ul class="space-y-2 ' . ($level > 1 ? 'ml-4 pl-4 border-l-2 border-gray-200 mt-2 task-children' : '') . '" ' . $ul_style . '>';
        
        foreach ($tasks as $task) {
            $has_children = !empty($task['children']);
            $current_title = htmlspecialchars($task['title'] ?? '');
            
            $task_classes = 'p-3 rounded-xl shadow-sm border border-gray-100 transition-shadow hover:shadow-md opacity-80';
            $pale_bg = hex2rgba($task['color'], 0.1);
            
            echo '<li class="' . $task_classes . '" data-task-id="' . $task['id'] . '" style="background-color: ' . $pale_bg . ';">';
            echo '<div class="flex justify-between items-center">';
            
            // Левая часть: Кружок + Название
            echo '<div class="flex items-center gap-3 select-none flex-grow overflow-hidden">';
            
            // Цветной кружок
            echo '<div class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: ' . ($task['color'] ? htmlspecialchars($task['color']) : '#cbd5e1') . ';" title="Цвет задачи"></div>';
            
            // Название задачи
            echo '<div class="flex flex-col overflow-hidden min-w-0 pr-4">';
            echo '<div class="font-bold text-gray-800 truncate text-lg ' . ($has_children ? 'toggle-children cursor-pointer' : '') . '" title="' . $current_title . '">';
            echo $current_title;
            echo '</div>';
            
            if (!empty($task['customer_name'])) {
                echo '<div class="text-sm text-gray-500 truncate mt-0.5">👤 ' . htmlspecialchars($task['customer_name']) . '</div>';
            }
            echo '</div>'; // Конец flex-col названия
            
            // Индикатор вложенности
            if ($has_children) {
                $sub_count = count($task['children']);
                echo '<div class="flex flex-col items-center justify-center flex-shrink-0 cursor-pointer toggle-children ml-2 text-gray-400 hover:text-blue-500 transition-colors" title="Показать/скрыть подзадачи (' . $sub_count . ')">';
                echo '<span class="text-xs font-bold leading-none mb-1">' . $sub_count . '</span>';
                echo '<svg class="w-4 h-4 icon-expand transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>';
                echo '</div>';
            }
            echo '</div>'; // Конец левой части
            
            // Правая часть: Кнопки Восстановить и Удалить навсегда
            echo '<div class="flex gap-2 flex-shrink-0">';
            
            // Кнопка восстановления
            echo '<button class="restore-trash-btn w-10 h-10 rounded-full flex items-center justify-center text-green-500 hover:bg-green-100 hover:text-green-700 transition-colors" data-task-id="' . $task['id'] . '" title="Восстановить (вернутся и подзадачи)">';
            echo '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
            echo '</button>';
            
            // Кнопка окончательного удаления
            echo '<button class="hard-delete-btn w-10 h-10 rounded-full flex items-center justify-center text-red-400 hover:bg-red-100 hover:text-red-600 transition-colors" data-task-id="' . $task['id'] . '" title="Удалить навсегда!">';
            echo '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
            echo '</button>';
            
            echo '</div>'; // Конец правой части
            
            echo '</div>'; // Конец строки flex justify-between
            
            // Вывод подзадач
            if ($has_children) {
                render_trash_tree($task['children'], $level + 1);
            }
            
            echo '</li>';
        }
        
        echo '</ul>';
    }
}
?>

<div class="relative min-h-[80vh] pb-32">
    <div class="bg-white rounded-3xl shadow-sm p-4 border border-gray-100">
        <?php if (!empty($tasks_tree)): ?>
            <?php render_trash_tree($tasks_tree, 1); ?>
        <?php else: ?>
            <div class="text-center py-16 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <p class="text-xl font-bold">Корзина пуста</p>
            </div>
        <?php endif; ?>
    </div>
</div>

