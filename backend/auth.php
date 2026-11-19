<?php
session_start();
include('../backend/db.php');

// Registro de usuario
if (isset($_POST['register'])) {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'cashier')");
    $stmt->bind_param("ss", $email, $password);
    if ($stmt->execute()) {
        echo "Usuario registrado correctamente";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Login de usuario
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            header("Location: ../frontend/index.php"); // Redirige a la página principal
        } else {
            echo "Contraseña incorrecta";
        }
    } else {
        echo "Usuario no encontrado";
    }
}
?>
