<?php
// Запрещаем прямой доступ к файлу
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Контроллер для автоматических задач по расписанию (Cron)
 *
 * Вызывается из crontab через HTTP с секретным токеном:
 *   wget -qO- "http://site/index.php/cron/backup_db/ВАШ_ТОКЕН"
 *   curl -s "http://site/index.php/cron/backup_db/ВАШ_ТОКЕН"
 *
 * Токен задаётся в application/config/config.php:
 *   $config['cron_secret_token'] = 'ваш_секретный_токен';
 *
 * НЕ наследует MY_Controller — не требует авторизации через сессию.
 */
class Cron extends CI_Controller {

    /**
     * Проверяет секретный токен из URL.
     * Если токен неверный — отдаёт 403 и завершает выполнение.
     *
     * @param string $token Токен из URL-сегмента
     */
    private function _verify_token($token) {
        $valid_token = $this->config->item('cron_secret_token');

        if (empty($valid_token) || $token !== $valid_token) {
            // Не раскрываем причину отказа
            set_status_header(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
            exit;
        }
    }

    /**
     * Автоматический бэкап базы данных.
     * URL: /cron/backup_db/{secret_token}
     *
     * Создаёт SQL-дамп в директории backups/ — тот же формат,
     * что и ручной бэкап из админки (отображается в списке бэкапов).
     *
     * Для crontab (ежедневно в 03:00):
     *   0 3 * * * curl -s "http://192.168.100.2:7880/index.php/cron/backup_db/ВАШ_ТОКЕН" > /dev/null 2>&1
     *
     * @param string $token Секретный токен для авторизации
     */
    public function backup_db($token = '') {
        $this->_verify_token($token);

        header('Content-Type: application/json');

        $this->load->database();

        $backup_dir = FCPATH . 'backups/';
        if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

        // Защита .htaccess
        $htaccess = $backup_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }

        $filename = 'auto_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backup_dir . $filename;

        try {
            $mysqli  = $this->db->conn_id;
            $db_name = $this->db->database;

            // Заголовок SQL-дампа
            $dump  = "-- Автоматический бэкап (cron) базы данных {$db_name}\n";
            $dump .= "-- Дата: " . date('Y-m-d H:i:s') . "\n";
            $dump .= "-- Метод: PHP mysqli (cron)\n\n";
            $dump .= "SET NAMES utf8mb4;\n";
            $dump .= "SET FOREIGN_KEY_CHECKS = 0;\n";
            $dump .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

            // Проходим по каждой таблице
            $tables_result = $mysqli->query("SHOW TABLES");
            $total_rows = 0;
            $table_count = 0;

            while ($table_row = $tables_result->fetch_row()) {
                $table = $table_row[0];
                $table_count++;

                // Структура таблицы
                $create_row = $mysqli->query("SHOW CREATE TABLE `{$table}`")->fetch_row();
                $dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $dump .= $create_row[1] . ";\n\n";

                // Данные таблицы
                $data_result = $mysqli->query("SELECT * FROM `{$table}`");
                $row_count = $data_result->num_rows;
                $total_rows += $row_count;

                if ($row_count > 0) {
                    $fields = $data_result->fetch_fields();
                    $col_names = [];
                    foreach ($fields as $field) $col_names[] = '`' . $field->name . '`';
                    $columns = implode(', ', $col_names);

                    $batch = [];
                    while ($row = $data_result->fetch_row()) {
                        $values = [];
                        foreach ($row as $val) {
                            $values[] = ($val === null) ? 'NULL' : "'" . $mysqli->real_escape_string($val) . "'";
                        }
                        $batch[] = '(' . implode(', ', $values) . ')';

                        if (count($batch) >= 100) {
                            $dump .= "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n";
                            $batch = [];
                        }
                    }
                    if (!empty($batch)) {
                        $dump .= "INSERT INTO `{$table}` ({$columns}) VALUES\n" . implode(",\n", $batch) . ";\n";
                    }
                    $dump .= "\n";
                }
                $data_result->free();
            }
            $tables_result->free();

            $dump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            $dump .= "-- Конец бэкапа. Таблиц: {$table_count}, записей: {$total_rows}\n";

            // Сохраняем
            if (file_put_contents($filepath, $dump) !== false) {
                $size = filesize($filepath);
                $size_str = ($size > 1048576)
                    ? round($size / 1048576, 2) . ' MB'
                    : round($size / 1024, 1) . ' KB';

                // Удаляем старые авто-бэкапы (хранить 7 последних)
                $this->_cleanup_old_backups($backup_dir, 7);

                echo json_encode([
                    'status'  => 'success',
                    'message' => "Бэкап создан: {$filename} ({$size_str}, {$table_count} таблиц, {$total_rows} записей)"
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Ошибка записи файла']);
            }

        } catch (Exception $e) {
            if (file_exists($filepath)) unlink($filepath);
            echo json_encode(['status' => 'error', 'message' => 'Ошибка: ' . $e->getMessage()]);
        }
    }

    /**
     * Удаляет старые автоматические бэкапы, оставляя последние $keep штук.
     * Удаляются ТОЛЬКО файлы с префиксом «auto_backup_».
     *
     * @param string $dir  Директория с бэкапами
     * @param int    $keep Сколько последних оставить
     */
    private function _cleanup_old_backups($dir, $keep = 7) {
        $auto_backups = [];

        foreach (scandir($dir) as $file) {
            // Удаляем только автоматические бэкапы
            if (strpos($file, 'auto_backup_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $auto_backups[] = [
                    'file' => $file,
                    'time' => filemtime($dir . $file)
                ];
            }
        }

        // Сортируем по времени (новые первые)
        usort($auto_backups, function($a, $b) {
            return $b['time'] - $a['time'];
        });

        // Удаляем всё сверх лимита
        for ($i = $keep; $i < count($auto_backups); $i++) {
            @unlink($dir . $auto_backups[$i]['file']);
        }
    }
}
