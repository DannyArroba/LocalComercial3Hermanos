<?php
session_start();
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:5173';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

include('../db.php');

function respond($status, $message = '', $data = null) {
    $response = ["status" => $status];
    if ($message !== '') {
        $response["message"] = $message;
    }
    if ($data !== null) {
        $response["data"] = $data;
    }
    echo json_encode($response);
    exit();
}

function validateCustomer($data) {
    $name = trim($data['name'] ?? '');
    $idNumber = trim($data['id_number'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');

    if ($name === '' || $idNumber === '' || $phone === '' || $address === '') {
        respond("error", "Nombre, cedula, telefono y direccion son obligatorios");
    }
    if (!preg_match('/^\d{10}$/', $idNumber)) {
        respond("error", "La cedula debe contener exactamente 10 digitos");
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond("error", "El correo electronico no es valido");
    }
    if (!preg_match('/^09\d{8}$/', $phone)) {
        respond("error", "El celular debe iniciar con 09 y contener 10 digitos");
    }

    return [$name, $idNumber, $email, $phone, $address];
}

if (!isset($_SESSION['user_id'])) {
    respond("error", "No autenticado");
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $idNumber = trim($_GET['id_number'] ?? '');
    $id = (int)($_GET['id'] ?? 0);

    if ($idNumber !== '') {
        $stmt = $conn->prepare("
            SELECT c.*,
                   COUNT(p.id) AS transaction_count,
                   COALESCE(SUM(p.total), 0) AS total_spent
            FROM customers c
            LEFT JOIN purchases p ON p.customer_id = c.id
            WHERE c.id_number = ?
            GROUP BY c.id
        ");
        $stmt->bind_param("s", $idNumber);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        respond("success", $customer ? "Cliente encontrado" : "Cliente no registrado", $customer);
    }

    if ($id > 0) {
        $stmt = $conn->prepare("
            SELECT c.*,
                   COUNT(p.id) AS transaction_count,
                   COALESCE(SUM(p.total), 0) AS total_spent,
                   MAX(p.created_at) AS last_purchase_at
            FROM customers c
            LEFT JOIN purchases p ON p.customer_id = c.id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        if (!$customer) {
            respond("error", "Cliente no encontrado");
        }

        $historyStmt = $conn->prepare("
            SELECT id, purchase_type, subtotal, iva, total, created_at
            FROM purchases
            WHERE customer_id = ?
            ORDER BY created_at DESC
        ");
        $historyStmt->bind_param("i", $id);
        $historyStmt->execute();
        $history = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $customer['transactions'] = $history;
        respond("success", "", $customer);
    }

    $result = $conn->query("
        SELECT c.*,
               COUNT(p.id) AS transaction_count,
               COALESCE(SUM(p.total), 0) AS total_spent,
               MAX(p.created_at) AS last_purchase_at
        FROM customers c
        LEFT JOIN purchases p ON p.customer_id = c.id
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    respond("success", "", $result->fetch_all(MYSQLI_ASSOC));
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];
$action = $data['action'] ?? '';

if ($action === 'add') {
    [$name, $idNumber, $email, $phone, $address] = validateCustomer($data);
    $stmt = $conn->prepare("
        INSERT INTO customers (name, id_number, email, phone, address)
        VALUES (?, ?, NULLIF(?, ''), ?, ?)
    ");
    $stmt->bind_param("sssss", $name, $idNumber, $email, $phone, $address);
    try {
        $stmt->execute();
        respond("success", "Cliente registrado correctamente", ["id" => $stmt->insert_id]);
    } catch (mysqli_sql_exception $e) {
        respond("error", $e->getCode() === 1062 ? "La cedula o el correo ya estan registrados" : $e->getMessage());
    }
}

if ($action === 'update') {
    $id = (int)($data['id'] ?? 0);
    [$name, $idNumber, $email, $phone, $address] = validateCustomer($data);
    $stmt = $conn->prepare("
        UPDATE customers
        SET name = ?, id_number = ?, email = NULLIF(?, ''), phone = ?, address = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssssi", $name, $idNumber, $email, $phone, $address, $id);
    try {
        $stmt->execute();
        respond("success", "Cliente actualizado correctamente");
    } catch (mysqli_sql_exception $e) {
        respond("error", $e->getCode() === 1062 ? "La cedula o el correo ya estan registrados" : $e->getMessage());
    }
}

if ($action === 'delete') {
    $id = (int)($data['id'] ?? 0);
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM purchases WHERE customer_id = ?");
    $countStmt->bind_param("i", $id);
    $countStmt->execute();
    $purchaseCount = (int)$countStmt->get_result()->fetch_assoc()['total'];

    if ($purchaseCount > 0) {
        respond("error", "No se puede eliminar un cliente que tiene transacciones registradas");
    }

    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    respond("success", "Cliente eliminado correctamente");
}

respond("error", "Accion no valida");
?>
