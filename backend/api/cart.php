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

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

if ($method === 'GET') {
    echo json_encode($_SESSION['cart']);
} elseif ($method === 'POST') {
    $action = $data['action'] ?? '';

    if ($action === 'add') {
        $id = $data['id'];
        $quantity = (int)($data['quantity'] ?? 1);

        $sql = "SELECT * FROM products WHERE id = $id";
        $res = $conn->query($sql);
        if ($res && $res->num_rows > 0) {
            $product = $res->fetch_assoc();
            
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $id) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['cart'][] = [
                    "id" => $product['id'],
                    "name" => $product['name'],
                    "price" => $product['price'],
                    "applies_iva" => (int)$product['applies_iva'],
                    "image" => "http://localhost/TienditaNexar/frontend/uploads/" . $product['image'],
                    "quantity" => $quantity
                ];
            }
            echo json_encode(["status" => "success", "cart" => $_SESSION['cart']]);
        } else {
            echo json_encode(["status" => "error", "message" => "Producto no encontrado"]);
        }
    } elseif ($action === 'remove') {
        $id = $data['id'];
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($id) {
            return $item['id'] != $id;
        });
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        echo json_encode(["status" => "success", "cart" => $_SESSION['cart']]);
    } elseif ($action === 'update') {
        $id = $data['id'];
        $quantity = (int)$data['quantity'];
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id) {
                $item['quantity'] = $quantity;
                break;
            }
        }
        echo json_encode(["status" => "success", "cart" => $_SESSION['cart']]);
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        echo json_encode(["status" => "success", "cart" => []]);
    }
}
?>
