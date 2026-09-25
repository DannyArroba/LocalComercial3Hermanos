<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5173';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
include('../db.php');

function respond($status, $message, $data = null) {
    echo json_encode(["status" => $status, "message" => $message, "data" => $data]);
    exit();
}

if (!isset($_SESSION['user_id'])) respond('error', 'No autenticado');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond('error', 'Metodo no permitido');

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$purchaseId = (int)($data['purchase_id'] ?? 0);
$requestedItems = is_array($data['items'] ?? null) ? $data['items'] : [];
$customerName = trim($data['customer_name'] ?? '');
$customerIdNumber = trim($data['customer_idnumber'] ?? '');
$customerPhone = trim($data['customer_phone'] ?? '');
$reasonCategory = trim($data['reason_category'] ?? '');
$reasonDetail = trim($data['reason_detail'] ?? '');
$validReasons = ['MALA_ELECCION_PRODUCTO', 'PRODUCTO_DANADO', 'PRODUCTO_EQUIVOCADO', 'PRODUCTO_VENCIDO'];

if ($purchaseId <= 0 || count($requestedItems) === 0) respond('error', 'Selecciona al menos un producto para devolver');
if ($customerName === '') respond('error', 'Nombre y apellido del cliente son obligatorios');
if (!preg_match('/^\d{10}$/', $customerIdNumber)) respond('error', 'La cedula debe contener exactamente 10 digitos');
if (!preg_match('/^09\d{8}$/', $customerPhone)) respond('error', 'El celular debe iniciar con 09 y contener 10 digitos');
if (!in_array($reasonCategory, $validReasons, true)) respond('error', 'Selecciona un motivo de devolucion valido');

$conn->begin_transaction();
try {
    $preparedItems = [];
    $totalRefund = 0;
    $itemStmt = $conn->prepare("
        SELECT pi.id, pi.purchase_id, pi.product_id, pi.product_name, pi.quantity,
               pi.unit_price, pi.unit_cost, pi.tax_rate, pi.tax_amount,
               COALESCE((SELECT SUM(sri.quantity) FROM sales_return_items sri WHERE sri.purchase_item_id = pi.id), 0) AS returned_quantity
        FROM purchase_items pi
        WHERE pi.id = ? AND pi.purchase_id = ? FOR UPDATE
    ");

    foreach ($requestedItems as $requestedItem) {
        $purchaseItemId = (int)($requestedItem['purchase_item_id'] ?? 0);
        $quantity = (int)($requestedItem['quantity'] ?? 0);
        if ($purchaseItemId <= 0 || $quantity <= 0) throw new Exception('Las cantidades seleccionadas deben ser mayores a cero');

        $itemStmt->bind_param('ii', $purchaseItemId, $purchaseId);
        $itemStmt->execute();
        $item = $itemStmt->get_result()->fetch_assoc();
        if (!$item) throw new Exception('Uno de los productos no pertenece a la venta seleccionada');

        $available = (int)$item['quantity'] - (int)$item['returned_quantity'];
        if ($quantity > $available) throw new Exception("Solo hay $available unidades de {$item['product_name']} disponibles para devolver");

        $lineBase = round((float)$item['unit_price'] * $quantity, 2);
        $unitTax = (int)$item['quantity'] > 0 ? (float)$item['tax_amount'] / (int)$item['quantity'] : 0;
        $taxAmount = round($unitTax * $quantity, 2);
        $lineTotal = round($lineBase + $taxAmount, 2);
        $item['return_quantity'] = $quantity;
        $item['return_tax'] = $taxAmount;
        $item['return_total'] = $lineTotal;
        $preparedItems[] = $item;
        $totalRefund += $lineTotal;
    }
    $itemStmt->close();
    $totalRefund = round($totalRefund, 2);

    $returnCode = 'DEV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $userId = (int)$_SESSION['user_id'];
    $returnStmt = $conn->prepare("
        INSERT INTO sales_returns
        (purchase_id, user_id, return_code, customer_name, customer_idnumber, customer_phone, reason_category, reason_detail, total_refund)
        VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?)
    ");
    $returnStmt->bind_param('iissssssd', $purchaseId, $userId, $returnCode, $customerName, $customerIdNumber, $customerPhone, $reasonCategory, $reasonDetail, $totalRefund);
    $returnStmt->execute();
    $returnId = $returnStmt->insert_id;
    $returnStmt->close();

    $detailStmt = $conn->prepare("
        INSERT INTO sales_return_items
        (return_id, purchase_item_id, product_id, product_name, quantity, unit_price, unit_cost, tax_rate, tax_amount, line_total)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stockStmt = $conn->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
    foreach ($preparedItems as $item) {
        $detailStmt->bind_param('iiisiddddd', $returnId, $item['id'], $item['product_id'], $item['product_name'], $item['return_quantity'], $item['unit_price'], $item['unit_cost'], $item['tax_rate'], $item['return_tax'], $item['return_total']);
        $detailStmt->execute();
        $stockStmt->bind_param('ii', $item['return_quantity'], $item['product_id']);
        $stockStmt->execute();
    }
    $detailStmt->close();
    $stockStmt->close();

    $conn->commit();
    respond('success', 'Devolucion registrada y stock actualizado', [
        'return_id' => $returnId,
        'return_code' => $returnCode,
        'total_refund' => $totalRefund
    ]);
} catch (Throwable $error) {
    $conn->rollback();
    respond('error', 'No se pudo registrar la devolucion: ' . $error->getMessage());
}
?>
