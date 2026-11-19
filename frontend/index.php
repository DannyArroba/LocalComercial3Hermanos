<?php
session_start();
include('../backend/db.php');
require_once __DIR__ . '/../backend/lib/access.php';
appRequirePermission($conn, 'sales');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Categorías
$category_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> ''";
$category_result = $conn->query($category_sql);

// Filtro (por defecto: todos)
$selectedCategory = $_GET['category'] ?? 'todas';

if ($selectedCategory !== 'todas') {
    $safeCat = $conn->real_escape_string($selectedCategory);
    $sql = "SELECT * FROM products WHERE category = '$safeCat'";
} else {
    $sql = "SELECT * FROM products";
}
$result = $conn->query($sql);

// Contador carrito
$cartCount = 0;
foreach ($_SESSION['cart'] as $it) $cartCount += (int)$it['quantity'];

$pageTitle = "Menú principal";
$backUrl = null; // en index NO queremos volver
include("partials/header.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mi Tienda</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.2/dist/tailwind.min.css" rel="stylesheet">

  <!-- SweetAlert + Alerts helper -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/alerts.js"></script>
</head>

<body class="bg-gray-100">
<?php /* header ya incluido arriba */ ?>

<main class="container mx-auto p-8">
  <!-- Acciones arriba -->
  <div class="text-center mb-8">
    <a href="inventory.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg mx-2 inline-block">Administrar Productos</a>
    <a href="purchase-history.php" class="bg-green-600 text-white px-6 py-3 rounded-lg mx-2 inline-block">Historial de Compras</a>
  </div>

  <h2 class="text-3xl text-center mb-6 font-semibold">Productos Disponibles</h2>

  <!-- Filtro categorías -->
  <div class="mb-8 text-center">
    <select class="p-3 rounded-lg border"
            onchange="window.location.href=this.value;">
      <option value="index.php?category=todas" <?php echo ($selectedCategory==='todas')?'selected':''; ?>>Todas</option>
      <?php if($category_result): ?>
        <?php while ($cat = $category_result->fetch_assoc()): ?>
          <?php $c = $cat['category']; ?>
          <option value="index.php?category=<?php echo htmlspecialchars($c); ?>"
            <?php echo ($selectedCategory===$c)?'selected':''; ?>>
            <?php echo htmlspecialchars($c); ?>
          </option>
        <?php endwhile; ?>
      <?php endif; ?>
    </select>
  </div>

  <!-- Productos -->
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <img src="../frontend/uploads/<?php echo htmlspecialchars($row['image']); ?>"
             alt="<?php echo htmlspecialchars($row['name']); ?>"
             class="w-full h-48 object-cover">

        <div class="p-4">
          <h3 class="text-xl font-semibold text-gray-700"><?php echo htmlspecialchars($row['name']); ?></h3>
          <p class="text-lg text-gray-500">$<?php echo htmlspecialchars($row['price']); ?></p>
          <p class="text-sm text-gray-400"><?php echo htmlspecialchars($row['category']); ?></p>

          <div class="mt-4">
            <form class="addToCartForm" data-id="<?php echo (int)$row['id']; ?>">
              <div class="flex items-center gap-2">
                <input type="number" name="quantity" min="1" value="1"
                       class="p-2 rounded-lg border w-24"
                       required>
                <button type="submit"
                        class="flex-1 bg-blue-500 text-white px-4 py-2 rounded-lg text-center hover:bg-blue-600">
                  Añadir a la Cesta
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    <?php endwhile; ?>
  </div>
</main>

<!-- Botón flotante carrito -->
<div class="fixed bottom-8 right-8 z-50">
  <button id="cartButton"
          class="relative bg-blue-600 text-white rounded-full p-4 shadow-lg hover:bg-blue-700">
    <span class="text-2xl">🛒</span>
    <span id="cartBadge"
          class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold rounded-full px-2 py-1">
      <?php echo (int)$cartCount; ?>
    </span>
  </button>
</div>

<!-- Modal carrito -->
<div id="cartModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden">
  <div class="bg-white p-6 rounded-lg w-[92%] max-w-lg">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">Tu Carrito</h2>
      <button id="closeCart"
              class="w-9 h-9 rounded-full hover:bg-gray-100 text-gray-600">✕</button>
    </div>

    <!-- contenido carrito -->
    <div id="cartBody" class="mb-4">
      <div class="text-gray-500 text-sm">Cargando...</div>
    </div>

    <!-- Totales -->
    <div class="border-t pt-4 space-y-2 text-sm">
      <div class="flex justify-between">
        <span class="text-gray-600">Total sin IVA</span>
        <span id="subtotalTxt" class="font-semibold text-gray-800">$0.00</span>
      </div>
      <div class="flex justify-between">
        <span class="text-gray-600">IVA (12%)</span>
        <span id="ivaTxt" class="font-semibold text-gray-800">$0.00</span>
      </div>
      <div class="flex justify-between text-base">
        <span class="text-gray-800 font-bold">Total con IVA</span>
        <span id="totalTxt" class="font-bold text-gray-900">$0.00</span>
      </div>
    </div>

    <div class="mt-5">
      <!-- Botón pagar con confirm -->
      <button id="btnCheckout"
         class="block bg-green-600 text-white px-6 py-3 rounded-lg w-full text-center hover:bg-green-700">
        Pagar
      </button>
    </div>
  </div>
</div>

<script>
  const cartModal = document.getElementById('cartModal');
  const cartButton = document.getElementById('cartButton');
  const closeCart = document.getElementById('closeCart');

  const cartBody = document.getElementById('cartBody');
  const cartBadge = document.getElementById('cartBadge');

  const subtotalTxt = document.getElementById('subtotalTxt');
  const ivaTxt = document.getElementById('ivaTxt');
  const totalTxt = document.getElementById('totalTxt');

  const btnCheckout = document.getElementById('btnCheckout');

  function money(n){
    return "$" + (Number(n || 0)).toFixed(2);
  }

  async function cartApi(action, payload = {}) {
    const formData = new FormData();
    formData.append('action', action);
    Object.keys(payload).forEach(k => formData.append(k, payload[k]));

    const res = await fetch('cart_api.php', { method: 'POST', body: formData });

    // Evita crash si el server devuelve texto no JSON
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      return { status: "error", message: "Respuesta inválida del servidor", raw: text };
    }
  }

  // ✅ Detecta éxito aunque tu API no use status:"success"
  function apiIsSuccess(r) {
    if (!r) return false;
    if (r.status === "error") return false;

    if (r.status === "success" || r.status === "ok") return true;
    if (r.success === true || r.ok === true) return true;
    if (r.result === "success") return true;

    // Señales de que devolvió estructura válida
    if (typeof r.cartCount !== "undefined") return true;
    if (typeof r.subtotal !== "undefined") return true;
    if (typeof r.cartHtml !== "undefined") return true;

    return false;
  }

  async function refreshCartUI() {
    const data = await cartApi('get');

    if (data.status === "error") {
      cartBody.innerHTML = `<div class="text-red-600 text-sm">${data.message || "Error cargando carrito"}</div>`;
      return;
    }

    cartBody.innerHTML = data.cartHtml || "";
    cartBadge.textContent = data.cartCount || 0;

    subtotalTxt.textContent = money(data.subtotal);
    ivaTxt.textContent = money(data.iva);
    totalTxt.textContent = money(data.total);

    // Re-asignar eventos porque el HTML se re-renderiza
    document.querySelectorAll('.cart-remove').forEach(btn => {
      btn.addEventListener('click', async () => {
        const r = await cartApi('remove', { product_id: btn.dataset.id });

        if (apiIsSuccess(r)) {
          Alerts.toastInfo("Producto eliminado ✨");
        } else {
          Alerts.toastError(r.message || "No se pudo eliminar");
        }

        await refreshCartUI();
      });
    });

    document.querySelectorAll('.cart-inc').forEach(btn => {
      btn.addEventListener('click', async () => {
        const r = await cartApi('inc', { product_id: btn.dataset.id });

        if (apiIsSuccess(r)) {
          Alerts.toastSuccess("Cantidad aumentada ✅");
        } else {
          Alerts.toastError(r.message || "No se pudo aumentar");
        }

        await refreshCartUI();
      });
    });

    document.querySelectorAll('.cart-dec').forEach(btn => {
      btn.addEventListener('click', async () => {
        const r = await cartApi('dec', { product_id: btn.dataset.id });

        if (apiIsSuccess(r)) {
          Alerts.toastInfo("Cantidad disminuida");
        } else {
          Alerts.toastError(r.message || "No se pudo disminuir");
        }

        await refreshCartUI();
      });
    });
  }

  // Abrir modal
  cartButton.addEventListener('click', async () => {
    cartModal.classList.remove('hidden');
    await refreshCartUI();
  });

  // Cerrar modal
  closeCart.addEventListener('click', () => {
    cartModal.classList.add('hidden');
  });

  // ✅ Confirm SweetAlert al pagar
  btnCheckout.addEventListener('click', async () => {
    const data = await cartApi('get');
    if (!data.cartCount || Number(data.cartCount) <= 0) {
      Alerts.modalError("Carrito vacío", "Agrega productos antes de pagar.");
      return;
    }
    Alerts.confirmCheckout(() => {
      window.location.href = "checkout.php";
    });
  });

  // Agregar al carrito SIN recargar
  document.querySelectorAll('.addToCartForm').forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const productId = form.dataset.id;
      const qty = form.querySelector('input[name="quantity"]').value || 1;

      const r = await cartApi('add', { product_id: productId, quantity: qty });

      if (apiIsSuccess(r)) {
        Alerts.toastSuccess("Producto agregado al carrito ✅");
      } else {
        Alerts.toastError(r.message || "No se pudo agregar");
      }

      // actualizar badge sin mover pantalla
      const data = await cartApi('get');
      cartBadge.textContent = data.cartCount || 0;
    });
  });

  // ✅ Toast login exitoso (si vienes de login.php -> index.php?ok=login)
  (function(){
    const params = new URLSearchParams(window.location.search);
    if (params.get("ok") === "login") {
      Alerts.toastSuccess("Inicio de sesión exitoso ✅");
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  })();
</script>

</body>
</html>
