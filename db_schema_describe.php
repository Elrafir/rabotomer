<?php
define('BASEPATH', true);
define('ENVIRONMENT', 'development');
require 'application/config/database.php';
$pdo = new PDO("mysql:host={$db['default']['hostname']};dbname={$db['default']['database']};charset={$db['default']['char_set']}", $db['default']['username'], $db['default']['password']);

echo "=== TASKS TABLE ===\n";
$stmt = $pdo->query("DESCRIBE tasks");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== TIME_SESSIONS TABLE ===\n";
$stmt = $pdo->query("DESCRIBE time_sessions");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
