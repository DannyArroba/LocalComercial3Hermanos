<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function cart_find_index($product_id) {
    foreach ($_SESSION['cart'] as $i => $item) {
        if ((string)$item['product_id'] === (string)$product_id) return $i;
    }
    return -1;
}

function cart_count_items() {
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int)$item['quantity'];
    }
    return $count;
}

function cart_render_html($conn) {
    $total = 0;

    if (empty($_SESSION['cart'])) {
        return [
            'html' => "<div class='text-gray-500 text-sm'>Tu carrito está vacío.</div>",
            'subtotal' => 0,
            'iva' => 0,
            'total' => 0
        ];
    }

    $rows = "";

    foreach ($_SESSION['cart'] as $item) {
        $pid = $item['product_id'];
        $qty = (int)$item['quantity'];

        $q = $conn->query("SELECT id, name, price FROM products WHERE id = '". $conn->real_escape_string($pid) ."' LIMIT 1");
        if (!$q || $q->num_rows === 0) continue;

        $p = $q->fetch_assoc();
        $name = htmlspecialchars($p['name']);
        $price = (float)$p['price'];
        $line = $price * $qty;
        $total += $line;

        // fila: nombre | qty (con - +) | precio | x
        $rows .= "
        <tr class='border-b'>
            <td class='py-2 pr-2 text-sm text-gray-800 align-top'>
                <div class='font-medium leading-tight'>$name</div>
            </td>

            <td class='py-2 w-28 align-top'>
                <div class='flex items-center justify-center gap-2'>
                    <button type='button'
                        class='cart-dec w-8 h-8 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100'
                        data-id='{$p['id']}'>−</button>

                    <span class='min-w-[22px] text-center text-sm font-semibold text-gray-800'>$qty</span>

                    <button type='button'
                        class='cart-inc w-8 h-8 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100'
                        data-id='{$p['id']}'>+</button>
                </div>
            </td>

            <td class='py-2 w-28 text-right text-sm font-semibold text-gray-800 align-top'>
                $" . number_format($line, 2) . "
            </td>

            <td class='py-2 w-10 text-right align-top'>
                <button type='button'
                    class='cart-remove inline-flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 text-gray-500'
                    title='Quitar'
                    data-id='{$p['id']}'>✕</button>
            </td>
        </tr>";
    }

    $iva = $total * 0.12;
    $withIva = $total + $iva;

    $html = "
    <div class='overflow-x-auto'>
      <table class='w-full table-fixed'>
        <colgroup>
          <col />
          <col style='width: 7rem;' />
          <col style='width: 7rem;' />
          <col style='width: 2.5rem;' />
        </colgroup>
        <thead>
          <tr class='text-xs text-gray-500'>
            <th class='text-left font-semibold pb-2'>Producto</th>
            <th class='text-center font-semibold pb-2'>Cant.</th>
            <th class='text-right font-semibold pb-2'>Precio</th>
            <th class='text-right font-semibold pb-2'></th>
          </tr>
        </thead>
        <tbody>$rows</tbody>
      </table>
    </div>";

    return [
        'html' => $html,
        'subtotal' => $total,
        'iva' => $iva,
        'total' => $withIva
    ];
}

include('../backend/db.php'); // usa tu conexión
require_once __DIR__ . '/../backend/lib/access.php';
appRequirePermission($conn, 'sales');

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $product_id = $_POST['product_id'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($quantity < 1) $quantity = 1;

    $idx = cart_find_index($product_id);
    if ($idx >= 0) {
        $_SESSION['cart'][$idx]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][] = ['product_id' => $product_id, 'quantity' => $quantity];
    }

    $render = cart_render_html($conn);
    echo json_encode([
        'ok' => true,
        'cartCount' => cart_count_items(),
        'cartHtml' => $render['html'],
        'subtotal' => $render['subtotal'],
        'iva' => $render['iva'],
        'total' => $render['total']
    ]);
    exit;
}

if ($action === 'remove') {
    $product_id = $_POST['product_id'] ?? '';
    $idx = cart_find_index($product_id);
    if ($idx >= 0) {
        array_splice($_SESSION['cart'], $idx, 1);
    }

    $render = cart_render_html($conn);
    echo json_encode([
        'ok' => true,
        'cartCount' => cart_count_items(),
        'cartHtml' => $render['html'],
        'subtotal' => $render['subtotal'],
        'iva' => $render['iva'],
        'total' => $render['total']
    ]);
    exit;
}

if ($action === 'inc' || $action === 'dec') {
    $product_id = $_POST['product_id'] ?? '';
    $idx = cart_find_index($product_id);

    if ($idx >= 0) {
        $q = (int)$_SESSION['cart'][$idx]['quantity'];
        if ($action === 'inc') $q++;
        if ($action === 'dec') $q--;

        if ($q <= 0) {
            array_splice($_SESSION['cart'], $idx, 1);
        } else {
            $_SESSION['cart'][$idx]['quantity'] = $q;
        }
    }

    $render = cart_render_html($conn);
    echo json_encode([
        'ok' => true,
        'cartCount' => cart_count_items(),
        'cartHtml' => $render['html'],
        'subtotal' => $render['subtotal'],
        'iva' => $render['iva'],
        'total' => $render['total']
    ]);
    exit;
}

$render = cart_render_html($conn);
echo json_encode([
    'ok' => true,
    'cartCount' => cart_count_items(),
    'cartHtml' => $render['html'],
    'subtotal' => $render['subtotal'],
    'iva' => $render['iva'],
    'total' => $render['total']
]);
exit;
