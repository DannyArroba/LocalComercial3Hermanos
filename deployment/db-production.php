<?php
$configPath = __DIR__ . '/config.php';
$config = is_file($configPath) ? require $configPath : [];
$host = $config['host'] ?? (getenv('DB_HOST') ?: 'localhost');
$user = $config['user'] ?? (getenv('DB_USER') ?: 'root');
$password = $config['password'] ?? (getenv('DB_PASSWORD') ?: '');
$dbname = $config['database'] ?? (getenv('DB_NAME') ?: 'tienda');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $password, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    error_log('Database connection failed: ' . $error->getMessage());
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    exit(json_encode(['status' => 'error', 'message' => 'No se pudo conectar a la base de datos. Revisa backend/config.php.']));
}
