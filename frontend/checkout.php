<?php
session_start();
include('../backend/db.php');
require_once __DIR__ . '/../backend/lib/access.php';
$actingUser = appRequirePermission($conn, 'sales');
$served_by_name = $actingUser['name'];

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

// Si el carrito está vacío, vuelve
if (count($_SESSION['cart']) === 0) {
  header("Location: index.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];
$IVA_RATE = 0.12;

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Armar carrito con info de productos
$cartRows = [];
$subtotal = 0;

foreach ($_SESSION['cart'] as $item) {
  $pid = (int)$item['product_id'];
  $qty = (int)$item['quantity'];
  if ($qty < 1) $qty = 1;

  $q = $conn->query("SELECT id, name, price FROM products WHERE id = '$pid' LIMIT 1");
  if (!$q || $q->num_rows === 0) continue;

  $p = $q->fetch_assoc();
  $name = $p['name'];
  $price = (float)$p['price'];

  $line = $price * $qty;
  $subtotal += $line;

  $cartRows[] = [
    'product_id' => (int)$p['id'],
    'name' => $name,
    'price' => $price,
    'qty' => $qty,
    'line' => $line
  ];
}

$iva = $subtotal * $IVA_RATE;
$total = $subtotal + $iva;

// Si por algún motivo no quedó nada válido
if (count($cartRows) === 0) {
  $_SESSION['cart'] = [];
  header("Location: index.php");
  exit();
}

$errors = [];
$success = false;

// Guardar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_purchase'])) {
  $purchase_type = $_POST['purchase_type'] ?? 'CONSUMIDOR_FINAL';
  if ($purchase_type !== 'CONSUMIDOR_FINAL' && $purchase_type !== 'FACTURA') {
    $purchase_type = 'CONSUMIDOR_FINAL';
  }

  $customer_name = trim($_POST['customer_name'] ?? '');
  $customer_email = trim($_POST['customer_email'] ?? '');
  $customer_phone = trim($_POST['customer_phone'] ?? '');
  $customer_address = trim($_POST['customer_address'] ?? '');
  $customer_idnumber = trim($_POST['customer_idnumber'] ?? '');

  if ($purchase_type === 'FACTURA') {
    if ($customer_name === '') $errors[] = "Falta el nombre del cliente.";
    if ($customer_email === '') $errors[] = "Falta el correo del cliente.";
    if ($customer_phone === '') $errors[] = "Falta el teléfono del cliente.";
    if ($customer_address === '') $errors[] = "Falta la dirección del cliente.";
    if ($customer_idnumber === '') $errors[] = "Falta la cédula o RUC.";
  } else {
    // Consumidor final: vaciar datos
    $customer_name = null;
    $customer_email = null;
    $customer_phone = null;
    $customer_address = null;
    $customer_idnumber = null;
  }

  if (count($errors) === 0) {
    $conn->begin_transaction();

    try {
      $stmt = $conn->prepare("
        INSERT INTO purchases
        (user_id, purchase_type, customer_name, customer_email, customer_phone, customer_address, customer_idnumber, subtotal, iva, total, served_by_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");

      $subtotal_db = number_format($subtotal, 2, '.', '');
      $iva_db = number_format($iva, 2, '.', '');
      $total_db = number_format($total, 2, '.', '');

      $stmt->bind_param(
        "issssssddds",
        $user_id,
        $purchase_type,
        $customer_name,
        $customer_email,
        $customer_phone,
        $customer_address,
        $customer_idnumber,
        $subtotal_db,
        $iva_db,
        $total_db,
        $served_by_name
      );

      $stmt->execute();
      $purchase_id = $stmt->insert_id;
      $stmt->close();

      $stmtItem = $conn->prepare("
        INSERT INTO purchase_items (purchase_id, product_id, product_name, unit_price, quantity, line_total)
        VALUES (?, ?, ?, ?, ?, ?)
      ");

      foreach ($cartRows as $r) {
        $pid = (int)$r['product_id'];
        $pname = $r['name'];
        $uprice = number_format($r['price'], 2, '.', '');
        $qty = (int)$r['qty'];
        $line = number_format($r['line'], 2, '.', '');

        $stmtItem->bind_param("iisdid", $purchase_id, $pid, $pname, $uprice, $qty, $line);
        $stmtItem->execute();
      }

      $stmtItem->close();

      $conn->commit();

      // Vaciar carrito
      $_SESSION['cart'] = [];

      // Redirigir a historial
      header("Location: purchase-history.php");
      exit();

    } catch (Exception $e) {
      $conn->rollback();
      $errors[] = "Error guardando la compra. Intenta otra vez.";
    }
  }
}

$pageTitle = "Checkout";
$backUrl = "cart_page_o_index.php"; // o a donde quieras
$backText = "Volver";
include("partials/header.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.2/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/alerts.js"></script>
</head>

<body class="bg-gray-100">
<?php /* header ya incluido arriba */ ?>

<main class="container mx-auto p-6 md:p-10">
  <div class="max-w-4xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Izquierda: tipo + datos -->
    <div class="bg-white rounded-xl shadow p-6">
      <h1 class="text-2xl font-bold text-gray-800 mb-4">Checkout</h1>

      <?php if (count($errors) > 0): ?>
        <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
          <ul class="list-disc ml-5">
            <?php foreach ($errors as $er): ?>
              <li><?php echo h($er); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-5">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de compra</label>

          <div class="flex flex-col sm:flex-row gap-3">
            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
              <input type="radio" name="purchase_type" value="CONSUMIDOR_FINAL" checked>
              <div>
                <div class="font-semibold text-gray-800">Consumidor final</div>
                <div class="text-xs text-gray-500">Sin datos del cliente</div>
              </div>
            </label>

            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
              <input type="radio" name="purchase_type" value="FACTURA">
              <div>
                <div class="font-semibold text-gray-800">Factura / Comprobante</div>
                <div class="text-xs text-gray-500">Con datos del cliente</div>
              </div>
            </label>
          </div>
        </div>

        <div id="customerBox" class="hidden">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Nombre</label>
              <input name="customer_name" class="w-full border rounded-lg p-3" placeholder="Nombre completo">
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Cédula / RUC</label>
              <input name="customer_idnumber" class="w-full border rounded-lg p-3" placeholder="Cédula o RUC">
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Correo</label>
              <input name="customer_email" type="email" class="w-full border rounded-lg p-3" placeholder="correo@ejemplo.com">
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Teléfono</label>
              <input name="customer_phone" class="w-full border rounded-lg p-3" placeholder="0999999999">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm text-gray-700 mb-1">Dirección</label>
              <input name="customer_address" class="w-full border rounded-lg p-3" placeholder="Dirección completa">
            </div>
          </div>
        </div>

        <button type="submit" name="finish_purchase"
          class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg">
          Terminar compra
        </button>

        <a href="index.php" class="block text-center text-sm text-gray-600 hover:underline">
          Seguir comprando
        </a>
      </form>
    </div>

    <!-- Derecha: resumen -->
    <div class="bg-white rounded-xl shadow p-6">
      <h2 class="text-lg font-bold text-gray-800 mb-4">Resumen de tu compra</h2>

      <div class="overflow-x-auto">
        <table class="w-full table-fixed">
          <colgroup>
            <col />
            <col style="width: 5rem;">
            <col style="width: 7rem;">
          </colgroup>
          <thead>
            <tr class="text-xs text-gray-500">
              <th class="text-left font-semibold pb-2">Producto</th>
              <th class="text-center font-semibold pb-2">Cant.</th>
              <th class="text-right font-semibold pb-2">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cartRows as $r): ?>
              <tr class="border-b">
                <td class="py-2 pr-2 text-sm text-gray-800">
                  <div class="font-medium leading-tight"><?php echo h($r['name']); ?></div>
                  <div class="text-xs text-gray-500">$<?php echo number_format($r['price'],2); ?> c/u</div>
                </td>
                <td class="py-2 text-center text-sm font-semibold text-gray-700"><?php echo (int)$r['qty']; ?></td>
                <td class="py-2 text-right text-sm font-semibold text-gray-800">$<?php echo number_format($r['line'],2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="border-t pt-4 mt-4 space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Total sin IVA</span>
          <span class="font-semibold text-gray-800">$<?php echo number_format($subtotal,2); ?></span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">IVA (12%)</span>
          <span class="font-semibold text-gray-800">$<?php echo number_format($iva,2); ?></span>
        </div>
        <div class="flex justify-between text-base">
          <span class="text-gray-800 font-bold">Total con IVA</span>
          <span class="font-bold text-gray-900">$<?php echo number_format($total,2); ?></span>
        </div>
      </div>
    </div>

  </div>
</main>

<script>
  const radios = document.querySelectorAll('input[name="purchase_type"]');
  const box = document.getElementById('customerBox');

  function refreshCustomerBox(){
    const selected = document.querySelector('input[name="purchase_type"]:checked')?.value;
    if(selected === 'FACTURA'){
      box.classList.remove('hidden');
    } else {
      box.classList.add('hidden');
    }
  }

  radios.forEach(r => r.addEventListener('change', refreshCustomerBox));
  refreshCustomerBox();
</script>

</body>
</html>
