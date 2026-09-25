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

function respond($status, $message) {
    echo json_encode(["status" => $status, "message" => $message]);
    exit();
}

function ensureBarcodeColumn($conn) {
    $result = $conn->query("SHOW COLUMNS FROM products LIKE 'barcode'");
    if ($result && $result->num_rows === 0) {
        if (!$conn->query("ALTER TABLE products ADD COLUMN barcode VARCHAR(100) DEFAULT NULL AFTER name")) {
            respond("error", "No se pudo preparar el campo de codigo de barras: " . $conn->error);
        }
    }
}

function saveUploadedImage($file) {
    if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond("error", "No se pudo subir la imagen. Codigo: " . $file['error']);
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        respond("error", "La imagen no puede superar los 5 MB");
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

    if (!isset($allowedTypes[$mimeType])) {
        respond("error", "Formato no permitido. Usa JPG, PNG, WEBP o GIF");
    }

    $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        respond("error", "No se pudo crear la carpeta de imagenes");
    }

    $fileName = bin2hex(random_bytes(12)) . '.' . $allowedTypes[$mimeType];
    $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        respond("error", "No se pudo guardar la imagen en el servidor");
    }

    return $fileName;
}

if (!isset($_SESSION['user_id'])) {
    respond("error", "No autenticado");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond("error", "Metodo no permitido");
}

ensureBarcodeColumn($conn);

$action = $_POST['action'] ?? '';

if ($action === 'add') {
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $barcode = $conn->real_escape_string($_POST['barcode'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $applies_iva = (int)($_POST['applies_iva'] ?? 0) === 1 ? 1 : 0;
        $cost_price = (float)($_POST['cost_price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category = $conn->real_escape_string($_POST['category'] ?? '');
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $image = saveUploadedImage($_FILES['image'] ?? null) ?? "";

        $sql = "INSERT INTO products (name, barcode, price, applies_iva, cost_price, stock, category, description, supplier_id, image)
                VALUES ('$name', '$barcode', '$price', '$applies_iva', '$cost_price', '$stock', '$category', '$description', " . ($supplier_id ? $supplier_id : "NULL") . ", '$image')";

        if ($conn->query($sql) === TRUE) {
            respond("success", "Producto anadido");
        }

        respond("error", $conn->error);
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = $conn->real_escape_string($_POST['name'] ?? '');
        $barcode = $conn->real_escape_string($_POST['barcode'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $applies_iva = (int)($_POST['applies_iva'] ?? 0) === 1 ? 1 : 0;
        $cost_price = (float)($_POST['cost_price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category = $conn->real_escape_string($_POST['category'] ?? '');
        $description = $conn->real_escape_string($_POST['description'] ?? '');
        $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

        $imageSql = "";
        $image = saveUploadedImage($_FILES['image'] ?? null);
        if ($image !== null) {
            $imageSql = ", image='$image'";
        }

        $sql = "UPDATE products
                SET name='$name', barcode='$barcode', price='$price', applies_iva='$applies_iva', cost_price='$cost_price', stock='$stock', category='$category',
                    description='$description', supplier_id=" . ($supplier_id ? $supplier_id : "NULL") . " $imageSql
                WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        respond("success", "Producto actualizado");
    }

    respond("error", $conn->error);
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);

    if ($conn->query("DELETE FROM products WHERE id=$id") === TRUE) {
        respond("success", "Producto eliminado");
    }

    respond("error", $conn->error);
}

respond("error", "Accion no valida");
?>
