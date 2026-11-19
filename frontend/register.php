<?php
session_start();
include('../backend/db.php');

if (isset($_POST['register'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        header("Location: register.php?err=match");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        header("Location: register.php?err=exists");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'cashier')");
    $stmt->bind_param("ss", $email, $hashed_password);

    if ($stmt->execute()) {
        header("Location: login.php?ok=registered");
        exit();
    } else {
        header("Location: register.php?err=server");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.2/dist/tailwind.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/alerts.js"></script>
</head>
<body class="bg-gray-200">

<div class="flex justify-center items-center h-screen">
    <div class="bg-white shadow-xl rounded-lg max-w-lg w-full p-8">
        <h2 class="text-2xl font-semibold text-center mb-6">Crear Cuenta</h2>

        <form action="register.php" method="POST" id="register-form">
            <div class="mb-4">
                <label for="email" class="block text-gray-700">Correo Electrónico</label>
                <input type="email" name="email" id="email"
                       class="w-full p-3 mt-2 border rounded-lg"
                       placeholder="Tu correo" required>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700">Contraseña</label>
                <input type="password" name="password" id="password"
                       class="w-full p-3 mt-2 border rounded-lg"
                       placeholder="Tu contraseña" required>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="block text-gray-700">Confirmar Contraseña</label>
                <input type="password" name="confirm_password" id="confirm_password"
                       class="w-full p-3 mt-2 border rounded-lg"
                       placeholder="Confirma tu contraseña" required>
            </div>

            <button type="submit" name="register"
                    class="w-full p-3 mt-4 bg-green-600 text-white font-semibold rounded-lg">
                Registrar
            </button>
        </form>

        <div class="mt-4 text-center">
            <p>¿Ya tienes cuenta? <a href="login.php" class="text-blue-600">Inicia sesión aquí</a></p>
        </div>
    </div>
</div>

<script>
  const params = new URLSearchParams(window.location.search);
  const err = params.get("err");

  if (err === "match") {
    Alerts.modalError("Error", "Las contraseñas no coinciden.");
  }
  if (err === "exists") {
    Alerts.modalError("Error", "Ese correo ya está registrado.");
  }
  if (err === "server") {
    Alerts.modalError("Error", "No se pudo registrar. Intenta más tarde.");
  }
</script>

</body>
</html>
