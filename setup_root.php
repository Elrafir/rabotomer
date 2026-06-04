<?php
// Скрипт для создания пользователя root
// Запустите этот скрипт один раз через браузер: http://ваш-ip/setup_root.php

$host = '127.0.0.1';
$db   = 'time_tracker';
$user = 'root';
$pass = ''; // Пустой пароль, как в database.php
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Хэшируем пароль "root" (можете поменять на любой другой)
$password_plain = 'root';
$password_hashed = password_hash($password_plain, PASSWORD_BCRYPT);

// Проверяем, есть ли уже root
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'root'");
$stmt->execute();
$existing_user = $stmt->fetch();

if ($existing_user) {
    echo "<h1>Пользователь root уже существует!</h1>";
} else {
    // Создаем пользователя
    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES ('root', :password)");
    $stmt->execute(['password' => $password_hashed]);
    
    echo "<h1>Пользователь root успешно создан!</h1>";
    echo "<p>Логин: <b>root</b></p>";
    echo "<p>Пароль: <b>root</b></p>";
    echo "<p style='color:red'>В целях безопасности обязательно удалите этот файл (setup_root.php) после использования!</p>";
}
