<?php
session_start();
include('../backend/db.php');
require_once __DIR__ . '/../backend/lib/access.php';
appRequirePermission($conn, 'transactions');

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Eliminar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_purchase_id'])) {
  $delete_id = (int)($_POST['delete_purchase_id']);

  $check = $conn->query("SELECT id FROM purchases WHERE id = $delete_id AND user_id = $user_id LIMIT 1");
  if ($check && $check->num_rows > 0) {
    $conn->query("DELETE FROM purchases WHERE id = $delete_id AND user_id = $user_id");
  }

  header("Location: purchase-history.php");
  exit();
}

$purchases = [];
$q = $conn->query("SELECT * FROM purchases WHERE user_id = $user_id ORDER BY created_at DESC");
if ($q) {
  while ($row = $q->fetch_assoc()) $purchases[] = $row;
}

$pageTitle = "Historial de compras";
$backUrl = "index.php";
$backText = "Menú";
include("partials/header.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historial de Compras</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<?php /* header ya incluido arriba */ ?>

<main class="container mx-auto p-6 md:p-10">
  <div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Historial de compras</h1>
      <a href="index.php" class="text-sm text-blue-600 hover:underline">Volver a la tienda</a>
    </div>

    <?php if (count($purchases) === 0): ?>
      <div class="bg-white rounded-xl shadow p-6 text-gray-600">
        Aún no tienes compras registradas.
      </div>
    <?php else: ?>

      <div class="space-y-4">
        <?php foreach ($purchases as $p): ?>
          <?php
            $pid = (int)$p['id'];
            $items = [];
            $qi = $conn->query("SELECT * FROM purchase_items WHERE purchase_id = $pid");
            if ($qi) { while ($it = $qi->fetch_assoc()) $items[] = $it; }
            $typeLabel = ($p['purchase_type'] === 'FACTURA') ? 'Factura / Comprobante' : 'Consumidor final';
          ?>

          <details class="bg-white rounded-xl shadow p-5">
            <summary class="cursor-pointer select-none">
              <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-800">Compra #<?php echo $pid; ?></span>
                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700"><?php echo h($typeLabel); ?></span>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    Fecha: <?php echo h($p['created_at']); ?>
                  </div>
                </div>

                <div class="flex items-center justify-between md:justify-end gap-3">
                  <div class="text-right">
                    <div class="text-xs text-gray-500">Total</div>
                    <div class="text-lg font-bold text-gray-900">$<?php echo number_format((float)$p['total'], 2); ?></div>
                  </div>

                  <!-- ✅ Descargar PDF -->
                  <a href="purchase_receipt.php?id=<?php echo $pid; ?>"
                     class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-semibold">
                    📄 PDF
                  </a>

                  <!-- ✅ Eliminar -->
                  <form method="POST"
                        onsubmit="return confirm('¿Seguro que deseas eliminar esta compra? Esta acción no se puede deshacer.');">
                    <input type="hidden" name="delete_purchase_id" value="<?php echo $pid; ?>">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-semibold">
                      🗑️ Eliminar
                    </button>
                  </form>
                </div>
              </div>
            </summary>

            <div class="mt-4 border-t pt-4">
              <?php if ($p['purchase_type'] === 'FACTURA'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mb-4">
                  <div><span class="text-gray-500">Cliente:</span> <span class="font-semibold text-gray-800"><?php echo h($p['customer_name']); ?></span></div>
                  <div><span class="text-gray-500">Cédula/RUC:</span> <span class="font-semibold text-gray-800"><?php echo h($p['customer_idnumber']); ?></span></div>
                  <div><span class="text-gray-500">Correo:</span> <span class="font-semibold text-gray-800"><?php echo h($p['customer_email']); ?></span></div>
                  <div><span class="text-gray-500">Teléfono:</span> <span class="font-semibold text-gray-800"><?php echo h($p['customer_phone']); ?></span></div>
                  <div class="md:col-span-2"><span class="text-gray-500">Dirección:</span> <span class="font-semibold text-gray-800"><?php echo h($p['customer_address']); ?></span></div>
                </div>
              <?php else: ?>
                <div class="text-sm text-gray-600 mb-4">
                  Compra registrada como <span class="font-semibold">Consumidor final</span> (sin datos).
                </div>
              <?php endif; ?>

              <div class="overflow-x-auto">
                <table class="w-full table-fixed">
                  <colgroup>
                    <col />
                    <col style="width: 5rem;">
                    <col style="width: 7rem;">
                    <col style="width: 7rem;">
                  </colgroup>
                  <thead>
                    <tr class="text-xs text-gray-500">
                      <th class="text-left font-semibold pb-2">Producto</th>
                      <th class="text-center font-semibold pb-2">Cant.</th>
                      <th class="text-right font-semibold pb-2">P. Unit</th>
                      <th class="text-right font-semibold pb-2">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($items as $it): ?>
                      <tr class="border-b">
                        <td class="py-2 text-sm text-gray-800"><?php echo h($it['product_name']); ?></td>
                        <td class="py-2 text-center text-sm font-semibold text-gray-700"><?php echo (int)$it['quantity']; ?></td>
                        <td class="py-2 text-right text-sm text-gray-800">$<?php echo number_format((float)$it['unit_price'],2); ?></td>
                        <td class="py-2 text-right text-sm font-semibold text-gray-900">$<?php echo number_format((float)$it['line_total'],2); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="mt-4 border-t pt-3 text-sm space-y-1">
                <div class="flex justify-between">
                  <span class="text-gray-600">Subtotal</span>
                  <span class="font-semibold text-gray-800">$<?php echo number_format((float)$p['subtotal'],2); ?></span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">IVA</span>
                  <span class="font-semibold text-gray-800">$<?php echo number_format((float)$p['iva'],2); ?></span>
                </div>
                <div class="flex justify-between text-base">
                  <span class="font-bold text-gray-900">Total</span>
                  <span class="font-bold text-gray-900">$<?php echo number_format((float)$p['total'],2); ?></span>
                </div>
              </div>
            </div>
          </details>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </div>
</main>

</body>
</html>
