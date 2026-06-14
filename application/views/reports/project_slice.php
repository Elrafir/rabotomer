<?php
// Ограничиваем прямой вызов файла в обход MVC фреймворка
defined('BASEPATH') OR exit('No direct script access allowed');

// Проверяем наличие вспомогательной функции преобразования HEX цвета в RGBA для прозрачных фонов.
// Если функции нет в глобальном контексте, объявляем её локально.
if (!function_exists('stats_hex2rgba')) {
    /**
     * Конвертирует шестнадцатеричный цвет HEX в строковый формат rgba().
     * Интенсивность opacity задает степень прозрачности фона.
     */
    function stats_hex2rgba($color, $opacity = 0.08) {
        // Если цвет пустой, возвращаем дефолтный серый цвет с заданной прозрачностью
        if (empty($color)) return 'rgba(229, 231, 235, ' . $opacity . ')';
        // Убираем решетку из начала строки, если она присутствует
        $color = ltrim($color, '#');
        // Если длина строки цвета равна 6 символам (стандартный HEX)
        if (strlen($color) === 6) {
            // Преобразуем первые два символа в десятичный канал красного цвета
            $r = hexdec(substr($color, 0, 2));
            // Преобразуем средние два символа в канал зелёного цвета
            $g = hexdec(substr($color, 2, 2));
            // Преобразуем последние два символа в канал синего цвета
            $b = hexdec(substr($color, 4, 2));
            // Возвращаем готовую CSS строку в формате rgba
            return "rgba($r, $g, $b, $opacity)";
        }
        // Возврат по умолчанию при неверном формате цвета
        return 'rgba(229, 231, 235, ' . $opacity . ')';
    }
}

// Рекурсивная функция для рендеринга дерева проектов и задач со статистикой
if (!function_exists('render_stats_project_tree')) {
    /**
     * Выводит иерархический список UL/LI для дерева задач.
     * Задает отступы для уровней и скрывает дочерние подзадачи по умолчанию.
     */
    function render_stats_project_tree($nodes, $level = 1) {
        // Если массив узлов пустой, выходим из рекурсии
        if (empty($nodes)) return;
        
        // Дочерние уровни (больше 1) по умолчанию скрыты через inline-стили для плавного открытия по клику
        $ul_style = ($level > 1) ? 'style="display: none;"' : '';
        
        // Генерируем открывающий тег UL с классами отступов и левой границей для вложенных уровней
        echo '<ul class="space-y-2 ' . ($level > 1 ? 'ml-4 pl-4 border-l border-gray-200 mt-2 stats-children' : '') . '" ' . $ul_style . '>';
        
        // Перебираем в цикле все задачи на текущем уровне дерева
        foreach ($nodes as $node) {
            // Определяем, есть ли у текущей задачи дочерние подзадачи
            $has_children = !empty($node['children']);
            
            // Преобразуем HEX цвет в прозрачный RGBA для заливки карточки
            $pale_bg = stats_hex2rgba($node['color'], 0.06);
            
            // Задаем базовый набор стилей для карточки задачи
            $li_classes = 'p-4 rounded-2xl border border-gray-100/50 shadow-sm transition-shadow hover:shadow-md';
            
            // Если задача завершена, визуально приглушаем её непрозрачность
            if ($node['status'] === 'completed') {
                // Добавляем класс полупрозрачности
                $li_classes .= ' opacity-60';
            }
            
            // Выводим элемент списка LI с индивидуальным фоном и дата-атрибутом ID задачи
            echo '<li class="' . $li_classes . '" data-task-id="' . $node['id'] . '" style="background-color: ' . $pale_bg . ';">';
            
            // Обертка flexbox для выравнивания содержимого по горизонтали
            echo '<div class="flex justify-between items-center gap-4">';
            
            // Левая группа: маркер, название и кнопка раскрытия детей (аккордеон)
            // Добавляем класс toggle-stats-children, если у задачи есть подзадачи, чтобы на него вешался клик в JS
            echo '<div class="flex items-center gap-3 min-w-0 ' . ($has_children ? 'toggle-stats-children cursor-pointer select-none' : '') . '">';
            
            // Цветной кружок-маркер задачи. Цвет берется из БД
            $color_bg = !empty($node['color']) ? "background-color: {$node['color']};" : "background-color: #e5e7eb;";
            // Выводим HTML-тег маркера
            echo '<div class="w-3.5 h-3.5 rounded-full border border-gray-200 shadow-sm flex-shrink-0" style="' . $color_bg . '"></div>';
            
            // Выводим название задачи. Стилизуем зачеркиванием, если задача завершена
            $title_classes = 'font-bold text-gray-800 text-sm truncate ' . ($node['status'] === 'completed' ? 'line-through text-gray-400' : '');
            // Пишем название
            echo '<span class="' . $title_classes . '">' . htmlspecialchars($node['title'] ?? '') . '</span>';
            
            // Если у задачи напрямую или каскадно определен заказчик, выводим его имя в квадратных скобках
            if (!empty($node['customer_name'])) {
                echo '<span class="text-[11px] text-gray-400 font-medium px-1.5 py-0.5 rounded bg-gray-50 border border-gray-100 flex-shrink-0">[' . htmlspecialchars($node['customer_name']) . ']</span>';
            }
            
            // Если у задачи есть вложенные подзадачи, выводим бейдж со счетчиком и стрелочку
            if ($has_children) {
                // Количество прямых потомков
                $children_count = count($node['children']);
                // Выводим бейдж счетчика
                echo '<span class="text-[10px] font-bold bg-gray-100 text-gray-400 px-2 py-0.5 rounded-full border border-gray-200/50 shadow-inner flex-shrink-0">Подзадач: ' . $children_count . '</span>';
                // Выводим иконку стрелочки, которая будет плавно вращаться при раскрытии аккордеона
                echo '<svg class="w-4 h-4 text-gray-400 transition-transform duration-200 icon-stats-expand flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>';
            }
            
            // Закрываем левую группу flexbox
            echo '</div>';
            
            // Правая группа: отображение каскадного времени
            echo '<div class="flex-shrink-0">';
            
            // Округлая плашка с накопленным временем. Для активных задач цвет текста синий, для архивных — серый.
            $time_badge_classes = 'text-xs font-black px-4 py-1.5 rounded-full bg-white shadow-sm border whitespace-nowrap block ';
            // Добавляем стиль в зависимости от статуса задачи
            $time_badge_classes .= ($node['status'] === 'completed') ? 'text-gray-400 border-gray-100' : 'text-indigo-600 border-indigo-100';
            
            // Выводим бейдж времени на экран
            echo '<span class="' . $time_badge_classes . '">' . htmlspecialchars($node['formatted_time'] ?? '') . '</span>';
            
            // Закрываем правую группу
            echo '</div>';
            
            // Закрываем обертку строки flexbox
            echo '</div>';
            
            // Если есть дети, рекурсивно вызываем функцию для рендеринга вложенного списка UL
            if ($has_children) {
                // Рекурсивный запуск на уровень ниже (+1)
                render_stats_project_tree($node['children'], $level + 1);
            }
            
            // Закрываем элемент списка LI
            echo '</li>';
        }
        
        // Закрываем список UL
        echo '</ul>';
    }
}
?>

<!-- Обертка страницы проектного среза с отступами -->
<div class="w-full min-h-[80vh] pb-32">
    
    <!-- Шапка страницы: Логотип и Главный заголовок -->
    <div class="flex justify-between items-end mb-4">
        <!-- Flex-контейнер шапки -->
        <div class="flex items-center gap-6">
            <!-- Иконка раздела отчетов -->
            <img src="<?= base_url('assets/img/reports_logo.png') ?>" alt="Reports Logo" class="w-16 h-16 object-cover rounded-2xl shadow-sm">
            <!-- Текст заголовка "Проектный срез" -->
            <h1 class="text-3xl font-black text-gray-800">Проектный срез</h1>
        </div>
    </div>

    <?php 
    // Загружаем общий тулбар управления фильтрами.
    // Передаем параметры: отключаем фильтрацию дат (show_dates => false) и передаем флаг архивных задач
    $this->load->view('reports/toolbar', [
        'show_dates' => false, 
        'show_archived' => $show_archived,
        // Передаем активный фильтр по заказчикам, сортировке и список всех клиентов
        'customer_filter' => $customer_filter,
        'sort_by' => $sort_by,
        'customers' => $customers
    ]); 
    ?>

    <!-- Информационный заголовок раздела дерева -->
    <h2 class="text-lg font-black text-gray-800 mb-6 flex items-center gap-2">
        <span>📂</span>
        <span>Дерево проектов с каскадным суммированием времени</span>
    </h2>

    <?php 
    // Если дерево проектов пустое (нет задач, соответствующих фильтрам)
    if (empty($projects_tree)){ 
    ?>
        <!-- Выводим пустую заглушку -->
        <div class="bg-white p-12 rounded-3xl border border-gray-100 text-center shadow-sm">
            <!-- Иконка папки -->
            <span class="text-5xl mb-4 block">📁</span>
            <!-- Текст заглушки -->
            <p class="text-lg font-bold text-gray-500">Задачи и проекты не найдены.</p>
            <!-- Описание причин -->
            <p class="text-sm text-gray-400 mt-1">Возможно, у вас скрыты архивные проекты или база данных пуста.</p>
        </div>
    <?php 
    } else { 
    ?>
        <!-- Обертка для дерева проектов -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm space-y-4">
            <?php 
            // Запускаем рекурсивный рендеринг дерева проектов с первого уровня (Level 1)
            render_stats_project_tree($projects_tree, 1); 
            ?>
        </div>
    <?php 
    } 
    ?>

</div>
