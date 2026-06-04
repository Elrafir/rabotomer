<?php
define('BASEPATH', true);
define('ENVIRONMENT', 'development');
require 'application/config/database.php';
$pdo = new PDO("mysql:host={$db['default']['hostname']};dbname={$db['default']['database']};charset={$db['default']['char_set']}", $db['default']['username'], $db['default']['password']);

$stmt = $pdo->query("SELECT * FROM time_sessions WHERE task_id IN (5, 6, 7)");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($sessions);
