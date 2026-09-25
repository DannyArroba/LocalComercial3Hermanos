<?php
session_start();
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5173';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

include('../db.php');

function respond($status, $message, $data = null) {
    echo json_encode(["status" => $status, "message" => $message, "data" => $data]);
    exit();
}

if (!isset($_SESSION['user_id'])) respond("error", "No autenticado");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("
        SELECT sp.id, sp.total, sp.status, sp.created_at, sp.paid_at, sp.received_at,
               sp.cancelled_at, sp.refunded_at, sp.reversal_code, sp.reversal_reason,
               spi.product_id, spi.quantity, spi.cost_price,
               p.name AS product_name, p.image,
               s.company_name AS supplier_name, u.email AS requested_by
        FROM stock_purchases sp
        JOIN stock_purchase_items spi ON spi.purchase_id = sp.id
        JOIN products p ON p.id = spi.product_id
        LEFT JOIN suppliers s ON s.id = sp.supplier_id
        LEFT JOIN users u ON u.id = sp.user_id
        ORDER BY sp.created_at DESC, sp.id DESC
    ");
    $purchases = [];
    while ($row = $result->fetch_assoc()) {
        $row['image_url'] = "http://localhost/TienditaNexar/frontend/uploads/" . $row['image'];
        $purchases[] = $row;
    }
    respond("success", "Historial obtenido", $purchases);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond("error", "Metodo no permitido");

$action = $_POST['action'] ?? 'create';

if ($action === 'create') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $purchaseCost = round((float)($_POST['cost_price'] ?? 0), 2);
    $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

    if ($productId <= 0 || $quantity <= 0 || $purchaseCost <= 0 || $supplierId === null) {
        respond("error", "Producto, proveedor, cantidad y costo de compra son obligatorios");
    }

    $conn->begin_transaction();
    try {
        $productStmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $productStmt->bind_param("i", $productId);
        $productStmt->execute();
        if ($productStmt->get_result()->num_rows === 0) throw new Exception("Producto no encontrado");
        $productStmt->close();

        $supplierStmt = $conn->prepare("SELECT id FROM suppliers WHERE id = ?");
        $supplierStmt->bind_param("i", $supplierId);
        $supplierStmt->execute();
        if ($supplierStmt->get_result()->num_rows === 0) throw new Exception("Proveedor no encontrado");
        $supplierStmt->close();

        $total = round($quantity * $purchaseCost, 2);
        $userId = (int)$_SESSION['user_id'];
        $purchaseStmt = $conn->prepare("INSERT INTO stock_purchases (user_id, supplier_id, total, status) VALUES (?, ?, ?, 'SOLICITADO')");
        $purchaseStmt->bind_param("iid", $userId, $supplierId, $total);
        $purchaseStmt->execute();
        $purchaseId = $purchaseStmt->insert_id;
        $purchaseStmt->close();

        $itemStmt = $conn->prepare("INSERT INTO stock_purchase_items (purchase_id, product_id, quantity, cost_price) VALUES (?, ?, ?, ?)");
        $itemStmt->bind_param("iiid", $purchaseId, $productId, $quantity, $purchaseCost);
        $itemStmt->execute();
        $itemStmt->close();

        $conn->commit();
        respond("success", "Solicitud de abastecimiento registrada", ["purchase_id" => $purchaseId, "total" => $total]);
    } catch (Throwable $error) {
        $conn->rollback();
        respond("error", "No se pudo registrar la solicitud: " . $error->getMessage());
    }
}

$purchaseId = (int)($_POST['purchase_id'] ?? 0);
if ($purchaseId <= 0) respond("error", "Solicitud no valida");

if ($action === 'mark_paid') {
    $stmt = $conn->prepare("UPDATE stock_purchases SET status = 'PAGADO', paid_at = NOW() WHERE id = ? AND status = 'SOLICITADO'");
    $stmt->bind_param("i", $purchaseId);
    $stmt->execute();
    $updated = $stmt->affected_rows;
    $stmt->close();
    if ($updated !== 1) respond("error", "La solicitud ya fue pagada o no esta disponible");
    respond("success", "Pago registrado");
}

if ($action === 'cancel') {
    $stmt = $conn->prepare("UPDATE stock_purchases SET status = 'CANCELADO', cancelled_at = NOW() WHERE id = ? AND status = 'SOLICITADO'");
    $stmt->bind_param("i", $purchaseId);
    $stmt->execute();
    $updated = $stmt->affected_rows;
    $stmt->close();
    if ($updated !== 1) respond("error", "Solo se pueden cancelar solicitudes pendientes de pago");
    respond("success", "Solicitud cancelada");
}

if ($action === 'mark_received') {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            SELECT sp.status, sp.supplier_id, spi.product_id, spi.quantity, spi.cost_price,
                   p.stock, p.cost_price AS current_cost
            FROM stock_purchases sp
            JOIN stock_purchase_items spi ON spi.purchase_id = sp.id
            JOIN products p ON p.id = spi.product_id
            WHERE sp.id = ? FOR UPDATE
        ");
        $stmt->bind_param("i", $purchaseId);
        $stmt->execute();
        $purchase = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$purchase) throw new Exception("Solicitud no encontrada");
        if ($purchase['status'] !== 'PAGADO') throw new Exception("Primero debes registrar el pago");

        $currentStock = (int)$purchase['stock'];
        $quantity = (int)$purchase['quantity'];
        $newStock = $currentStock + $quantity;
        $averageCost = round((($currentStock * (float)$purchase['current_cost']) + ($quantity * (float)$purchase['cost_price'])) / $newStock, 2);

        $productStmt = $conn->prepare("UPDATE products SET stock = ?, cost_price = ?, supplier_id = ? WHERE id = ?");
        $productStmt->bind_param("idii", $newStock, $averageCost, $purchase['supplier_id'], $purchase['product_id']);
        $productStmt->execute();
        $productStmt->close();

        $statusStmt = $conn->prepare("UPDATE stock_purchases SET status = 'RECIBIDO', received_at = NOW() WHERE id = ? AND status = 'PAGADO'");
        $statusStmt->bind_param("i", $purchaseId);
        $statusStmt->execute();
        if ($statusStmt->affected_rows !== 1) throw new Exception("No se pudo confirmar la recepcion");
        $statusStmt->close();

        $conn->commit();
        respond("success", "Productos recibidos y stock actualizado", ["new_stock" => $newStock]);
    } catch (Throwable $error) {
        $conn->rollback();
        respond("error", "No se pudo recibir el abastecimiento: " . $error->getMessage());
    }
}

if ($action === 'refund') {
    $reversalReason = trim($_POST['reversal_reason'] ?? '');
    if ($reversalReason === '') respond("error", "Indica el motivo de la reversa");

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            SELECT sp.status
            FROM stock_purchases sp
            WHERE sp.id = ? FOR UPDATE
        ");
        $stmt->bind_param("i", $purchaseId);
        $stmt->execute();
        $purchase = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$purchase) throw new Exception("Abastecimiento no encontrado");
        if ($purchase['status'] !== 'PAGADO') {
            throw new Exception("Solo se puede reversar un pago antes de recibir los productos");
        }

        $reversalCode = 'REV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $reversedBy = (int)$_SESSION['user_id'];
        $refundStmt = $conn->prepare("UPDATE stock_purchases SET status = 'REEMBOLSADO', refunded_at = NOW(), reversal_code = ?, reversal_reason = ?, reversed_by = ? WHERE id = ? AND status = 'PAGADO'");
        $refundStmt->bind_param("ssii", $reversalCode, $reversalReason, $reversedBy, $purchaseId);
        $refundStmt->execute();
        if ($refundStmt->affected_rows !== 1) throw new Exception("No se pudo registrar el reembolso");
        $refundStmt->close();

        $conn->commit();
        respond("success", "Reversa registrada", ["reversal_code" => $reversalCode]);
    } catch (Throwable $error) {
        $conn->rollback();
        respond("error", "No se pudo registrar el reembolso: " . $error->getMessage());
    }
}

respond("error", "Accion no valida");
?>
