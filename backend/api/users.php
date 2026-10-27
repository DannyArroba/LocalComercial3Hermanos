<?php
require_once __DIR__ . '/../api-bootstrap.php';
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../db.php';

// Solo admin puede gestionar usuarios
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "No autenticado"]);
    exit();
}

$userRole = $_SESSION['role'] ?? '';

if ($userRole !== 'admin') {
    echo json_encode(["status" => "error", "message" => "No tienes permisos de administrador. Tu rol es: " . $userRole]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

if ($method === 'GET') {
    $sql = "SELECT id, email, role, created_at FROM users ORDER BY id DESC";
    $result = $conn->query($sql);
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode($users);
} elseif ($method === 'POST') {
    $action = $data['action'] ?? '';

    if ($action === 'add') {
        $email = $conn->real_escape_string($data['email']);
        $password_raw = $data['password'] ?? '';
        $role = $conn->real_escape_string($data['role'] ?? 'trabajador');

        // VALIDACIONES
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => "error", "message" => "Formato de correo inválido"]);
            exit();
        }

        if (strlen($password_raw) < 8) {
            echo json_encode(["status" => "error", "message" => "La contraseña debe tener al menos 8 caracteres"]);
            exit();
        }

        $email_parts = explode('@', $email);
        if (strlen($email_parts[0]) < 4) {
            echo json_encode(["status" => "error", "message" => "El nombre de usuario debe tener al menos 4 caracteres"]);
            exit();
        }

        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        // Verificar si el email ya existe
        $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
        if ($check->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "El correo ya está registrado"]);
            exit();
        }

        $sql = "INSERT INTO users (email, password, role) VALUES ('$email', '$password', '$role')";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Usuario registrado correctamente"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
        }
    } elseif ($action === 'update') {
        $id = (int)$data['id'];
        $email = $conn->real_escape_string($data['email']);
        $role = $conn->real_escape_string($data['role']);
        
        // VALIDACIONES
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["status" => "error", "message" => "Formato de correo inválido"]);
            exit();
        }

        $passwordSql = "";
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                echo json_encode(["status" => "error", "message" => "La nueva contraseña debe tener al menos 8 caracteres"]);
                exit();
            }
            $pass = password_hash($data['password'], PASSWORD_DEFAULT);
            $passwordSql = ", password='$pass'";
        }

        $email_parts = explode('@', $email);
        if (strlen($email_parts[0]) < 4) {
            echo json_encode(["status" => "error", "message" => "El nombre de usuario debe tener al menos 4 caracteres"]);
            exit();
        }

        $sql = "UPDATE users SET email='$email', role='$role' $passwordSql WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Usuario actualizado"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    } elseif ($action === 'delete') {
        $id = (int)$data['id'];
        if ($id == $admin_id) {
            echo json_encode(["status" => "error", "message" => "No puedes eliminarte a ti mismo"]);
            exit();
        }
        if ($conn->query("DELETE FROM users WHERE id=$id") === TRUE) {
            echo json_encode(["status" => "success", "message" => "Usuario eliminado"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    }
}
?>
