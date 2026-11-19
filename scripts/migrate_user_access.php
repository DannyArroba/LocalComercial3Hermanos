<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') !== '--apply' || !is_file($argv[2] ?? '') || filesize($argv[2]) < 1000) {
    fwrite(STDERR, "Uso: php scripts/migrate_user_access.php --apply RESPALDO_SQL_COMPLETO\n"); exit(1);
}
require __DIR__ . '/../backend/db.php';
$columns = [
    'users' => ['name' => 'VARCHAR(120) NULL', 'permissions_json' => 'TEXT NULL'],
    'purchases' => ['served_by_name' => 'VARCHAR(120) NULL'],
    'sales_returns' => ['handled_by_name' => 'VARCHAR(120) NULL'],
    'stock_purchases' => ['requested_by_name' => 'VARCHAR(120) NULL'],
];
$added = [];
foreach ($columns as $table => $fields) foreach ($fields as $field => $definition) {
    if (!$conn->query("SHOW COLUMNS FROM `$table` LIKE '$field'")->num_rows) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$field` $definition");
        $added[] = "$table.$field";
    }
}
$conn->query("UPDATE users SET role='cashier' WHERE role IS NULL OR role=''");
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin','cashier') NOT NULL DEFAULT 'cashier'");
echo json_encode(['status' => 'success', 'added_columns' => $added], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
