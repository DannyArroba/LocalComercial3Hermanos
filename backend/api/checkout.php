<?php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5173';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

include('../db.php');

function respond($status, $message, $extra = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));
    exit();
}

if (!isset($_SESSION['user_id'])) {
    respond("error", "Sesion expirada. Inicia sesion nuevamente.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond("error", "Metodo no permitido");
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$userId = (int)$_SESSION['user_id'];
$purchaseType = $data['purchase_type'] ?? 'CONSUMIDOR_FINAL';
$paymentMethod = $data['payment_method'] ?? '';
$amountReceived = isset($data['amount_received']) ? round((float)$data['amount_received'], 2) : null;
$transferReference = trim($data['transfer_reference'] ?? '');

$customerName = trim($data['customer_name'] ?? '');
$customerEmail = trim($data['customer_email'] ?? '');
$customerPhone = trim($data['customer_phone'] ?? '');
$customerAddress = trim($data['customer_address'] ?? '');
$customerIdNumber = trim($data['customer_idnumber'] ?? '');

if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    respond("error", "El carrito esta vacio.");
}

$subtotal = 0;
$iva = 0;
$pricedItems = [];
$productStmt = $conn->prepare("SELECT id, name, price, cost_price, applies_iva FROM products WHERE id = ?");
foreach ($_SESSION['cart'] as $cartItem) {
    $productId = (int)$cartItem['id'];
    $quantity = (int)$cartItem['quantity'];
    $productStmt->bind_param("i", $productId);
    $productStmt->execute();
    $product = $productStmt->get_result()->fetch_assoc();
    if (!$product) respond("error", "Uno de los productos ya no esta disponible.");

    $lineBase = round((float)$product['price'] * $quantity, 2);
    $taxRate = (int)$product['applies_iva'] === 1 ? 15.00 : 0.00;
    $taxAmount = round($lineBase * ($taxRate / 100), 2);
    $lineTotal = round($lineBase + $taxAmount, 2);
    $subtotal += $lineBase;
    $iva += $taxAmount;
    $pricedItems[] = [
        'id' => $productId,
        'name' => $product['name'],
        'unit_price' => (float)$product['price'],
        'unit_cost' => (float)$product['cost_price'],
        'tax_rate' => $taxRate,
        'tax_amount' => $taxAmount,
        'quantity' => $quantity,
        'line_total' => $lineTotal
    ];
}
$productStmt->close();
$subtotal = round($subtotal, 2);
$iva = round($iva, 2);
$total = round($subtotal + $iva, 2);

if (!in_array($paymentMethod, ['EFECTIVO', 'TRANSFERENCIA'], true)) {
    respond("error", "Selecciona una forma de pago valida.");
}

$changeAmount = null;
if ($paymentMethod === 'EFECTIVO') {
    if ($amountReceived === null || $amountReceived < $total) {
        respond("error", "El efectivo recibido no cubre el total de la venta.");
    }
    $changeAmount = round($amountReceived - $total, 2);
    $transferReference = null;
} else {
    if ($transferReference === '') {
        respond("error", "Ingresa el numero de comprobante de la transferencia.");
    }
    $amountReceived = null;
}

$conn->begin_transaction();

try {
    $customerId = null;

    if ($purchaseType === 'FACTURA') {
        if (!preg_match('/^\d{10}$/', $customerIdNumber)) {
            throw new Exception("La cedula debe contener exactamente 10 digitos.");
        }
        if (!preg_match('/^09\d{8}$/', $customerPhone)) {
            throw new Exception("El celular debe iniciar con 09 y contener 10 digitos.");
        }
        if ($customerName === '' || $customerAddress === '') {
            throw new Exception("Nombre y direccion del cliente son obligatorios.");
        }
        if ($customerEmail !== '' && !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El correo electronico no es valido.");
        }

        $findCustomer = $conn->prepare("SELECT id FROM customers WHERE id_number = ?");
        $findCustomer->bind_param("s", $customerIdNumber);
        $findCustomer->execute();
        $existingCustomer = $findCustomer->get_result()->fetch_assoc();
        $findCustomer->close();

        if ($existingCustomer) {
            $customerId = (int)$existingCustomer['id'];
            $updateCustomer = $conn->prepare("
                UPDATE customers
                SET name = ?, email = NULLIF(?, ''), phone = ?, address = ?
                WHERE id = ?
            ");
            $updateCustomer->bind_param(
                "ssssi",
                $customerName,
                $customerEmail,
                $customerPhone,
                $customerAddress,
                $customerId
            );
            $updateCustomer->execute();
            $updateCustomer->close();
        } else {
            $insertCustomer = $conn->prepare("
                INSERT INTO customers (name, id_number, email, phone, address)
                VALUES (?, ?, NULLIF(?, ''), ?, ?)
            ");
            $insertCustomer->bind_param(
                "sssss",
                $customerName,
                $customerIdNumber,
                $customerEmail,
                $customerPhone,
                $customerAddress
            );
            $insertCustomer->execute();
            $customerId = $insertCustomer->insert_id;
            $insertCustomer->close();
        }
    } else {
        $customerName = null;
        $customerEmail = null;
        $customerPhone = null;
        $customerAddress = null;
        $customerIdNumber = null;
    }

    $purchaseStmt = $conn->prepare("
        INSERT INTO purchases
        (user_id, customer_id, purchase_type, payment_method, customer_name, customer_email,
         customer_phone, customer_address, customer_idnumber, subtotal, iva, total,
         amount_received, change_amount, transfer_reference)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $purchaseStmt->bind_param(
        "iisssssssddddds",
        $userId,
        $customerId,
        $purchaseType,
        $paymentMethod,
        $customerName,
        $customerEmail,
        $customerPhone,
        $customerAddress,
        $customerIdNumber,
        $subtotal,
        $iva,
        $total,
        $amountReceived,
        $changeAmount,
        $transferReference
    );
    $purchaseStmt->execute();
    $purchaseId = $purchaseStmt->insert_id;
    $purchaseStmt->close();

    $itemStmt = $conn->prepare("
        INSERT INTO purchase_items
        (purchase_id, product_id, product_name, unit_price, unit_cost, tax_rate, tax_amount, quantity, line_total)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stockStmt = $conn->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ? AND stock >= ?
    ");

    foreach ($pricedItems as $item) {
        $productId = (int)$item['id'];
        $productName = $item['name'];
        $unitPrice = (float)$item['unit_price'];
        $unitCost = (float)$item['unit_cost'];
        $taxRate = (float)$item['tax_rate'];
        $taxAmount = (float)$item['tax_amount'];
        $quantity = (int)$item['quantity'];
        $lineTotal = (float)$item['line_total'];

        $stockStmt->bind_param("iii", $quantity, $productId, $quantity);
        $stockStmt->execute();
        if ($stockStmt->affected_rows !== 1) {
            throw new Exception("Stock insuficiente para " . $productName);
        }

        $itemStmt->bind_param(
            "iisddddid",
            $purchaseId,
            $productId,
            $productName,
            $unitPrice,
            $unitCost,
            $taxRate,
            $taxAmount,
            $quantity,
            $lineTotal
        );
        $itemStmt->execute();
    }

    $itemStmt->close();
    $stockStmt->close();
    $conn->commit();
    $_SESSION['cart'] = [];

    respond("success", "Venta registrada con exito", [
        "purchase_id" => $purchaseId,
        "customer_id" => $customerId,
        "subtotal" => $subtotal,
        "iva" => $iva,
        "total" => $total,
        "change_amount" => $changeAmount
    ]);
} catch (Throwable $error) {
    $conn->rollback();
    respond("error", "No se pudo registrar la venta: " . $error->getMessage());
}
?>
