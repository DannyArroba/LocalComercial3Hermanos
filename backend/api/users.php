<?php
require_once __DIR__ . '/../api-bootstrap.php';
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/access.php';
$actor = appRequirePermission($conn, [], true);

function userRespond(string $status, string $message, array $extra = [], int $code = 200): never {
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message] + $extra, JSON_UNESCAPED_UNICODE); exit;
}
function userRoleValue($value): string {
    if ($value === 'admin') return 'admin';
    if (in_array($value, ['cashier', 'trabajador'], true)) return 'cashier';
    throw new InvalidArgumentException('Selecciona un rol válido.');
}
function userPermissionValues($value): array {
    if (!is_array($value) || !array_is_list($value)) throw new InvalidArgumentException('Los accesos deben ser una lista.');
    foreach ($value as $key) if (!is_string($key) || !in_array($key, appPermissionKeys(), true)) throw new InvalidArgumentException('Uno de los accesos seleccionados no es válido.');
    return array_values(array_intersect(appPermissionKeys(), $value));
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $users = [];
    foreach ($conn->query('SELECT id,name,email,role,permissions_json,created_at FROM users ORDER BY id DESC')->fetch_all(MYSQLI_ASSOC) as $row) $users[] = appUserPayload($row);
    echo json_encode($users, JSON_UNESCAPED_UNICODE); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') userRespond('error', 'Método no permitido.', [], 405);
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) userRespond('error', 'Datos inválidos.', [], 400);
foreach (['name','email','password','role'] as $field) {
    if (isset($data[$field]) && !is_string($data[$field])) userRespond('error', 'Datos del usuario inválidos.', [], 400);
}
$action = $data['action'] ?? '';
if (!in_array($action, ['add', 'update', 'delete'], true)) userRespond('error', 'Acción inválida.', [], 400);
$transaction = false;
try {
    $id = (int)($data['id'] ?? 0);
    $conn->begin_transaction(); $transaction = true;
    $admins = $conn->query("SELECT id FROM users WHERE role='admin' ORDER BY id FOR UPDATE")->fetch_all(MYSQLI_ASSOC);
    $actorId = (int)$actor['id'];
    if (!in_array($actorId, array_map('intval', array_column($admins, 'id')), true)) throw new RuntimeException('Tus permisos cambiaron. Inicia sesión nuevamente.');
    $existing = null;
    if ($action !== 'add') {
        if ($id <= 0) throw new InvalidArgumentException('Usuario inválido.');
        $stmt = $conn->prepare('SELECT * FROM users WHERE id=? FOR UPDATE'); $stmt->bind_param('i', $id); $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if (!$existing) throw new InvalidArgumentException('El usuario ya no existe.');
    }
    if ($action === 'delete') {
        if ($id === $actorId) throw new InvalidArgumentException('No puedes eliminar tu propia cuenta.');
        if ($existing['role'] === 'admin' && count($admins) <= 1) throw new InvalidArgumentException('Debe permanecer al menos un administrador.');
        foreach (['purchases', 'stock_purchases', 'sales_returns', 'sales'] as $table) {
            $stmt = $conn->prepare("SELECT 1 FROM $table WHERE user_id=? LIMIT 1"); $stmt->bind_param('i', $id); $stmt->execute();
            if ($stmt->get_result()->num_rows) throw new InvalidArgumentException('Este usuario tiene operaciones registradas. Desactiva sus accesos para conservar la trazabilidad.');
        }
        $stmt = $conn->prepare('DELETE FROM users WHERE id=?'); $stmt->bind_param('i', $id); $stmt->execute();
        $conn->commit(); $transaction = false;
        userRespond('success', 'Usuario eliminado.');
    }
    $name = trim((string)($data['name'] ?? ''));
    $email = trim((string)($data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');
    $role = userRoleValue($data['role'] ?? ($action === 'add' ? 'cashier' : $existing['role']));
    if ($name === '' || mb_strlen($name) > 120) throw new InvalidArgumentException('Ingresa el nombre del trabajador, de hasta 120 caracteres.');
    if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Ingresa un correo electrónico válido.');
    if (strlen(explode('@', $email)[0]) < 4) throw new InvalidArgumentException('El nombre del correo debe tener al menos 4 caracteres.');
    if (($action === 'add' || $password !== '') && strlen($password) < 8) throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
    if (strlen($password) > 4096) throw new InvalidArgumentException('La contraseña es demasiado larga.');
    if ($action === 'update' && $id === $actorId && $role !== 'admin') throw new InvalidArgumentException('No puedes quitarte el rol de administrador.');
    if ($action === 'update' && $existing['role'] === 'admin' && $role !== 'admin' && count($admins) <= 1) throw new InvalidArgumentException('Debe permanecer al menos un administrador.');
    $permissions = array_key_exists('permissions', $data) ? userPermissionValues($data['permissions']) : ($existing ? appUserPayload($existing)['permissions'] : appDefaultPermissions());
    if ($role === 'admin') $permissions = appPermissionKeys();
    $json = json_encode($permissions, JSON_THROW_ON_ERROR);
    if ($action === 'add') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users(name,email,password,role,permissions_json) VALUES(?,?,?,?,?)');
        $stmt->bind_param('sssss', $name, $email, $hash, $role, $json); $stmt->execute(); $id = (int)$stmt->insert_id;
    } elseif ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET name=?,email=?,role=?,permissions_json=?,password=? WHERE id=?');
        $stmt->bind_param('sssssi', $name, $email, $role, $json, $hash, $id); $stmt->execute();
    } else {
        $stmt = $conn->prepare('UPDATE users SET name=?,email=?,role=?,permissions_json=? WHERE id=?');
        $stmt->bind_param('ssssi', $name, $email, $role, $json, $id); $stmt->execute();
    }
    $stmt = $conn->prepare('SELECT id,name,email,role,permissions_json,created_at FROM users WHERE id=?'); $stmt->bind_param('i', $id); $stmt->execute();
    $user = appUserPayload($stmt->get_result()->fetch_assoc());
    $conn->commit(); $transaction = false;
    userRespond('success', $action === 'add' ? 'Usuario registrado correctamente.' : 'Usuario actualizado.', ['user' => $user]);
} catch (Throwable $error) {
    if ($transaction) $conn->rollback();
    if ($error instanceof mysqli_sql_exception) {
        $message = match ($error->getCode()) {
            1062 => 'El correo ya está registrado.',
            1451 => 'El usuario tiene operaciones registradas. Desactiva sus accesos para conservar la trazabilidad.',
            default => 'No se pudo guardar el usuario. Intenta nuevamente.',
        };
        userRespond('error', $message, [], 400);
    }
    userRespond('error', $error->getMessage(), [], 400);
}
