<?php
session_start();
include('../backend/db.php');
require_once __DIR__ . '/../backend/lib/access.php';
appRequirePermission($conn, 'inventory');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Agregar producto (igual que tenías)
if (isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $price = $conn->real_escape_string($_POST['price']);
    $category = $conn->real_escape_string($_POST['category']);
    $description = $conn->real_escape_string($_POST['description']);
    $image = $_FILES['image']['name'] ?? '';

    if (!empty($image)) {
        $target = "../frontend/uploads/" . basename($image);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            header("Location: inventory.php?err=upload");
            exit();
        }
    }

    $insert_sql = "INSERT INTO products (name, price, category, description, image) 
                   VALUES ('$name', '$price', '$category', '$description', '$image')";

    if ($conn->query($insert_sql) === TRUE) {
        header("Location: inventory.php?ok=added");
        exit();
    } else {
        header("Location: inventory.php?err=add");
        exit();
    }
}

// Obtener productos
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($sql);

$pageTitle = "Inventario";
$backUrl = "index.php";
$backText = "Menú";
include("partials/header.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventario - Tienda</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.2/dist/tailwind.min.css" rel="stylesheet">

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/alerts.js"></script>
</head>

<body class="bg-gray-100">
<?php /* header ya incluido arriba */ ?>

<main class="container mx-auto p-8">
  <h2 class="text-3xl text-center mb-6 font-semibold">Inventario de Productos</h2>

  <!-- Botón para agregar -->
  <div class="text-center mb-6">
    <button id="addProductBtn" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
      Agregar Nuevo Producto
    </button>
  </div>

  <!-- Form agregar -->
  <div id="addProductForm" class="mb-8 p-6 bg-white shadow-lg rounded-lg hidden">
    <h3 class="text-2xl mb-4 font-semibold">Agregar Producto</h3>
    <form action="inventory.php" method="POST" enctype="multipart/form-data">
      <div class="mb-4">
        <label class="block text-gray-700">Nombre</label>
        <input type="text" name="name" class="w-full p-3 mt-2 border rounded-lg" required>
      </div>

      <div class="mb-4">
        <label class="block text-gray-700">Precio</label>
        <input type="number" name="price" step="0.01" class="w-full p-3 mt-2 border rounded-lg" required>
      </div>

      <div class="mb-4">
        <label class="block text-gray-700">Categoría</label>
        <input type="text" name="category" class="w-full p-3 mt-2 border rounded-lg" required>
      </div>

      <div class="mb-4">
        <label class="block text-gray-700">Descripción</label>
        <textarea name="description" class="w-full p-3 mt-2 border rounded-lg" required></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-gray-700">Imagen</label>
        <input type="file" name="image" class="w-full p-3 mt-2 border rounded-lg">
      </div>

      <button type="submit" name="add_product" class="w-full p-3 mt-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">
        Agregar Producto
      </button>
    </form>
  </div>

  <h3 class="text-2xl mb-4 font-semibold">Productos en Inventario</h3>

  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="bg-white rounded-lg shadow-lg overflow-hidden" id="prodCard-<?php echo (int)$row['id']; ?>">
        <img id="prodImg-<?php echo (int)$row['id']; ?>"
             src="../frontend/uploads/<?php echo htmlspecialchars($row['image']); ?>"
             alt="<?php echo htmlspecialchars($row['name']); ?>"
             class="w-full h-48 object-cover">

        <div class="p-4">
          <h3 id="prodName-<?php echo (int)$row['id']; ?>" class="text-xl font-semibold text-gray-700">
            <?php echo htmlspecialchars($row['name']); ?>
          </h3>

          <p class="text-lg text-gray-500">
            $<span id="prodPrice-<?php echo (int)$row['id']; ?>"><?php echo htmlspecialchars($row['price']); ?></span>
          </p>

          <p class="text-sm text-gray-400" id="prodCat-<?php echo (int)$row['id']; ?>">
            <?php echo htmlspecialchars($row['category']); ?>
          </p>

          <p class="text-xs text-gray-500 mt-2 line-clamp-2" id="prodDesc-<?php echo (int)$row['id']; ?>">
            <?php echo htmlspecialchars($row['description']); ?>
          </p>

          <div class="mt-4 flex gap-2">
            <button
              class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 btnEdit"
              data-id="<?php echo (int)$row['id']; ?>"
              data-name="<?php echo htmlspecialchars($row['name'], ENT_QUOTES); ?>"
              data-price="<?php echo htmlspecialchars($row['price'], ENT_QUOTES); ?>"
              data-category="<?php echo htmlspecialchars($row['category'], ENT_QUOTES); ?>"
              data-description="<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>"
              data-image="<?php echo htmlspecialchars($row['image'], ENT_QUOTES); ?>"
            >
              Editar
            </button>

            <button
              class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 btnDelete"
              data-id="<?php echo (int)$row['id']; ?>"
            >
              Eliminar
            </button>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</main>

<script>
  // Mostrar/Ocultar formulario agregar
  const addProductBtn = document.getElementById('addProductBtn');
  const addProductForm = document.getElementById('addProductForm');
  addProductBtn.addEventListener('click', () => addProductForm.classList.toggle('hidden'));

  // Alertas por URL (agregar producto)
  (function(){
    const params = new URLSearchParams(window.location.search);
    const ok = params.get("ok");
    const err = params.get("err");

    if (ok === "added") Alerts.toastSuccess("Producto agregado ✅");
    if (err === "upload") Alerts.toastError("Error al subir imagen");
    if (err === "add") Alerts.toastError("Error al agregar producto");

    if (ok || err) window.history.replaceState({}, document.title, window.location.pathname);
  })();

  async function productApi(action, payload = {}, fileInput = null) {
    const fd = new FormData();
    fd.append("action", action);
    Object.keys(payload).forEach(k => fd.append(k, payload[k]));
    if (fileInput && fileInput.files && fileInput.files[0]) {
      fd.append("image", fileInput.files[0]);
    }

    const res = await fetch("product_api.php", { method: "POST", body: fd });
    const text = await res.text();
    try { return JSON.parse(text); }
    catch { return { status:"error", message:"Respuesta inválida del servidor", raw:text }; }
  }

  // Editar con SweetAlert modal
  document.querySelectorAll(".btnEdit").forEach(btn => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.id;

      const current = {
        name: btn.dataset.name,
        price: btn.dataset.price,
        category: btn.dataset.category,
        description: btn.dataset.description,
        image: btn.dataset.image
      };

      const { value: formValues } = await Swal.fire({
        title: "Editar producto",
        html:
          `<div class="text-left space-y-3">
            <label class="block text-sm text-gray-600">Nombre</label>
            <input id="sw_name" class="swal2-input" value="${current.name}" placeholder="Nombre">

            <label class="block text-sm text-gray-600">Precio</label>
            <input id="sw_price" type="number" step="0.01" class="swal2-input" value="${current.price}" placeholder="Precio">

            <label class="block text-sm text-gray-600">Categoría</label>
            <input id="sw_category" class="swal2-input" value="${current.category}" placeholder="Categoría">

            <label class="block text-sm text-gray-600">Descripción</label>
            <textarea id="sw_description" class="swal2-textarea" placeholder="Descripción">${current.description}</textarea>

            <label class="block text-sm text-gray-600">Imagen (opcional)</label>
            <input id="sw_image" type="file" class="swal2-file" accept=".jpg,.jpeg,.png,.webp">
           </div>`,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: "Guardar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#16a34a",
        cancelButtonColor: "#6b7280",
        preConfirm: () => {
          const name = document.getElementById("sw_name").value.trim();
          const price = document.getElementById("sw_price").value.trim();
          const category = document.getElementById("sw_category").value.trim();
          const description = document.getElementById("sw_description").value.trim();
          const imageInput = document.getElementById("sw_image");

          if (!name || !price || Number(price) <= 0 || !category || !description) {
            Swal.showValidationMessage("Completa todos los campos (precio > 0).");
            return false;
          }

          return { id, name, price, category, description, imageInput };
        }
      });

      if (!formValues) return;

      const r = await productApi("update", {
        id: formValues.id,
        name: formValues.name,
        price: formValues.price,
        category: formValues.category,
        description: formValues.description
      }, formValues.imageInput);

      if (r.status !== "success") {
        Alerts.modalError("No se pudo guardar", r.message || "Error");
        return;
      }

      // Actualizar UI sin recargar
      const p = r.product;

      document.getElementById(`prodName-${p.id}`).textContent = p.name;
      document.getElementById(`prodPrice-${p.id}`).textContent = p.price;
      document.getElementById(`prodCat-${p.id}`).textContent = p.category;
      document.getElementById(`prodDesc-${p.id}`).textContent = p.description;

      if (p.image) {
        const img = document.getElementById(`prodImg-${p.id}`);
        img.src = `../frontend/uploads/${p.image}?v=${Date.now()}`;
      }

      // actualizar dataset para próxima edición
      btn.dataset.name = p.name;
      btn.dataset.price = p.price;
      btn.dataset.category = p.category;
      btn.dataset.description = p.description;
      btn.dataset.image = p.image;

      Alerts.toastSuccess("Producto actualizado ✅");
    });
  });

  // Eliminar con confirm SweetAlert + AJAX
  document.querySelectorAll(".btnDelete").forEach(btn => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.id;

      Alerts.confirmDelete("Se eliminará el producto del inventario.", async () => {
        const r = await productApi("delete", { id });

        if (r.status !== "success") {
          Alerts.modalError("No se pudo eliminar", r.message || "Error");
          return;
        }

        const card = document.getElementById(`prodCard-${id}`);
        if (card) card.remove();

        Alerts.toastInfo("Producto eliminado");
      });
    });
  });
</script>

</body>
</html>
