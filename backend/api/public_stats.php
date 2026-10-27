<?php
require_once __DIR__ . '/../api-bootstrap.php';
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../db.php';

// Estadísticas para la Home (Públicas o no requieren login estricto para mostrar impacto)

// 1. Total de Ventas Históricas (Simulando gran impacto para marketing) o Reales
$resSales = $conn->query("SELECT COUNT(*) as count FROM purchases");
$totalSales = $resSales->fetch_assoc()['count'] ?? 0;

// 2. Total de Productos en el catálogo
$resProducts = $conn->query("SELECT COUNT(*) as count FROM products");
$totalProducts = $resProducts->fetch_assoc()['count'] ?? 0;

// 3. Total de Usuarios (Trabajadores) registrados
$resUsers = $conn->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $resUsers->fetch_assoc()['count'] ?? 0;

echo json_encode([
    "status" => "success",
    "data" => [
        "totalSales" => $totalSales,
        "totalProducts" => $totalProducts,
        "totalUsers" => $totalUsers
    ]
]);
?>