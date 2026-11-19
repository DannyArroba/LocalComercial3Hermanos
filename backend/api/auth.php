<?php
require_once __DIR__ . '/../api-bootstrap.php';
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/access.php';
function authRespond(string $status, string $message = '', array $extra = [], int $code = 200): never {
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message] + $extra, JSON_UNESCAPED_UNICODE); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') authRespond('error', 'Método no permitido.', [], 405);
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) authRespond('error', 'Datos inválidos.', [], 400);
foreach (['name','email','password'] as $field) {
    if (isset($data[$field]) && !is_string($data[$field])) authRespond('error', 'Datos inválidos.', [], 400);
}
$action = $data['action'] ?? '';
if ($action === 'check') {
    $user = appCurrentUser($conn);
    if (!$user) authRespond('error', 'No autenticado.');
    $_SESSION['role'] = $user['role'];
    authRespond('success', '', ['user' => $user]);
}
if ($action === 'logout') { $_SESSION = []; session_destroy(); authRespond('success', 'Sesión cerrada.'); }
try {
    $email = trim((string)($data['email'] ?? '')); $password = (string)($data['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) authRespond('error', 'Ingresa un correo válido.', [], 400);
    if ($action === 'login') {
        $stmt = $conn->prepare('SELECT * FROM users WHERE email=?'); $stmt->bind_param('s', $email); $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || !password_verify($password, $row['password'])) authRespond('error', 'Correo o contraseña incorrectos.');
        session_regenerate_id(true); $user = appUserPayload($row);
        $_SESSION['user_id'] = $user['id']; $_SESSION['role'] = $user['role'];
        authRespond('success', 'Login exitoso.', ['user' => $user]);
    }
    if ($action === 'register') {
        if (strlen($password) < 8 || strlen($password) > 4096) authRespond('error', 'La contraseña debe tener al menos 8 caracteres.', [], 400);
        $name = trim((string)($data['name'] ?? '')); if ($name === '') $name = explode('@', $email)[0];
        if (mb_strlen($name) > 120) authRespond('error', 'El nombre es demasiado largo.', [], 400);
        $hash = password_hash($password, PASSWORD_DEFAULT); $role = 'cashier'; $permissions = json_encode(appDefaultPermissions(), JSON_THROW_ON_ERROR);
        // Registration never accepts caller-selected roles or access.
        $stmt = $conn->prepare('INSERT INTO users(name,email,password,role,permissions_json) VALUES(?,?,?,?,?)');
        $stmt->bind_param('sssss', $name, $email, $hash, $role, $permissions); $stmt->execute();
        authRespond('success', 'Usuario registrado correctamente.');
    }
    authRespond('error', 'Acción inválida.', [], 400);
} catch (mysqli_sql_exception $error) {
    authRespond('error', $error->getCode() === 1062 ? 'El correo ya está registrado.' : 'No se pudo completar la solicitud.', [], 400);
}
