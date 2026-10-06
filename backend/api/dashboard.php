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
date_default_timezone_set('America/Bogota');

function respond($payload) {
    echo json_encode($payload);
    exit();
}

function queryOrFail($conn, $sql) {
    $result = $conn->query($sql);
    if ($result === false) {
        respond([
            "status" => "error",
            "message" => "Error en consulta SQL: " . $conn->error,
        ]);
    }
    return $result;
}

function fetchScalar($conn, $sql, $field, $default = 0) {
    $result = queryOrFail($conn, $sql);
    $row = $result->fetch_assoc();
    return $row[$field] ?? $default;
}

function money($value) {
    return round((float)($value ?? 0), 2);
}

function imageUrl($image) {
    if (!$image) {
        return "";
    }
    return "http://127.0.0.1:8000/frontend/uploads/" . $image;
}

if (!isset($_SESSION['user_id'])) {
    respond(["status" => "error", "message" => "No autenticado"]);
}

$activityPeriods = [];
$periodsResult = queryOrFail($conn, "
    SELECT
        period,
        MAX(last_activity) AS last_activity,
        SUM(sales_count) AS sales_count,
        SUM(supply_count) AS supply_count,
        SUM(return_count) AS return_count
    FROM (
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, MAX(created_at) AS last_activity,
               COUNT(*) AS sales_count, 0 AS supply_count, 0 AS return_count
        FROM purchases
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')

        UNION ALL

        SELECT DATE_FORMAT(COALESCE(paid_at, received_at, created_at), '%Y-%m') AS period,
               MAX(COALESCE(paid_at, received_at, created_at)) AS last_activity,
               0 AS sales_count, COUNT(*) AS supply_count, 0 AS return_count
        FROM stock_purchases
        GROUP BY DATE_FORMAT(COALESCE(paid_at, received_at, created_at), '%Y-%m')

        UNION ALL

        SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, MAX(created_at) AS last_activity,
               0 AS sales_count, 0 AS supply_count, COUNT(*) AS return_count
        FROM sales_returns
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ) activity
    WHERE period IS NOT NULL
    GROUP BY period
    ORDER BY period DESC
");

while ($row = $periodsResult->fetch_assoc()) {
    $activityPeriods[] = [
        "period" => $row["period"],
        "year" => (int)substr($row["period"], 0, 4),
        "month" => (int)substr($row["period"], 5, 2),
        "lastActivity" => $row["last_activity"],
        "salesCount" => (int)$row["sales_count"],
        "supplyCount" => (int)$row["supply_count"],
        "returnCount" => (int)$row["return_count"],
    ];
}

$latestPeriod = $activityPeriods[0] ?? [
    "year" => (int)date('Y'),
    "month" => (int)date('n'),
    "period" => date('Y-m'),
];

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$isAnnual = ($_GET['is_annual'] ?? '') === 'true';

if (!$year) {
    $year = (int)$latestPeriod["year"];
}

if (!$month) {
    $month = (int)$latestPeriod["month"];
}
$month = max(1, min(12, $month));

if ($isAnnual) {
    $startDate = sprintf('%04d-01-01 00:00:00', $year);
    $endDate = sprintf('%04d-12-31 23:59:59', $year);
} else {
    $startDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $endDate = date("Y-m-t 23:59:59", strtotime($startDate));
}

$periodKey = $isAnnual ? (string)$year : sprintf('%04d-%02d', $year, $month);
$hasActivity = false;
foreach ($activityPeriods as $period) {
    if (($isAnnual && $period["year"] === (int)$year) || (!$isAnnual && $period["period"] === $periodKey)) {
        $hasActivity = true;
        break;
    }
}

$today = date('Y-m-d');
$salesToday = money(fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total FROM purchases WHERE DATE(created_at) = '$today'",
    "total"
) - fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(total_refund), 0) AS total FROM sales_returns WHERE DATE(created_at) = '$today'",
    "total"
));

$expensesToday = money(fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM stock_purchases
     WHERE DATE(COALESCE(paid_at, received_at, created_at)) = '$today'
       AND status IN ('PAGADO', 'RECIBIDO')",
    "total"
));

$lowStockCount = fetchScalar($conn, "SELECT COUNT(*) AS count FROM products WHERE stock < 10", "count");
$totalProducts = fetchScalar($conn, "SELECT COUNT(*) AS count FROM products", "count");

$recentSales = [];
$resRecentSales = queryOrFail($conn, "SELECT * FROM purchases ORDER BY created_at DESC LIMIT 5");
while ($row = $resRecentSales->fetch_assoc()) {
    $recentSales[] = $row;
}

$topProducts = [];
$resTopProducts = queryOrFail($conn, "
    SELECT
        p.id,
        p.name,
        p.image,
        SUM(pi.quantity - COALESCE(returns.returned_quantity, 0)) AS total_quantity,
        SUM(pi.line_total - COALESCE(returns.returned_total, 0)) AS total_revenue
    FROM purchase_items pi
    JOIN products p ON pi.product_id = p.id
    JOIN purchases pur ON pi.purchase_id = pur.id
    LEFT JOIN (
        SELECT purchase_item_id, SUM(quantity) AS returned_quantity, SUM(line_total) AS returned_total
        FROM sales_return_items
        GROUP BY purchase_item_id
    ) returns ON returns.purchase_item_id = pi.id
    WHERE pur.created_at BETWEEN '$startDate' AND '$endDate'
    GROUP BY p.id, p.name, p.image
    HAVING total_quantity > 0
    ORDER BY total_quantity DESC, total_revenue DESC
    LIMIT 5
");
while ($row = $resTopProducts->fetch_assoc()) {
    $row['image_url'] = imageUrl($row['image'] ?? '');
    $topProducts[] = $row;
}

$lowStockProducts = [];
$resLowStockProducts = queryOrFail($conn, "
    SELECT id, name, image, price, stock
    FROM products
    WHERE stock < 10
    ORDER BY stock ASC, name ASC
");
while ($row = $resLowStockProducts->fetch_assoc()) {
    $row['image_url'] = imageUrl($row['image'] ?? '');
    $lowStockProducts[] = $row;
}

$criticalStockProducts = [];
$resCriticalStockProducts = queryOrFail($conn, "
    SELECT id, name, image, price, stock
    FROM products
    WHERE stock >= 10 AND stock <= 20
    ORDER BY stock ASC, name ASC
");
while ($row = $resCriticalStockProducts->fetch_assoc()) {
    $row['image_url'] = imageUrl($row['image'] ?? '');
    $criticalStockProducts[] = $row;
}

$grossRevenue = money(fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total FROM purchases WHERE created_at BETWEEN '$startDate' AND '$endDate'",
    "total"
));

$returnsTotal = money(fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(total_refund), 0) AS total FROM sales_returns WHERE created_at BETWEEN '$startDate' AND '$endDate'",
    "total"
));

$netRevenue = money($grossRevenue - $returnsTotal);

$salesCount = (int)fetchScalar(
    $conn,
    "SELECT COUNT(*) AS count FROM purchases WHERE created_at BETWEEN '$startDate' AND '$endDate'",
    "count"
);

$returnsCount = (int)fetchScalar(
    $conn,
    "SELECT COUNT(*) AS count FROM sales_returns WHERE created_at BETWEEN '$startDate' AND '$endDate'",
    "count"
);

$supplyDateExpr = "COALESCE(paid_at, received_at, created_at)";
$supplyExpenses = money(fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM stock_purchases
     WHERE $supplyDateExpr BETWEEN '$startDate' AND '$endDate'
       AND status IN ('PAGADO', 'RECIBIDO')",
    "total"
));

$pendingSupplyTotal = money(fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM stock_purchases
     WHERE created_at BETWEEN '$startDate' AND '$endDate'
       AND status = 'SOLICITADO'",
    "total"
));

$refundedSupplyTotal = money(fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(total), 0) AS total
     FROM stock_purchases
     WHERE COALESCE(refunded_at, $supplyDateExpr) BETWEEN '$startDate' AND '$endDate'
       AND status = 'REEMBOLSADO'",
    "total"
));

$supplyCount = (int)fetchScalar(
    $conn,
    "SELECT COUNT(*) AS count
     FROM stock_purchases
     WHERE $supplyDateExpr BETWEEN '$startDate' AND '$endDate'",
    "count"
);

$paidSupplyCount = (int)fetchScalar(
    $conn,
    "SELECT COUNT(*) AS count
     FROM stock_purchases
     WHERE $supplyDateExpr BETWEEN '$startDate' AND '$endDate'
       AND status IN ('PAGADO', 'RECIBIDO')",
    "count"
);

$costOfGoodsSold = money(fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(pi.unit_cost * pi.quantity), 0) AS total_cost
     FROM purchase_items pi
     JOIN purchases pur ON pi.purchase_id = pur.id
     WHERE pur.created_at BETWEEN '$startDate' AND '$endDate'",
    "total_cost"
));

$returnedCost = money(fetchScalar(
    $conn,
    "SELECT COALESCE(SUM(sri.unit_cost * sri.quantity), 0) AS total_cost
     FROM sales_return_items sri
     JOIN sales_returns sr ON sr.id = sri.return_id
     WHERE sr.created_at BETWEEN '$startDate' AND '$endDate'",
    "total_cost"
));

$costOfGoodsSold = money($costOfGoodsSold - $returnedCost);
$netProfit = money($netRevenue - $costOfGoodsSold);
$cashBalance = money($netRevenue - $supplyExpenses);

$supplierExpenses = [];
$supplierResult = queryOrFail($conn, "
    SELECT
        COALESCE(s.company_name, 'Proveedor no registrado') AS supplier_name,
        COUNT(sp.id) AS purchases_count,
        COALESCE(SUM(sp.total), 0) AS total
    FROM stock_purchases sp
    LEFT JOIN suppliers s ON s.id = sp.supplier_id
    WHERE COALESCE(sp.paid_at, sp.received_at, sp.created_at) BETWEEN '$startDate' AND '$endDate'
      AND sp.status IN ('PAGADO', 'RECIBIDO')
    GROUP BY COALESCE(s.company_name, 'Proveedor no registrado')
    ORDER BY total DESC
    LIMIT 5
");
while ($row = $supplierResult->fetch_assoc()) {
    $row["total"] = money($row["total"]);
    $row["purchases_count"] = (int)$row["purchases_count"];
    $supplierExpenses[] = $row;
}

$availableYears = [];
foreach ($activityPeriods as $period) {
    if (!in_array($period["year"], $availableYears, true)) {
        $availableYears[] = $period["year"];
    }
}
if (!in_array((int)$year, $availableYears, true)) {
    $availableYears[] = (int)$year;
    rsort($availableYears);
}

respond([
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
    "availablePeriods" => $activityPeriods,
    "availableYears" => $availableYears,
    "suggestedPeriod" => [
        "year" => (int)$latestPeriod["year"],
        "month" => (int)$latestPeriod["month"],
        "period" => $latestPeriod["period"],
    ],
    "supplierExpenses" => $supplierExpenses,
    "monthlyStats" => [
        "year" => (int)$year,
        "month" => (int)$month,
        "isAnnual" => $isAnnual,
        "startDate" => $startDate,
        "endDate" => $endDate,
        "hasActivity" => $hasActivity,
        "salesCount" => $salesCount,
        "returnsCount" => $returnsCount,
        "supplyCount" => $supplyCount,
        "paidSupplyCount" => $paidSupplyCount,
        "grossRevenue" => $grossRevenue,
        "returns" => $returnsTotal,
        "revenue" => $netRevenue,
        "expenses" => $supplyExpenses,
        "pendingSupplyTotal" => $pendingSupplyTotal,
        "refundedSupplyTotal" => $refundedSupplyTotal,
        "costOfGoodsSold" => $costOfGoodsSold,
        "netProfit" => $netProfit,
        "cashBalance" => $cashBalance
    ]
]);
?>
