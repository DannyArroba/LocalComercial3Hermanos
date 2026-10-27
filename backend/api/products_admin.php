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

function respond($status, $message) {
    echo json_encode(["status" => $status, "message" => $message]);
    exit();
}

function normalizeProductName($name) {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    return function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
}

function findDuplicateProduct($conn, $name, $barcode = '', $excludeId = 0) {
    $normalizedName = normalizeProductName($name);
    $normalizedBarcode = trim($barcode);

    if ($normalizedName === '') {
        respond("error", "El nombre del producto es obligatorio");
    }

    $sql = "SELECT id, name, barcode FROM products WHERE id <> ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond("error", "No se pudo validar duplicados: " . $conn->error);
    }

    $stmt->bind_param("i", $excludeId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($product = $result->fetch_assoc()) {
        $existingName = normalizeProductName($product['name'] ?? '');
        $existingBarcode = trim($product['barcode'] ?? '');

        if ($existingName === $normalizedName) {
            return [
                "type" => "name",
                "product" => $product
            ];
        }

        if ($normalizedBarcode !== '' && $existingBarcode !== '' && $existingBarcode === $normalizedBarcode) {
            return [
                "type" => "barcode",
                "product" => $product
            ];
        }
    }

    return null;
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
        $rawName = trim(preg_replace('/\s+/', ' ', $_POST['name'] ?? ''));
        $rawBarcode = trim($_POST['barcode'] ?? '');
        $duplicate = findDuplicateProduct($conn, $rawName, $rawBarcode);
        if ($duplicate) {
            $product = $duplicate['product'];
            $reason = $duplicate['type'] === 'barcode' ? "codigo de barras" : "nombre";
            respond("error", "Producto duplicado por $reason: ya existe '{$product['name']}' (ID {$product['id']}). Usa editar o abastecer stock.");
        }

        $name = $conn->real_escape_string($rawName);
        $barcode = $conn->real_escape_string($rawBarcode);
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
        $rawName = trim(preg_replace('/\s+/', ' ', $_POST['name'] ?? ''));
        $rawBarcode = trim($_POST['barcode'] ?? '');
        $duplicate = findDuplicateProduct($conn, $rawName, $rawBarcode, $id);
        if ($duplicate) {
            $product = $duplicate['product'];
            $reason = $duplicate['type'] === 'barcode' ? "codigo de barras" : "nombre";
            respond("error", "Producto duplicado por $reason: ya existe '{$product['name']}' (ID {$product['id']}). No se guardo para evitar inconsistencias.");
        }

        $name = $conn->real_escape_string($rawName);
        $barcode = $conn->real_escape_string($rawBarcode);
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
