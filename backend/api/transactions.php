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
require_once __DIR__ . '/../lib/access.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "No autenticado"]);
    exit();
}

$action = $_GET['action'] ?? 'list';
$actor = appRequirePermission($conn, $action === 'details' ? ['dashboard', 'transactions', 'sales'] : 'transactions');
$canSeeAll = $actor['role'] === 'admin' || in_array('dashboard', $actor['permissions'], true) || in_array('transactions', $actor['permissions'], true);

if ($action === 'list') {
    $transactions = [];
    $salesResult = $conn->query("
        SELECT p.id, p.purchase_type, p.customer_name, p.customer_idnumber, p.customer_phone,
               p.total, p.created_at,
               COALESCE(NULLIF(p.served_by_name, ''), NULLIF(u.name, ''), NULLIF(SUBSTRING_INDEX(u.email, '@', 1), ''), 'Usuario no disponible') AS served_by_name,
               CASE
                   WHEN p.purchase_type = 'FACTURA' THEN COALESCE(c.name, p.customer_name)
                   ELSE NULL
               END AS display_customer_name
        FROM purchases p
        LEFT JOIN customers c ON c.id = p.customer_id
        LEFT JOIN users u ON u.id = p.user_id
    ");
    while ($row = $salesResult->fetch_assoc()) {
        $row['record_key'] = 'V-' . $row['id'];
        $row['record_type'] = 'VENTA';
        $row['original_purchase_id'] = null;
        $row['return_code'] = null;
        $transactions[] = $row;
    }

    $returnsResult = $conn->query("SELECT sr.*, COALESCE(NULLIF(sr.handled_by_name, ''), NULLIF(u.name, ''), NULLIF(SUBSTRING_INDEX(u.email, '@', 1), ''), 'Usuario no disponible') AS served_by_name FROM sales_returns sr LEFT JOIN users u ON u.id = sr.user_id");
    while ($row = $returnsResult->fetch_assoc()) {
        $transactions[] = [
            'record_key' => 'D-' . $row['id'],
            'record_type' => 'DEVOLUCION',
            'id' => $row['id'],
            'purchase_type' => 'DEVOLUCION',
            'customer_name' => $row['customer_name'],
            'customer_idnumber' => $row['customer_idnumber'],
            'customer_phone' => $row['customer_phone'],
            'total' => -((float)$row['total_refund']),
            'created_at' => $row['created_at'],
            'display_customer_name' => $row['customer_name'],
            'original_purchase_id' => $row['purchase_id'],
            'return_code' => $row['return_code'],
            'served_by_name' => $row['served_by_name']
        ];
    }

    usort($transactions, function ($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
    echo json_encode(["status" => "success", "data" => $transactions]);
} elseif ($action === 'details') {
    $purchase_id = (int)($_GET['id'] ?? 0);
    $recordType = $_GET['type'] ?? 'VENTA';
    if (!$purchase_id) {
        echo json_encode(["status" => "error", "message" => "ID de compra no proporcionado"]);
        exit();
    }

    if ($recordType === 'DEVOLUCION') {
        if (!$canSeeAll) {
            http_response_code(403);
            exit(json_encode(['status' => 'error', 'message' => 'No tienes acceso a este comprobante.']));
        }
        $returnStmt = $conn->prepare("
            SELECT sr.id, sr.return_code, sr.purchase_id AS original_purchase_id,
                   'DEVOLUCION' AS purchase_type, 'REEMBOLSO' AS payment_method,
                   sr.customer_name, sr.customer_idnumber, sr.customer_phone,
                   sr.reason_category, sr.reason_detail, sr.created_at,
                   COALESCE(NULLIF(sr.handled_by_name, ''), NULLIF(u.name, ''), NULLIF(SUBSTRING_INDEX(u.email, '@', 1), ''), 'Usuario no disponible') AS served_by_name,
                   (sr.total_refund - COALESCE(SUM(sri.tax_amount), 0)) AS subtotal,
                   COALESCE(SUM(sri.tax_amount), 0) AS iva, sr.total_refund AS total
            FROM sales_returns sr
            JOIN sales_return_items sri ON sri.return_id = sr.id
            LEFT JOIN users u ON u.id = sr.user_id
            WHERE sr.id = ?
            GROUP BY sr.id
        ");
        $returnStmt->bind_param('i', $purchase_id);
        $returnStmt->execute();
        $purchase = $returnStmt->get_result()->fetch_assoc();
        $returnStmt->close();

        $itemsStmt = $conn->prepare("
            SELECT sri.*, p.image
            FROM sales_return_items sri
            LEFT JOIN products p ON p.id = sri.product_id
            WHERE sri.return_id = ?
        ");
        $itemsStmt->bind_param('i', $purchase_id);
        $itemsStmt->execute();
        $items = [];
        $result = $itemsStmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['image_url'] = "/api/frontend/uploads/" . $row['image'];
            $items[] = $row;
        }
        $itemsStmt->close();

        echo json_encode(["status" => "success", "data" => ["purchase" => $purchase, "items" => $items]]);
        exit();
    }

    // Only users granted the history/dashboard may view another seller's receipt.
    $resPurchase = $conn->prepare("SELECT pur.*, COALESCE(NULLIF(pur.served_by_name, ''), NULLIF(u.name, ''), NULLIF(SUBSTRING_INDEX(u.email, '@', 1), ''), 'Usuario no disponible') AS served_by_name FROM purchases pur LEFT JOIN users u ON u.id = pur.user_id WHERE pur.id = ?");
    $resPurchase->bind_param("i", $purchase_id);
    $resPurchase->execute();
    $purchase = $resPurchase->get_result()->fetch_assoc();
    if (!$purchase) {
        http_response_code(404);
        exit(json_encode(['status' => 'error', 'message' => 'Comprobante no encontrado.']));
    }
    if (!$canSeeAll && (int)$purchase['user_id'] !== (int)$actor['id']) {
        http_response_code(403);
        exit(json_encode(['status' => 'error', 'message' => 'Solo puedes consultar tus propios comprobantes.']));
    }

    // Get sale items and the remaining quantity eligible for return.
    $resItems = $conn->prepare("SELECT pi.*, COALESCE(NULLIF(pi.product_name, ''), p.name) as product_name, p.image
                                , pi.quantity - COALESCE((SELECT SUM(sri.quantity) FROM sales_return_items sri WHERE sri.purchase_item_id = pi.id), 0) AS returnable_quantity
                                FROM purchase_items pi 
                                LEFT JOIN products p ON pi.product_id = p.id
                                WHERE pi.purchase_id = ?");
    $resItems->bind_param("i", $purchase_id);
    $resItems->execute();
    $result = $resItems->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['image_url'] = "/api/frontend/uploads/" . $row['image'];
        $items[] = $row;
    }

    echo json_encode([
        "status" => "success", 
        "data" => [
            "purchase" => $purchase,
            "items" => $items
        ]
    ]);
}
?>
