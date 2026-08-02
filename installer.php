<?php
/**
 * Скрипт миграции (установщик) для системы Работомер.
 * Позволяет распаковать архив бэкапа и развернуть базу данных на новом сервере.
 */

// Увеличиваем лимиты времени и памяти для работы с большими архивами и дампами
set_time_limit(300);
ini_set('memory_limit', '512M');

$step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
$error = '';
$success = '';

// Получаем список ZIP-архивов в текущей директории
$archives = glob('*.zip');

// =========================================================================
//  ШАГ 2: Обработка отправленной формы установки
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $archive_file = $_POST['archive'] ?? '';
    $db_host = $_POST['db_host'] ?? '127.0.0.1';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? 'time_tracker';

    try {
        if (empty($archive_file) || !file_exists($archive_file)) {
            throw new Exception("Архив не выбран или не существует: " . htmlspecialchars($archive_file));
        }

        // 1. Распаковка архива
        if (!class_exists('ZipArchive')) {
            throw new Exception("Расширение PHP ZipArchive не установлено на этом сервере.");
        }

        $zip = new ZipArchive;
        if ($zip->open($archive_file) === TRUE) {
            $extract_path = __DIR__; // Текущая директория установки
            $zip->extractTo($extract_path);
            $zip->close();
        } else {
            throw new Exception("Не удалось открыть ZIP-архив.");
        }

        // 2. Работа с базой данных
        // Подключаемся без выбора конкретной БД для начала
        $mysqli = new mysqli($db_host, $db_user, $db_pass);
        if ($mysqli->connect_error) {
            throw new Exception("Ошибка подключения к MySQL: " . $mysqli->connect_error);
        }
        $mysqli->set_charset("utf8mb4");

        // Создаем БД, если её нет
        $escaped_db_name = $mysqli->real_escape_string($db_name);
        if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS `$escaped_db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            throw new Exception("Не удалось создать базу данных: " . $mysqli->error);
        }

        // Выбираем созданную/существующую БД
        $mysqli->select_db($db_name);

        // 3. Импорт дампа БД
        $dump_file = __DIR__ . '/_database_dump.sql';
        if (file_exists($dump_file)) {
            $sql = file_get_contents($dump_file);
            if (!empty($sql)) {
                // Выполняем множество запросов из дампа
                if (!$mysqli->multi_query($sql)) {
                    throw new Exception("Ошибка при импорте дампа SQL: " . $mysqli->error);
                }
                
                // Очищаем буфер результатов multi_query, чтобы избежать ошибок синхронизации
                do {
                    if ($res = $mysqli->store_result()) {
                        $res->free();
                    }
                } while ($mysqli->more_results() && $mysqli->next_result());
                
                // Удаляем временный дамп после успешного импорта
                unlink($dump_file);
            }
        } else {
            // Если дампа нет, возможно это не полный бэкап, но мы не прерываем установку
            $error .= "Внимание: Файл дампа базы данных _database_dump.sql не найден в архиве.<br>";
        }
        
        $mysqli->close();

        // 4. Обновление database.php (Настройки CodeIgniter)
        $db_config_file = __DIR__ . '/application/config/database.php';
        if (file_exists($db_config_file)) {
            $db_config = file_get_contents($db_config_file);
            
            // Заменяем хост
            $db_config = preg_replace("/'hostname'\s*=>\s*'[^']*'/", "'hostname' => '{$db_host}'", $db_config);
            // Заменяем пользователя
            $db_config = preg_replace("/'username'\s*=>\s*'[^']*'/", "'username' => '{$db_user}'", $db_config);
            // Заменяем пароль
            $db_config = preg_replace("/'password'\s*=>\s*'[^']*'/", "'password' => '{$db_pass}'", $db_config);
            // Заменяем имя базы
            $db_config = preg_replace("/'database'\s*=>\s*'[^']*'/", "'database' => '{$db_name}'", $db_config);
            
            file_put_contents($db_config_file, $db_config);
        }

        // 5. Обновление config.php (Базовый URL)
        $config_file = __DIR__ . '/application/config/config.php';
        if (file_exists($config_file)) {
            $config_content = file_get_contents($config_file);
            
            // Определяем текущий URL (IP или домен) устройства
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST']; // Возвращает IP/домен и порт
            $path = dirname($_SERVER['SCRIPT_NAME']); 
            // Приводим путь к нормальному виду (со слешем на конце)
            if ($path === '\\' || $path === '/') $path = '';
            
            $new_base_url = $protocol . $host . $path . '/';
            
            // Заменяем $config['base_url']
            $config_content = preg_replace("/\\\$config\['base_url'\]\s*=\s*'[^']*';/", "\$config['base_url'] = '{$new_base_url}';", $config_content);
            
            file_put_contents($config_file, $config_content);
        }

        $success = "Сайт успешно развернут и настроен!";
        $step = 3;

    } catch (Exception $e) {
        $error = $e->getMessage();
        $step = 1; // Возвращаем на первый шаг при ошибке
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка Работомера</title>
    <!-- Подключаем Tailwind CSS для красивого современного дизайна -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

<div class="max-w-xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
    <!-- Шапка установщика -->
    <div class="bg-blue-600 p-6 text-white text-center">
        <h1 class="text-3xl font-black">🚀 Установка сайта</h1>
        <p class="mt-2 opacity-80">Мастер развертывания из резервной копии</p>
    </div>

    <div class="p-8">
        <?php if ($error): ?>
            <!-- Блок вывода ошибки -->
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                <p class="font-bold">Ошибка</p>
                <p><?= $error ?></p>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <!-- Шаг 1: Форма настроек -->
            <form method="POST" action="installer.php">
                <input type="hidden" name="step" value="2">
                
                <!-- Выбор архива для распаковки -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        Выберите ZIP-архив с бэкапом
                    </label>
                    <select name="archive" required class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php if (empty($archives)): ?>
                            <option value="">Архивы не найдены в текущей папке!</option>
                        <?php else: ?>
                            <?php foreach ($archives as $arch): ?>
                                <option value="<?= htmlspecialchars($arch) ?>"><?= htmlspecialchars($arch) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Положите скачанный ZIP в одну папку с installer.php</p>
                </div>

                <hr class="my-6 border-gray-200">
                
                <h3 class="text-lg font-bold text-gray-800 mb-4">Настройки базы данных MySQL</h3>
                
                <!-- Хост базы данных -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Хост БД (IP)</label>
                    <input type="text" name="db_host" value="127.0.0.1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Пользователь базы данных -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Пользователь БД</label>
                    <input type="text" name="db_user" value="root" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Пароль базы данных -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Пароль БД</label>
                    <input type="text" name="db_pass" value="" placeholder="Если нет пароля, оставьте пустым" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Имя базы данных -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Имя базы данных</label>
                    <input type="text" name="db_name" value="time_tracker" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">База будет создана автоматически, если её нет.</p>
                </div>

                <div class="flex items-center justify-between mt-8">
                    <button type="submit" <?= empty($archives) ? 'disabled' : '' ?> class="<?= empty($archives) ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700' ?> text-white font-bold py-3 px-4 rounded-xl w-full transition-colors shadow-lg">
                        Распаковать и Установить
                    </button>
                </div>
            </form>

        <?php elseif ($step === 3): ?>
            <!-- Шаг 3: Успешное завершение -->
            <div class="text-center">
                <div class="text-6xl mb-4">🎉</div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= $success ?></h2>
                <p class="text-gray-600 mb-6">Файлы распакованы, база данных импортирована, конфиги успешно обновлены.</p>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8 text-left rounded">
                    <p class="font-bold text-yellow-800">⚠️ Важное замечание по безопасности:</p>
                    <p class="text-yellow-700 text-sm mt-1">
                        Обязательно удалите файл <b>installer.php</b> и архив <b><?= htmlspecialchars($_POST['archive'] ?? '') ?></b> из корневой папки, чтобы предотвратить несанкционированную перезапись вашего сайта.
                    </p>
                </div>

                <a href="index.php" class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-colors">
                    Перейти на сайт
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
