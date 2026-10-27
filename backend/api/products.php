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

function ensureBarcodeColumn($conn) {
    $result = $conn->query("SHOW COLUMNS FROM products LIKE 'barcode'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE products ADD COLUMN barcode VARCHAR(100) DEFAULT NULL AFTER name");
    }
}

ensureBarcodeColumn($conn);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['categories'])) {
        $sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> ''";
        $result = $conn->query($sql);
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        echo json_encode($categories);
    } else {
        $category = $_GET['category'] ?? 'todas';
        if ($category !== 'todas') {
            $safeCat = $conn->real_escape_string($category);
            $sql = "SELECT p.*, s.company_name as supplier_name FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.id WHERE p.category = '$safeCat'";
        } else {
            $sql = "SELECT p.*, s.company_name as supplier_name FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.id";
        }
        $result = $conn->query($sql);
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['image_url'] = "/api/frontend/uploads/" . $row['image'];
            $products[] = $row;
        }
        echo json_encode($products);
    }
}
?>
