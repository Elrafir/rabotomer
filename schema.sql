-- Структура базы данных для Тайм-трекера

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `time_sessions`;
DROP TABLE IF EXISTS `tasks`;
DROP TABLE IF EXISTS `users`;


-- Таблица пользователей
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный ID пользователя',
    `username` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Имя пользователя (логин)',
    `password` VARCHAR(255) NOT NULL COMMENT 'Хэш пароля (используется password_hash)',
    `last_name` VARCHAR(50) DEFAULT NULL,
    `group_id` INT DEFAULT 2,
    `user_theme` VARCHAR(50) DEFAULT 'theme-default',
    `user_theme_opacity` DECIMAL(3,2) DEFAULT 1.00,
    `remember_token` VARCHAR(64) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время регистрации'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Пользователи системы';

-- Таблица задач (поддерживает иерархию через parent_id)
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный ID задачи',
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Привязка к пользователю (Изоляция данных)',
    `parent_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'ID родительской задачи (NULL если это корень)',
    `title` VARCHAR(255) NOT NULL COMMENT 'Название задачи',
    `description` TEXT DEFAULT NULL COMMENT 'Подробное описание задачи',
    `status` ENUM('active', 'completed') NOT NULL DEFAULT 'active' COMMENT 'Статус задачи',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата и время создания задачи',
    
    -- Внешние ключи для целостности данных
    -- При удалении пользователя удалятся все его задачи
    CONSTRAINT `fk_tasks_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    -- При удалении родительской задачи удалятся все подзадачи
    CONSTRAINT `fk_tasks_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Дерево задач';

-- Таблица сессий времени (трекинг)
CREATE TABLE IF NOT EXISTS `time_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Уникальный ID сессии',
    `user_id` INT UNSIGNED NOT NULL COMMENT 'Привязка к пользователю',
    `task_id` INT UNSIGNED NOT NULL COMMENT 'Привязка к задаче',
    `start_time` DATETIME NOT NULL COMMENT 'Время старта таймера',
    `end_time` DATETIME NULL DEFAULT NULL COMMENT 'Время остановки (NULL, если таймер сейчас активен)',
    
    -- Внешние ключи
    CONSTRAINT `fk_time_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_time_sessions_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Сессии трекинга времени';

-- Создадим тестового пользователя:
-- Логин: artist, Пароль: 123456
-- Хэш сгенерирован функцией password_hash('123456', PASSWORD_DEFAULT);
INSERT IGNORE INTO `users` (`id`, `username`, `password`) VALUES
(1, 'artist', '$2y$10$P5o.h04u5/aK30eD8131y.qg6F7yN0s.LwN/0m4r5h/eM7O0D45B.');

SET FOREIGN_KEY_CHECKS = 1;
