<?php
session_start();
include('../backend/db.php');
require_once __DIR__ . '/../backend/lib/access.php';
appRequirePermission($conn, 'sales');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Si no está autenticado, redirige al login
    exit();
}

// Agregar producto al carrito
if (isset($_GET['product_id'])) {
    $product_id = $_GET['product_id'];
    $user_id = $_SESSION['user_id'];

    // Verificar si el producto ya está en el carrito
    $check_sql = "SELECT * FROM carts WHERE user_id = '$user_id' AND product_id = '$product_id'";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        // Si el producto ya está en el carrito, actualizar la cantidad
        $update_sql = "UPDATE carts SET quantity = quantity + 1 WHERE user_id = '$user_id' AND product_id = '$product_id'";
        $conn->query($update_sql);
    } else {
        // Si el producto no está en el carrito, agregarlo
        $insert_sql = "INSERT INTO carts (user_id, product_id, quantity) VALUES ('$user_id', '$product_id', 1)";
        $conn->query($insert_sql);
    }

    echo "Producto agregado al carrito. <a href='cart.php'>Ver carrito</a>";
}
?>
