<?php
/**
 * Migration script to add UUID and sync metadata to time_tracker database.
 * Safe and non-destructive: keeps all existing IDs and data intact.
 */

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'time_tracker';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "Connected to MySQL ($dbname)\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

function addColumnIfNotExists($pdo, $table, $column, $definition) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "  [+] Added `$column` to `$table`\n";
    } else {
        echo "  [=] Column `$column` already exists in `$table`\n";
    }
}

function addIndexIfNotExists($pdo, $table, $indexName, $columns) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $stmt->execute([$table, $indexName]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ($columns)");
        echo "  [+] Added index `$indexName` on `$table` ($columns)\n";
    } else {
        echo "  [=] Index `$indexName` already exists on `$table`\n";
    }
}

function addUniqueIndexIfNotExists($pdo, $table, $indexName, $columns) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $stmt->execute([$table, $indexName]);
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE `$table` ADD UNIQUE INDEX `$indexName` ($columns)");
        echo "  [+] Added unique index `$indexName` on `$table` ($columns)\n";
    } else {
        echo "  [=] Unique index `$indexName` already exists on `$table`\n";
    }
}

echo "\n--- 1. Updating Schema ---\n";

// USERS
echo "Updating table `users`:\n";
addColumnIfNotExists($pdo, 'users', 'uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'users', 'updated_at', 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
addColumnIfNotExists($pdo, 'users', 'deleted_at', 'DATETIME NULL');

// CUSTOMERS
echo "Updating table `customers`:\n";
addColumnIfNotExists($pdo, 'customers', 'uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'customers', 'user_uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'customers', 'updated_at', 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
addColumnIfNotExists($pdo, 'customers', 'deleted_at', 'DATETIME NULL');

// TASKS
echo "Updating table `tasks`:\n";
addColumnIfNotExists($pdo, 'tasks', 'uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'tasks', 'user_uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'tasks', 'customer_uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'tasks', 'parent_uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'tasks', 'updated_at', 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

// TIME_SESSIONS
echo "Updating table `time_sessions`:\n";
addColumnIfNotExists($pdo, 'time_sessions', 'uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'time_sessions', 'user_uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'time_sessions', 'task_uuid', 'VARCHAR(36) NULL');
addColumnIfNotExists($pdo, 'time_sessions', 'updated_at', 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
addColumnIfNotExists($pdo, 'time_sessions', 'deleted_at', 'DATETIME NULL');

echo "\n--- 2. Backfilling UUIDs for Existing Records ---\n";

function generateUUIDv4() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Backfill users
$stmt = $pdo->query("SELECT id FROM users WHERE uuid IS NULL OR uuid = ''");
$rows = $stmt->fetchAll();
if (!empty($rows)) {
    $up = $pdo->prepare("UPDATE users SET uuid = ? WHERE id = ?");
    foreach ($rows as $r) {
        $up->execute([generateUUIDv4(), $r['id']]);
    }
    echo "  [Users] Generated UUIDs for " . count($rows) . " users.\n";
} else {
    echo "  [Users] All users already have UUIDs.\n";
}

// Backfill customers
$stmt = $pdo->query("SELECT c.id, u.uuid as user_uuid FROM customers c LEFT JOIN users u ON c.user_id = u.id WHERE c.uuid IS NULL OR c.uuid = ''");
$rows = $stmt->fetchAll();
if (!empty($rows)) {
    $up = $pdo->prepare("UPDATE customers SET uuid = ?, user_uuid = COALESCE(user_uuid, ?) WHERE id = ?");
    foreach ($rows as $r) {
        $up->execute([generateUUIDv4(), $r['user_uuid'], $r['id']]);
    }
    echo "  [Customers] Generated UUIDs for " . count($rows) . " customers.\n";
} else {
    echo "  [Customers] All customers already have UUIDs.\n";
}

// Backfill tasks
$stmt = $pdo->query("SELECT t.id, u.uuid as user_uuid, c.uuid as customer_uuid FROM tasks t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN customers c ON t.customer_id = c.id WHERE t.uuid IS NULL OR t.uuid = ''");
$rows = $stmt->fetchAll();
if (!empty($rows)) {
    $up = $pdo->prepare("UPDATE tasks SET uuid = ?, user_uuid = COALESCE(user_uuid, ?), customer_uuid = COALESCE(customer_uuid, ?) WHERE id = ?");
    foreach ($rows as $r) {
        $up->execute([generateUUIDv4(), $r['user_uuid'], $r['customer_uuid'], $r['id']]);
    }
    echo "  [Tasks] Generated UUIDs for " . count($rows) . " tasks.\n";
} else {
    echo "  [Tasks] All tasks already have UUIDs.\n";
}

// Backfill parent_uuid for tasks
$pdo->exec("UPDATE tasks child JOIN tasks parent ON child.parent_id = parent.id SET child.parent_uuid = parent.uuid WHERE child.parent_id IS NOT NULL AND (child.parent_uuid IS NULL OR child.parent_uuid = '')");
echo "  [Tasks] Synchronized parent_uuid links.\n";

// Backfill time_sessions
$stmt = $pdo->query("SELECT s.id, u.uuid as user_uuid, t.uuid as task_uuid FROM time_sessions s LEFT JOIN users u ON s.user_id = u.id LEFT JOIN tasks t ON s.task_id = t.id WHERE s.uuid IS NULL OR s.uuid = ''");
$rows = $stmt->fetchAll();
if (!empty($rows)) {
    $up = $pdo->prepare("UPDATE time_sessions SET uuid = ?, user_uuid = COALESCE(user_uuid, ?), task_uuid = COALESCE(task_uuid, ?) WHERE id = ?");
    foreach ($rows as $r) {
        $up->execute([generateUUIDv4(), $r['user_uuid'], $r['task_uuid'], $r['id']]);
    }
    echo "  [Time Sessions] Generated UUIDs for " . count($rows) . " sessions.\n";
} else {
    echo "  [Time Sessions] All time sessions already have UUIDs.\n";
}

echo "\n--- 3. Adding Unique and Search Indexes ---\n";
addUniqueIndexIfNotExists($pdo, 'users', 'uq_users_uuid', '`uuid`');
addUniqueIndexIfNotExists($pdo, 'customers', 'uq_customers_uuid', '`uuid`');
addUniqueIndexIfNotExists($pdo, 'tasks', 'uq_tasks_uuid', '`uuid`');
addUniqueIndexIfNotExists($pdo, 'time_sessions', 'uq_time_sessions_uuid', '`uuid`');

addIndexIfNotExists($pdo, 'customers', 'idx_customers_updated', '`updated_at`');
addIndexIfNotExists($pdo, 'tasks', 'idx_tasks_updated', '`updated_at`');
addIndexIfNotExists($pdo, 'tasks', 'idx_tasks_customer_uuid', '`customer_uuid`');
addIndexIfNotExists($pdo, 'time_sessions', 'idx_sessions_updated', '`updated_at`');
addIndexIfNotExists($pdo, 'time_sessions', 'idx_sessions_task_uuid', '`task_uuid`');

echo "\n Migration completed successfully!\n";
