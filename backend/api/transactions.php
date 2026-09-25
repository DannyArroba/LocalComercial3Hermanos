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

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $transactions = [];
    $salesResult = $conn->query("
        SELECT p.id, p.purchase_type, p.customer_name, p.customer_idnumber, p.customer_phone,
               p.total, p.created_at, COALESCE(c.name, p.customer_name) AS display_customer_name
        FROM purchases p
        LEFT JOIN customers c ON c.id = p.customer_id
    ");
    while ($row = $salesResult->fetch_assoc()) {
        $row['record_key'] = 'V-' . $row['id'];
        $row['record_type'] = 'VENTA';
        $row['original_purchase_id'] = null;
        $row['return_code'] = null;
        $transactions[] = $row;
    }

    $returnsResult = $conn->query("SELECT * FROM sales_returns");
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
            'return_code' => $row['return_code']
        ];
    }

    usort($transactions, function ($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
    echo json_encode(["status" => "success", "data" => $transactions]);
} elseif ($action === 'details') {
    $purchase_id = $_GET['id'] ?? 0;
    $recordType = $_GET['type'] ?? 'VENTA';
    if (!$purchase_id) {
        echo json_encode(["status" => "error", "message" => "ID de compra no proporcionado"]);
        exit();
    }

    if ($recordType === 'DEVOLUCION') {
        $returnStmt = $conn->prepare("
            SELECT sr.id, sr.return_code, sr.purchase_id AS original_purchase_id,
                   'DEVOLUCION' AS purchase_type, 'REEMBOLSO' AS payment_method,
                   sr.customer_name, sr.customer_idnumber, sr.customer_phone,
                   sr.reason_category, sr.reason_detail, sr.created_at,
                   (sr.total_refund - COALESCE(SUM(sri.tax_amount), 0)) AS subtotal,
                   COALESCE(SUM(sri.tax_amount), 0) AS iva, sr.total_refund AS total
            FROM sales_returns sr
            JOIN sales_return_items sri ON sri.return_id = sr.id
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
            JOIN products p ON p.id = sri.product_id
            WHERE sri.return_id = ?
        ");
        $itemsStmt->bind_param('i', $purchase_id);
        $itemsStmt->execute();
        $items = [];
        $result = $itemsStmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['image_url'] = "http://localhost/TienditaNexar/frontend/uploads/" . $row['image'];
            $items[] = $row;
        }
        $itemsStmt->close();

        echo json_encode(["status" => "success", "data" => ["purchase" => $purchase, "items" => $items]]);
        exit();
    }

    // Get sale items and the remaining quantity eligible for return.
    $resItems = $conn->prepare("SELECT pi.*, p.name as product_name, p.image 
                                , pi.quantity - COALESCE((SELECT SUM(sri.quantity) FROM sales_return_items sri WHERE sri.purchase_item_id = pi.id), 0) AS returnable_quantity
                                FROM purchase_items pi 
                                JOIN products p ON pi.product_id = p.id 
                                WHERE pi.purchase_id = ?");
    $resItems->bind_param("i", $purchase_id);
    $resItems->execute();
    $result = $resItems->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['image_url'] = "http://localhost/TienditaNexar/frontend/uploads/" . $row['image'];
        $items[] = $row;
    }

    // Get purchase info
    $resPurchase = $conn->prepare("SELECT * FROM purchases WHERE id = ?");
    $resPurchase->bind_param("i", $purchase_id);
    $resPurchase->execute();
    $purchase = $resPurchase->get_result()->fetch_assoc();

    echo json_encode([
        "status" => "success", 
        "data" => [
            "purchase" => $purchase,
            "items" => $items
        ]
    ]);
}
?>
