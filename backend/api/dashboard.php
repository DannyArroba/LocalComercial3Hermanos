<?php
session_start();
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5173';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

include('../db.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "No autenticado"]);
    exit();
}

// Get filter params
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$isAnnual = isset($_GET['is_annual']) && $_GET['is_annual'] === 'true';

if ($isAnnual) {
    // Annual mode for selected year
    $startDate = "$year-01-01 00:00:00";
    $endDate = "$year-12-31 23:59:59";
} else {
    // Monthly mode
    $startDate = "$year-$month-01 00:00:00";
    $endDate = date("Y-m-t 23:59:59", strtotime($startDate));
}

// Ventas totales hoy
$today = date('Y-m-d');
$resSales = $conn->query("SELECT SUM(total) as total FROM purchases WHERE DATE(created_at) = '$today'");
$salesToday = $resSales->fetch_assoc()['total'] ?? 0;
$resReturnsToday = $conn->query("SELECT SUM(total_refund) as total FROM sales_returns WHERE DATE(created_at) = '$today'");
$returnsToday = $resReturnsToday->fetch_assoc()['total'] ?? 0;
$salesToday = round($salesToday - $returnsToday, 2);

$resExpensesToday = $conn->query("SELECT SUM(total) as total FROM stock_purchases WHERE DATE(paid_at) = '$today' AND status IN ('PAGADO', 'RECIBIDO')");
$expensesToday = $resExpensesToday->fetch_assoc()['total'] ?? 0;

// Cantidad de productos con bajo stock (menos de 10) Y los detalles de los productos
$resLowStock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock < 10");
$lowStockCount = $resLowStock->fetch_assoc()['count'] ?? 0;

// Obtener productos con bajo stock (menos de 10)
$lowStockQuery = "
    SELECT id, name, image, price, stock 
    FROM products 
    WHERE stock < 10 
    ORDER BY stock ASC
";
$lowStockProducts = [];
$resLowStockProducts = $conn->query($lowStockQuery);
while ($row = $resLowStockProducts->fetch_assoc()) {
    $row['image_url'] = "http://localhost/TienditaNexar/frontend/uploads/" . $row['image'];
    $lowStockProducts[] = $row;
}

// Obtener productos proximos a quedarse sin stock (10-20)
$criticalStockQuery = "
    SELECT id, name, image, price, stock 
    FROM products 
    WHERE stock >= 10 AND stock <= 20 
    ORDER BY stock ASC
";
$criticalStockProducts = [];
$resCriticalStockProducts = $conn->query($criticalStockQuery);
while ($row = $resCriticalStockProducts->fetch_assoc()) {
    $row['image_url'] = "http://localhost/TienditaNexar/frontend/uploads/" . $row['image'];
    $criticalStockProducts[] = $row;
}

// Total productos
$resProducts = $conn->query("SELECT COUNT(*) as count FROM products");
$totalProducts = $resProducts->fetch_assoc()['count'] ?? 0;

// Últimas 5 ventas
$resRecentSales = $conn->query("SELECT * FROM purchases ORDER BY created_at DESC LIMIT 5");
$recentSales = [];
while ($row = $resRecentSales->fetch_assoc()) {
    $recentSales[] = $row;
}

// Top 5 productos más vendidos
$topProductsQuery = "
    SELECT 
        p.id,
        p.name,
        p.image,
        SUM(pi.quantity) as total_quantity,
        SUM(pi.line_total) as total_revenue
    FROM purchase_items pi
    JOIN products p ON pi.product_id = p.id
    JOIN purchases pur ON pi.purchase_id = pur.id
    GROUP BY p.id, p.name, p.image
    ORDER BY total_quantity DESC
    LIMIT 5
";
$topProducts = [];
$resTopProducts = $conn->query($topProductsQuery);
while ($row = $resTopProducts->fetch_assoc()) {
    $row['image_url'] = "http://localhost/TienditaNexar/frontend/uploads/" . $row['image'];
    $topProducts[] = $row;
}

// Resumen del periodo: ventas, abastecimientos y ganancia estimada.
$resMonthlyRevenue = $conn->query("SELECT SUM(total) as total FROM purchases WHERE created_at BETWEEN '$startDate' AND '$endDate'");
$monthlyRevenue = $resMonthlyRevenue->fetch_assoc()['total'] ?? 0;
$resMonthlyReturns = $conn->query("SELECT SUM(total_refund) as total FROM sales_returns WHERE created_at BETWEEN '$startDate' AND '$endDate'");
$monthlyReturns = $resMonthlyReturns->fetch_assoc()['total'] ?? 0;
$monthlyRevenue = round($monthlyRevenue - $monthlyReturns, 2);

// Salidas de dinero registradas al abastecer inventario.
$resMonthlyExpenses = $conn->query("SELECT SUM(total) as total FROM stock_purchases WHERE paid_at BETWEEN '$startDate' AND '$endDate' AND status IN ('PAGADO', 'RECIBIDO')");
$monthlyExpenses = $resMonthlyExpenses->fetch_assoc()['total'] ?? 0;

// Costo de la mercaderia vendida, guardado al momento de cada venta.
$resCostOfGoodsSold = $conn->query("
    SELECT SUM(pi.unit_cost * pi.quantity) as total_cost
    FROM purchase_items pi
    JOIN purchases pur ON pi.purchase_id = pur.id
    WHERE pur.created_at BETWEEN '$startDate' AND '$endDate'
");
$costOfGoodsSold = $resCostOfGoodsSold->fetch_assoc()['total_cost'] ?? 0;
$resReturnedCost = $conn->query("
    SELECT SUM(sri.unit_cost * sri.quantity) AS total_cost
    FROM sales_return_items sri
    JOIN sales_returns sr ON sr.id = sri.return_id
    WHERE sr.created_at BETWEEN '$startDate' AND '$endDate'
");
$returnedCost = $resReturnedCost->fetch_assoc()['total_cost'] ?? 0;
$costOfGoodsSold = round($costOfGoodsSold - $returnedCost, 2);

$monthlyNetProfit = round($monthlyRevenue - $costOfGoodsSold, 2);

echo json_encode([
    "status" => "success",
    "stats" => [
        "salesToday" => $salesToday,
        "expensesToday" => $expensesToday,
        "lowStockCount" => $lowStockCount,
        "totalProducts" => $totalProducts
    ],
    "recentSales" => $recentSales,
    "topProducts" => $topProducts,
    "lowStockProducts" => $lowStockProducts,
    "criticalStockProducts" => $criticalStockProducts,
    "monthlyStats" => [
        "year" => $year,
        "month" => $month,
        "isAnnual" => $isAnnual,
        "revenue" => $monthlyRevenue,
        "expenses" => $monthlyExpenses,
        "returns" => $monthlyReturns,
        "costOfGoodsSold" => $costOfGoodsSold,
        "netProfit" => $monthlyNetProfit
    ]
]);
?>
