<?php
session_start();
include('../backend/db.php');
require_once __DIR__ . '/lib/access.php';
appRequirePermission($conn, 'sales');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Si no está autenticado, redirige al login
    exit();
}

// Obtener los productos del carrito del usuario
$user_id = $_SESSION['user_id'];
$sql = "SELECT c.quantity, p.name, p.price 
        FROM carts c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = '$user_id'";
$result = $conn->query($sql);

$total = 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="index.php">Inventario</a></li>
                <li><a href="cart.php">Carrito</a></li>
                <li><a href="logout.php">Salir</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2>Carrito de Compras</h2>

        <table>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio</th>
                <th>Total</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td>$<?php echo $row['price']; ?></td>
                <td>$<?php echo $row['quantity'] * $row['price']; ?></td>
            </tr>
            <?php 
            $total += $row['quantity'] * $row['price'];
            } 
            ?>
        </table>

        <h3>Total: $<?php echo $total; ?></h3>
        <a href="generateInvoice.php">Generar Factura</a>
    </main>
</body>
</html>
