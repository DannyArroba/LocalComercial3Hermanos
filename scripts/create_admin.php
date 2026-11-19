<?php
// Bootstrap the first administrator locally. Never expose account creation through HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (in_array('--help', $argv, true)) {
    echo "Crear el primer administrador de una base nueva.\n";
    echo "Define APP_ADMIN_NAME, APP_ADMIN_EMAIL y APP_ADMIN_PASSWORD y ejecuta php scripts/create_admin.php.\n";
    echo "No crea ni modifica cuentas si ya existe un administrador.\n";
    exit;
}
$name = trim((string)getenv('APP_ADMIN_NAME'));
$email = trim((string)getenv('APP_ADMIN_EMAIL'));
$password = (string)getenv('APP_ADMIN_PASSWORD');
putenv('APP_ADMIN_PASSWORD');
unset($_ENV['APP_ADMIN_PASSWORD'], $_SERVER['APP_ADMIN_PASSWORD']);
if ($name === '' || mb_strlen($name) > 120) {
    fwrite(STDERR, "APP_ADMIN_NAME debe contener el nombre, de hasta 120 caracteres.\n"); exit(2);
}
if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen(explode('@', $email)[0]) < 4) {
    fwrite(STDERR, "APP_ADMIN_EMAIL debe ser un correo válido, con al menos cuatro caracteres antes de @.\n"); exit(2);
}
if (strlen($password) < 12 || strlen($password) > 72) {
    fwrite(STDERR, "APP_ADMIN_PASSWORD debe tener entre 12 y 72 bytes.\n"); exit(2);
}
require __DIR__ . '/../backend/db.php';
require __DIR__ . '/../backend/lib/access.php';
$lockName = 'tiendita-first-admin:' . substr(hash('sha256', $dbname), 0, 40);
$locked = false; $transaction = false; $exitCode = 0;
try {
    $stmt = $conn->prepare('SELECT GET_LOCK(?, 10)');
    $stmt->bind_param('s', $lockName); $stmt->execute();
    $locked = (int)$stmt->get_result()->fetch_row()[0] === 1;
    if (!$locked) throw new RuntimeException('No se pudo obtener el bloqueo de creación. Intenta nuevamente.');
    $conn->begin_transaction(); $transaction = true;
    if ((int)$conn->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetch_row()[0] > 0) {
        throw new RuntimeException('Ya existe un administrador. No se creó ni modificó ninguna cuenta.');
    }
    $stmt = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email); $stmt->execute();
    if ($stmt->get_result()->num_rows) throw new RuntimeException('El correo ya pertenece a una cuenta. No se modificó su rol.');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $permissions = json_encode(appPermissionKeys(), JSON_THROW_ON_ERROR);
    $role = 'admin';
    $stmt = $conn->prepare('INSERT INTO users(name,email,password,role,permissions_json) VALUES(?,?,?,?,?)');
    $stmt->bind_param('sssss', $name, $email, $hash, $role, $permissions); $stmt->execute();
    $conn->commit(); $transaction = false;
    echo "Primer administrador creado. Inicia sesión con el correo y la contraseña que proporcionaste.\n";
} catch (Throwable $error) {
    if ($transaction) $conn->rollback();
    $exitCode = 1;
    $message = $error instanceof mysqli_sql_exception
        ? 'No se pudo crear la cuenta. Verifica la configuración y que la base use el esquema actual.'
        : $error->getMessage();
    fwrite(STDERR, $message . "\n");
} finally {
    if ($locked) {
        $stmt = $conn->prepare('SELECT RELEASE_LOCK(?)'); $stmt->bind_param('s', $lockName); $stmt->execute();
    }
    unset($password, $hash);
}
exit($exitCode);
