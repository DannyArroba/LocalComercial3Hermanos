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

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

if ($method === 'POST') {
    $action = $data['action'] ?? '';

    if ($action === 'register') {
        $email = $conn->real_escape_string($data['email']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (email, password) VALUES ('$email', '$password')";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "message" => "Usuario registrado correctamente"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error: " . $conn->error]);
        }
    } elseif ($action === 'login') {
        $email = $conn->real_escape_string($data['email']);
        $password = $data['password'];

        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role']; // Guardar rol en sesión
                echo json_encode([
                    "status" => "success", 
                    "message" => "Login exitoso",
                    "user" => [
                        "id" => $user['id'], 
                        "email" => $user['email'],
                        "role" => $user['role']
                    ]
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Contraseña incorrecta"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
        }
    } elseif ($action === 'logout') {
        session_destroy();
        echo json_encode(["status" => "success", "message" => "Sesión cerrada"]);
    } elseif ($action === 'check') {
        if (isset($_SESSION['user_id'])) {
            $id = $_SESSION['user_id'];
            $sql = "SELECT id, email, role FROM users WHERE id=$id";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                echo json_encode(["status" => "success", "user" => $res->fetch_assoc()]);
            } else {
                echo json_encode(["status" => "error"]);
            }
        } else {
            echo json_encode(["status" => "error"]);
        }
    }
}
?>
