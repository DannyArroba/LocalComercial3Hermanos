<?php
require_once __DIR__ . '/../backend/api-bootstrap.php';
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/lib/access.php';
$actor = appRequirePermission($conn, ['sales', 'transactions', 'dashboard']);

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = (int)$_SESSION['user_id'];
$purchase_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($purchase_id <= 0) die("ID inválido.");

// Historial/dashboard pueden consultar todos; un vendedor solo sus comprobantes.
$canSeeAll = $actor['role'] === 'admin' || in_array('dashboard', $actor['permissions'], true) || in_array('transactions', $actor['permissions'], true);
$ownership = $canSeeAll ? '' : " AND pur.user_id = $user_id";
$q = $conn->query("SELECT pur.*, COALESCE(NULLIF(pur.served_by_name, ''), NULLIF(u.name, ''), NULLIF(SUBSTRING_INDEX(u.email, '@', 1), ''), 'Usuario no disponible') AS served_by_name FROM purchases pur LEFT JOIN users u ON u.id = pur.user_id WHERE pur.id = $purchase_id $ownership LIMIT 1");
if (!$q || $q->num_rows === 0) die("Compra no encontrada o no autorizada.");

$p = $q->fetch_assoc();

// Items
$items = [];
$qi = $conn->query("SELECT * FROM purchase_items WHERE purchase_id = $purchase_id");
if ($qi) {
  while ($it = $qi->fetch_assoc()) $items[] = $it;
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$typeLabel = ($p['purchase_type'] === 'FACTURA') ? 'Factura / Comprobante' : 'Consumidor final';



?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Comprobante #<?php echo $purchase_id; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.2/dist/tailwind.min.css" rel="stylesheet">

  <style>
    /* ✅ Ajustes para impresión PDF */
    @media print {
      .no-print { display: none !important; }
      body { background: #fff !important; }
      .paper { box-shadow: none !important; margin: 0 !important; border: 0 !important; }
    }
  </style>
</head>

<body class="bg-gray-100">
  <!-- Barra superior -->
  <div class="no-print bg-blue-600 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
      <a href="purchase-history.php" class="underline">← Volver al historial</a>
      <div class="flex gap-2">
        <button onclick="window.print()"
          class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100">
          Descargar PDF
        </button>
      </div>
    </div>
  </div>

  <main class="container mx-auto p-6 md:p-10">
    <div class="paper max-w-3xl mx-auto bg-white rounded-xl shadow p-6 md:p-10 border">

      <!-- Encabezado factura -->
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-6">
        <div class="flex items-start gap-4">
          <!-- ✅ Logo (opcional) -->
          <div class="w-16 h-16 rounded-lg bg-white flex items-center justify-center overflow-hidden">
          <img src="assets/logo.png" class="w-full h-full object-cover" alt="Logo"> 
          <!-- si tienes logo ponlo así:
                 <img src="assets/logo.png" class="w-full h-full object-cover" alt="Logo">
                 si no, se queda el cuadrito -->
            <span class="text- text-xs"></span>
          </div>

          <div>
            <h1 class="text-2xl font-extrabold text-gray-900">TIENDA 3 HERMANOS</h1>
            <p class="text-sm text-gray-600">YANUNCOCHA Y BAÑOS</p>
            <p class="text-sm text-gray-600">Tel: 0983241223 • 3hermanossan@gmail.com</p>
          </div>
        </div>

        <div class="text-left sm:text-right">
          <div class="text-sm text-gray-500">Comprobante</div>
          <div class="text-2xl font-extrabold text-gray-900">#<?php echo $purchase_id; ?></div>
          <div class="text-sm text-gray-600">Fecha: <?php echo h($p['created_at']); ?></div>
          <div class="text-sm text-gray-600">Atendido por: <?php echo h($p['served_by_name']); ?></div>
          <div class="inline-flex mt-2 text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700 font-semibold">
            <?php echo h($typeLabel); ?>
          </div>
        </div>
      </div>

      <!-- Cliente -->
      <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gray-50 rounded-lg p-4 border">
          <div class="text-xs text-gray-500 font-semibold mb-2">DATOS DEL CLIENTE</div>

          <?php if ($p['purchase_type'] === 'FACTURA'): ?>
            <div class="text-sm"><span class="text-gray-500">Nombre:</span> <span class="font-semibold text-gray-900"><?php echo h($p['customer_name']); ?></span></div>
            <div class="text-sm"><span class="text-gray-500">Cédula/RUC:</span> <span class="font-semibold text-gray-900"><?php echo h($p['customer_idnumber']); ?></span></div>
            <div class="text-sm"><span class="text-gray-500">Correo:</span> <span class="font-semibold text-gray-900"><?php echo h($p['customer_email']); ?></span></div>
            <div class="text-sm"><span class="text-gray-500">Teléfono:</span> <span class="font-semibold text-gray-900"><?php echo h($p['customer_phone']); ?></span></div>
            <div class="text-sm"><span class="text-gray-500">Dirección:</span> <span class="font-semibold text-gray-900"><?php echo h($p['customer_address']); ?></span></div>
          <?php else: ?>
            <div class="text-sm text-gray-700">
              Compra registrada como <span class="font-semibold">Consumidor final</span> (sin datos).
            </div>
          <?php endif; ?>
        </div>

        <div class="bg-gray-50 rounded-lg p-4 border">
          <div class="text-xs text-gray-500 font-semibold mb-2">RESUMEN</div>
          <div class="text-sm flex justify-between">
            <span class="text-gray-600">Subtotal</span>
            <span class="font-semibold text-gray-900">$<?php echo number_format((float)$p['subtotal'],2); ?></span>
          </div>
          <div class="text-sm flex justify-between">
            <span class="text-gray-600">IVA (12%)</span>
            <span class="font-semibold text-gray-900">$<?php echo number_format((float)$p['iva'],2); ?></span>
          </div>
          <div class="text-base flex justify-between mt-2 pt-2 border-t">
            <span class="font-extrabold text-gray-900">TOTAL</span>
            <span class="font-extrabold text-gray-900">$<?php echo number_format((float)$p['total'],2); ?></span>
          </div>
        </div>
      </div>

      <!-- Tabla items -->
      <div class="mt-8 overflow-x-auto">
        <table class="w-full table-fixed">
          <colgroup>
            <col />
            <col style="width: 5rem;">
            <col style="width: 7rem;">
            <col style="width: 7rem;">
          </colgroup>
          <thead>
            <tr class="text-xs text-gray-500 border-b">
              <th class="text-left font-semibold pb-2">Producto</th>
              <th class="text-center font-semibold pb-2">Cant.</th>
              <th class="text-right font-semibold pb-2">P. Unit</th>
              <th class="text-right font-semibold pb-2">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr class="border-b">
                <td class="py-3 text-sm text-gray-900 font-medium"><?php echo h($it['product_name']); ?></td>
                <td class="py-3 text-center text-sm text-gray-800 font-semibold"><?php echo (int)$it['quantity']; ?></td>
                <td class="py-3 text-right text-sm text-gray-800">$<?php echo number_format((float)$it['unit_price'],2); ?></td>
                <td class="py-3 text-right text-sm text-gray-900 font-semibold">$<?php echo number_format((float)$it['line_total'],2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Nota legal -->
      <div class="mt-8 p-4 rounded-lg border bg-yellow-50">
        <div class="text-sm font-semibold text-gray-900">Aviso importante</div>
        <p class="text-sm text-gray-700 mt-1">
          Este documento es un <b>comprobante de compra NO AUTORIZADO</b>.
          No constituye comprobante tributario autorizado por el SRI, ya que el establecimiento
          no cuenta con respaldo/validación de facturación electrónica.
        </p>
      </div>

      <div class="mt-6 text-center text-xs text-gray-500">
        Gracias por su compra.
      </div>

    </div>
  </main>
</body>
</html>
