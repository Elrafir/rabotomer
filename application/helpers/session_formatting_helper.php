<?php
// Запрещаем прямой доступ к скрипту во избежание обхода фреймворка
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Вспомогательный хелпер для форматирования сессий времени (Time Sessions)
 *
 * Содержит функции для преобразования дат, длительности сессий
 * и безопасного экранирования текстовых полей перед отправкой в JSON/интерфейс.
 */

if (!function_exists('format_session_short')) {
    /**
     * Форматирует сессию для краткого отображения в каскадной истории (дочерние задачи)
     *
     * @param array $session Сырые данные сессии из БД (ассоциативный массив)
     * @param string $duration_format Языковой формат для sprintf (например, '%d ч %d мин')
     * @return array Отформатированная сессия для отправки клиенту
     */
    function format_session_short($session, $duration_format) {
        // Создаем копию массива сессии, чтобы не изменять оригинальные данные
        $formatted = $session;
        
        // Преобразуем строковую дату начала в формат 'd.m.Y H:i' для вывода на экран
        $formatted['start_formatted'] = date('d.m.Y H:i', strtotime($session['start_time']));
        
        // Преобразуем строковую дату окончания в формат 'H:i' (для экономии места выводим только время)
        $formatted['end_formatted'] = date('H:i', strtotime($session['end_time']));
        
        // Получаем общее время сессии в секундах, рассчитанное на стороне СУБД
        $diff = $session['duration_seconds'];
        
        // Рассчитываем количество полных часов в сессии
        $hours = floor($diff / 3600);
        
        // Рассчитываем количество оставшихся минут после вычета часов
        $minutes = floor(($diff % 3600) / 60);
        
        // Форматируем строку длительности, подставляя часы и минуты в языковой шаблон
        $formatted['duration'] = sprintf($duration_format, $hours, $minutes);
        
        // Безопасно экранируем пользовательскую заметку для предотвращения XSS-атак
        $formatted['note_safe'] = !empty($session['note']) ? htmlspecialchars($session['note'], ENT_QUOTES, 'UTF-8') : '';
        
        // Возвращаем полностью отформатированный ассоциативный массив
        return $formatted;
    }
}

if (!function_exists('format_session_full')) {
    /**
     * Форматирует сессию для полного отображения в ручном редакторе одной задачи
     *
     * @param array $session Сырые данные сессии из БД (ассоциативный массив)
     * @param string $duration_format Языковой формат для sprintf (например, '%d ч %d мин')
     * @return array Отформатированная сессия для отправки клиенту
     */
    function format_session_full($session, $duration_format) {
        // Создаем копию массива сессии для последующих преобразований
        $formatted = $session;
        
        // Преобразуем дату начала в полный формат с секундами 'd.m.Y H:i:s'
        $formatted['start_formatted'] = date('d.m.Y H:i:s', strtotime($session['start_time']));
        
        // Преобразуем дату окончания в полный формат с секундами 'd.m.Y H:i:s'
        $formatted['end_formatted'] = date('d.m.Y H:i:s', strtotime($session['end_time']));
        
        // Вычисляем разницу времени в секундах, так как в этом запросе нет поля duration_seconds из БД
        $diff = strtotime($session['end_time']) - strtotime($session['start_time']);
        
        // Рассчитываем количество полных часов в сессии
        $hours = floor($diff / 3600);
        
        // Рассчитываем количество минут после вычета часов
        $minutes = floor(($diff % 3600) / 60);
        
        // Форматируем строку длительности по переданному шаблону
        $formatted['duration'] = sprintf($duration_format, $hours, $minutes);
        
        // Экранируем заметку для безопасного рендеринга на веб-странице
        $formatted['note_safe'] = !empty($session['note']) ? htmlspecialchars($session['note'], ENT_QUOTES, 'UTF-8') : '';
        
        // Возвращаем подготовленный массив
        return $formatted;
    }
}
