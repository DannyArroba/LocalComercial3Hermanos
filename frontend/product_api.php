<?php
session_start();
include('../backend/db.php');
require_once __DIR__ . '/../backend/lib/access.php';
appRequirePermission($conn, 'inventory');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(["status" => "error", "message" => "No autenticado"]);
  exit();
}

$action = $_POST['action'] ?? '';

function clean($conn, $v) {
  return $conn->real_escape_string(trim((string)$v));
}

if ($action === 'update') {
  $id = (int)($_POST['id'] ?? 0);
  $name = clean($conn, $_POST['name'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $category = clean($conn, $_POST['category'] ?? '');
  $description = clean($conn, $_POST['description'] ?? '');

  if ($id <= 0 || $name === '' || $price <= 0 || $category === '' || $description === '') {
    echo json_encode(["status" => "error", "message" => "Datos inválidos"]);
    exit();
  }

  // Imagen opcional
  $imageSql = "";
  $newImageName = null;

  if (!empty($_FILES['image']['name'])) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed)) {
      echo json_encode(["status" => "error", "message" => "Formato de imagen no permitido (jpg, png, webp)"]);
      exit();
    }

    $newImageName = "prod_" . $id . "_" . time() . "." . $ext;
    $target = __DIR__ . "/uploads/" . $newImageName; // frontend/uploads

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      echo json_encode(["status" => "error", "message" => "No se pudo subir la imagen"]);
      exit();
    }

    $imageSql = ", image='$newImageName'";
  }

  $sql = "UPDATE products 
          SET name='$name', price='$price', category='$category', description='$description' $imageSql
          WHERE id=$id";

  if ($conn->query($sql) === TRUE) {
    // Obtener imagen final para actualizar UI
    $imgRes = $conn->query("SELECT image FROM products WHERE id=$id LIMIT 1");
    $imgRow = $imgRes ? $imgRes->fetch_assoc() : null;
    $finalImage = $imgRow['image'] ?? $newImageName ?? "";

    echo json_encode([
      "status" => "success",
      "message" => "Producto actualizado",
      "product" => [
        "id" => $id,
        "name" => $name,
        "price" => number_format($price, 2, '.', ''),
        "category" => $category,
        "description" => $description,
        "image" => $finalImage
      ]
    ]);
    exit();
  }

  echo json_encode(["status" => "error", "message" => "Error al actualizar: " . $conn->error]);
  exit();
}

if ($action === 'delete') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "ID inválido"]);
    exit();
  }

  if ($conn->query("DELETE FROM products WHERE id=$id") === TRUE) {
    echo json_encode(["status" => "success", "message" => "Producto eliminado"]);
    exit();
  }

  echo json_encode(["status" => "error", "message" => "Error al eliminar: " . $conn->error]);
  exit();
}

echo json_encode(["status" => "error", "message" => "Acción no válida"]);
