<?php
require_once __DIR__ . '/../api-bootstrap.php';
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../db.php';

function respond($status, $message, $data = null) {
    echo json_encode(["status" => $status, "message" => $message, "data" => $data]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    respond("error", "No autenticado");
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get all suppliers or one by id
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $sql = "SELECT * FROM suppliers WHERE id = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            respond("error", "Proveedor no encontrado");
        }
    } else {
        $sql = "SELECT * FROM suppliers ORDER BY company_name ASC";
        $result = $conn->query($sql);
        $suppliers = [];
        while ($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
        echo json_encode($suppliers);
    }
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $company_name = $conn->real_escape_string($_POST['company_name'] ?? '');
        $contact_name = $conn->real_escape_string($_POST['contact_name'] ?? '');
        $email = $conn->real_escape_string($_POST['email'] ?? '');
        $ruc = $conn->real_escape_string($_POST['ruc'] ?? '');
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $address = $conn->real_escape_string($_POST['address'] ?? '');

        $sql = "INSERT INTO suppliers (company_name, contact_name, email, ruc, phone, address)
                VALUES ('$company_name', '$contact_name', '$email', '$ruc', '$phone', '$address')";

        if ($conn->query($sql) === TRUE) {
            respond("success", "Proveedor agregado", ["id" => $conn->insert_id]);
        }
        respond("error", $conn->error);
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $company_name = $conn->real_escape_string($_POST['company_name'] ?? '');
        $contact_name = $conn->real_escape_string($_POST['contact_name'] ?? '');
        $email = $conn->real_escape_string($_POST['email'] ?? '');
        $ruc = $conn->real_escape_string($_POST['ruc'] ?? '');
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $address = $conn->real_escape_string($_POST['address'] ?? '');

        $sql = "UPDATE suppliers
                SET company_name='$company_name', contact_name='$contact_name', email='$email',
                    ruc='$ruc', phone='$phone', address='$address'
                WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            respond("success", "Proveedor actualizado");
        }
        respond("error", $conn->error);
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($conn->query("DELETE FROM suppliers WHERE id=$id") === TRUE) {
            respond("success", "Proveedor eliminado");
        }
        respond("error", $conn->error);
    }

    respond("error", "Acción no válida");
}
?>