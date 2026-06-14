<?php
header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=time_tracker;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- TASK OWNERS & CUSTOMERS ---\n";
    $stmt = $pdo->query("SELECT id, user_id, parent_id, customer_id, title FROM tasks WHERE deleted_at IS NULL");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
