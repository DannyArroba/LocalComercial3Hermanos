<?php
session_start();
include('../backend/db.php');

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$err = "";
$ok = "";

// Mensajes por URL (opcional)
if (isset($_GET['ok'])) $ok = $_GET['ok'];
if (isset($_GET['err'])) $err = $_GET['err'];

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            header("Location: index.php?ok=login");
            exit();
        } else {
            header("Location: login.php?err=pass");
            exit();
        }
    } else {
        header("Location: login.php?err=user");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.2/dist/tailwind.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/alerts.js"></script>
</head>
<body class="bg-gray-200">

<div class="flex justify-between h-screen">
    <div class="w-full md:w-1/2 flex justify-center items-center bg-white shadow-xl">
        <div class="w-full max-w-md p-8">
            <h2 class="text-2xl font-semibold text-center mb-6">Iniciar Sesión</h2>

            <form action="login.php" method="POST" id="login-form">
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

                <button type="submit" name="login"
                        class="w-full p-3 mt-4 bg-blue-600 text-white font-semibold rounded-lg">
                    Iniciar Sesión
                </button>
            </form>

            <div class="mt-4 text-center">
                <p>¿No tienes cuenta? <a href="register.php" class="text-blue-600">Regístrate aquí</a></p>
            </div>
        </div>
    </div>

    <div class="hidden md:block w-1/2 bg-cover bg-center" style="background-image: url('your-image-url.jpg');"></div>
</div>

<script>
  const params = new URLSearchParams(window.location.search);
  const err = params.get("err");
  const ok = params.get("ok");

  if (ok === "registered") {
    Alerts.modalSuccess("Registro exitoso ✅", "Ahora ya puedes iniciar sesión.");
  }

  if (err === "user") {
    Alerts.modalError("Usuario no encontrado", "Verifica tu correo e inténtalo de nuevo.");
  }
  if (err === "pass") {
    Alerts.modalError("Contraseña incorrecta", "Intenta nuevamente.");
  }
</script>

</body>
</html>
