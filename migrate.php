<?php
$hostname = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'time_tracker';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM time_sessions LIKE 'last_heartbeat'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE time_sessions ADD COLUMN last_heartbeat DATETIME NULL DEFAULT NULL AFTER end_time");
        echo "Column 'last_heartbeat' added to time_sessions table.\n";
    } else {
        echo "Column 'last_heartbeat' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
