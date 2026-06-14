<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Вспомогательный хелпер для Технических заданий (ТЗ)
 */

if (!function_exists('get_file_icon_emoji')) {
    /**
     * Возвращает эмодзи-иконку в зависимости от расширения файла или флага ссылки
     * 
     * @param string $filename Имя файла
     * @param int $is_link Является ли ссылка внешней (1 - да, 0 - нет)
     * @return string Эмодзи-иконка
     */
    function get_file_icon_emoji($filename, $is_link = 0) {
        if ($is_link) {
            return '🔗';
        }
        
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'pdf':
                return '📕';
            case 'doc':
            case 'docx':
                return '📘';
            case 'xls':
            case 'xlsx':
                return '📗';
            case 'png':
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'svg':
            case 'webp':
                return '🖼️';
            case 'zip':
            case 'rar':
            case '7z':
            case 'tar':
            case 'gz':
                return '📦';
            case 'txt':
            case 'sql':
            case 'json':
            case 'html':
            case 'css':
            case 'js':
            case 'php':
                return '📄';
            default:
                return '📎';
        }
    }
}

if (!function_exists('format_payment_type')) {
    /**
     * Форматирует строковый тип оплаты в человекочитаемый вид
     * 
     * @param string $type Тип оплаты (hourly/fixed)
     * @return string Читаемый текст
     */
    function format_payment_type($type) {
        switch ($type) {
            case 'fixed':
                return 'Фиксированная';
            case 'hourly':
                return 'Почасовая';
            default:
                return 'Не указан';
        }
    }
}
