<?php
/** Local-only historical fixture importer. Never emits invoices to SRI or sends contacts messages. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/lib/contact_data.php';
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
const BATCH_KEY = 'historial-ficticio-2026-mayo-v1';
const FIRST_DAY = '2026-05-01';
const LAST_DAY = '2026-11-18';
$mode = $argv[1] ?? '';
$directory = $argv[2] ?? '';
if (!in_array($mode, ['--plan', '--apply', '--verify'], true) || !is_dir($directory)) {
    throw new RuntimeException('Uso: php scripts/historial_demo.php --plan|--apply|--verify DIRECTORIO_RESPALDO');
}
$planFile = $directory . '/plan-historial.json';

function rows($table) {
    global $conn;
    return $conn->query("SELECT * FROM `$table` ORDER BY id")->fetch_all(MYSQLI_ASSOC);
}
function cents($value) { return (int)round((float)$value * 100); }
function money($value) { return number_format($value / 100, 2, '.', ''); }
function normalizedTime($value) {
    if (!$value) return null;
    [$date, $time] = explode(' ', $value);
    [$h, $m, $s] = array_map('intval', explode(':', $time));
    $seconds = 8 * 3600 + intdiv($h * 3600 + $m * 60 + $s, 2);
    return $date . ' ' . sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
}
function snapshot() {
    $snapshot = [];
    foreach (['products','customers','purchases','purchase_items','stock_purchases','stock_purchase_items','sales_returns','sales_return_items','inventory_logs','users','suppliers','sales','sale_items','invoices'] as $table) {
        $snapshot[$table] = rows($table);
    }
    return $snapshot;
}
function fingerprint($data) { return hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)); }
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function caseSize($p) {
    $name = mb_strtolower($p['name'], 'UTF-8');
    if (str_contains($name, 'quintal') || str_contains($name, '100 lb')) return 1;
    if (preg_match('/(?:10 kg|25 lb)/', $name)) return 2;
    if (str_contains($name, '5 kg') || $p['category'] === 'Tortas heladas') return 3;
    if ($p['category'] === 'Cervezas') return 12;
    if ($p['category'] === 'Carnes') return 6;
    if (str_contains($name, 'cubeta')) return 6;
    if (str_contains($name, 'polito')) return 25;
    if (str_contains($name, 'vasito')) return 20;
    if (str_contains($name, '6 l') || str_contains($name, 'galón')) return 4;
    if (in_array($p['category'], ['Gaseosas','Aguas','Maltas'], true)) return preg_match('/(?:300|330|500|625) ml/', $name) ? 24 : 6;
    if (in_array($p['category'], ['Leches','Yogures','Yogures con cereal','Quesos y embutidos'], true)) return 12;
    if (in_array($p['category'], ['Chocolates','Gomitas y caramelos','Galletas','Snacks','Sazonadores y sopas'], true)) return cents($p['price']) <= 100 ? 24 : 12;
    return 12;
}
function weightedProduct($products, $used, $cheap, $theme) {
    $options = []; $sum = 0;
    foreach ($products as $id => $p) {
        if (isset($used[$id]) || ($cheap && cents($p['price']) > 130)) continue;
        $weight = in_array($p['category'], $theme, true) ? 8 : 1;
        if (cents($p['price']) > 1000) $weight *= 0.08;
        elseif (cents($p['price']) > 500) $weight *= 0.4;
        $sum += max(1, (int)round($weight * 100));
        $options[] = [$id, $sum];
    }
    check($sum > 0, 'No hay suficientes productos distintos para el carrito.');
    $draw = mt_rand(1, $sum);
    foreach ($options as [$id, $limit]) if ($draw <= $limit) return $products[$id];
}
function makePlan($before) {
    mt_srand(20261118);
    $products = array_column($before['products'], null, 'id');
    $users = array_column($before['users'], 'id');
    $suppliers = array_column($before['suppliers'], 'id');
    foreach ($products as $p) {
        check(in_array($p['supplier_id'], $suppliers), 'Producto sin proveedor.');
        check(cents($p['price']) > 0 && cents($p['cost_price']) > 0, 'Producto sin precio o costo.');
    }
    $updates = []; $events = []; $daily = []; $occupied = [];
    $opening = array_map(fn($p) => (int)$p['stock'], $products);
    foreach ($before['purchase_items'] as $item) $opening[$item['product_id']] = ($opening[$item['product_id']] ?? 0) + (int)$item['quantity'];
    $purchases = array_column($before['purchases'], null, 'id');
    $supplies = array_column($before['stock_purchases'], null, 'id');
    foreach ($before['stock_purchase_items'] as $item) if ($supplies[$item['purchase_id']]['status'] === 'RECIBIDO') $opening[$item['product_id']] -= (int)$item['quantity'];
    foreach ($before['sales_return_items'] as $item) $opening[$item['product_id']] -= (int)$item['quantity'];
    foreach ($opening as $n) check($n >= 0, 'Saldo inicial negativo; se requiere revisión manual.');
    foreach ($before['purchases'] as $row) {
        $date = normalizedTime($row['created_at']);
        $updates[] = ['table'=>'purchases','id'=>(int)$row['id'],'values'=>['created_at'=>$date]];
        $events[] = ['kind'=>'existing_sale','time'=>$date,'id'=>(int)$row['id'],
            'items'=>array_values(array_filter($before['purchase_items'], fn($i) => $i['purchase_id'] === $row['id']))];
        $day = substr($date, 0, 10);
        $daily[$day] = ($daily[$day] ?? 0) + 1;
        $occupied[$date] = true;
    }
    foreach ($before['stock_purchases'] as $row) {
        $values = [];
        foreach (['created_at','paid_at','received_at','cancelled_at','refunded_at'] as $field) $values[$field] = normalizedTime($row[$field]);
        $updates[] = ['table'=>'stock_purchases','id'=>(int)$row['id'],'values'=>$values];
        if ($row['status'] === 'RECIBIDO') $events[] = ['kind'=>'existing_supply','time'=>$values['received_at'],'id'=>(int)$row['id'],
            'items'=>array_values(array_filter($before['stock_purchase_items'], fn($i) => $i['purchase_id'] === $row['id']))];
    }
    foreach ($before['sales_returns'] as $row) {
        $date = normalizedTime($row['created_at']);
        $updates[] = ['table'=>'sales_returns','id'=>(int)$row['id'],'values'=>['created_at'=>$date]];
        $events[] = ['kind'=>'existing_return','time'=>$date,'id'=>(int)$row['id'],
            'items'=>array_values(array_filter($before['sales_return_items'], fn($i) => $i['return_id'] === $row['id']))];
    }
    foreach ($before['inventory_logs'] as $row) $updates[] = ['table'=>'inventory_logs','id'=>(int)$row['id'],'values'=>['created_at'=>normalizedTime($row['created_at'])]];
    // Legacy unused sales tables, if present, retain their own original accounting values.
    foreach ($before['sales'] as $row) $updates[] = ['table'=>'sales','id'=>(int)$row['id'],'values'=>['sale_date'=>normalizedTime($row['sale_date'])]];
    foreach ($before['invoices'] as $row) $updates[] = ['table'=>'invoices','id'=>(int)$row['id'],'values'=>['invoice_date'=>normalizedTime($row['invoice_date'])]];
    // Only replace explicit example.com placeholders; preserve existing real contact details.
    $emailMap = [];
    foreach (['customers','purchases'] as $table) foreach ($before[$table] as $row) {
        $field = $table === 'customers' ? 'email' : 'customer_email';
        $email = $row[$field] ?? '';
        if (preg_match('/@example\.com$/i', $email)) {
            $newEmail = $emailMap[$email] ??= preg_replace('/@example\.com$/i', '@gmail.com', $email);
            $updates[] = ['table'=>$table,'id'=>(int)$row['id'],'values'=>[$field=>$newEmail]];
        }
    }
    $legacyCustomers=[]; $customerLinks=[];
    $existingCustomers=array_column($before['customers'],null,'id_number');
    foreach($before['purchases'] as $sale) if($sale['purchase_type']==='FACTURA') {
        $first=normalizedTime($sale['created_at']);
        if($sale['customer_id']) continue;
        $ci=$sale['customer_idnumber'];
        if(isset($existingCustomers[$ci])) {
            $customerLinks[$sale['id']]=['existing_id'=>(int)$existingCustomers[$ci]['id']];
        } else {
            if(!isset($legacyCustomers[$ci])) $legacyCustomers[$ci]=[
                'name'=>$sale['customer_name'],'id_number'=>$ci,'email'=>$emailMap[$sale['customer_email']]??$sale['customer_email'],
                'phone'=>$sale['customer_phone'],'address'=>$sale['customer_address'],'created_at'=>$first,'updated_at'=>$first];
            if($first<$legacyCustomers[$ci]['created_at'])$legacyCustomers[$ci]['created_at']=$first;
            $customerLinks[$sale['id']]=['legacy_key'=>$ci];
        }
    }
    foreach($before['customers'] as $customer) {
        $dates=[];
        foreach($before['purchases'] as $sale) if($sale['customer_id']===$customer['id'] || $sale['customer_idnumber']===$customer['id_number']) $dates[]=normalizedTime($sale['created_at']);
        if($dates && min($dates)<$customer['created_at']) $updates[]=['table'=>'customers','id'=>(int)$customer['id'],
            'values'=>['created_at'=>min($dates),'updated_at'=>max($customer['updated_at'],max($dates))]];
    }
    $firstNames = ['María Fernanda','Luis Fernando','José Luis','Ana Lucía','Carlos Andrés','Diana Carolina','Jorge Eduardo','Patricia Elizabeth','Juan Carlos','Mónica Alexandra','Diego Alejandro','Paola Estefanía','Miguel Ángel','Rosa Elena','Cristian David','Andrea Gabriela','Byron Javier','Daniela Alejandra','Wilson Alberto','Karla Vanessa','Kevin Alexander','Sandra Patricia','Freddy Orlando','Mayra Jacqueline','Pedro Antonio','Jéssica Tatiana','Víctor Manuel','Silvia Lorena','Ángel Gabriel','Gloria Isabel'];
    $surnames = ['Grefa','Aguinda','Tanguila','Villacís','Zambrano','Vera','Quishpe','Chimbo','Paredes','Tapuy','Mendoza','Cevallos','Moreta','Alvarado','Guamán','Lema','Sánchez','Cárdenas','Chávez','Rivas','Gualinga','Maldonado','López','Pillajo','Torres','Salazar','Muñoz','Castillo','Andrade','Poma'];
    $addresses = ['Av. 9 de Octubre y Quito','Barrio Santa Rosa','Barrio Las Américas','Av. Alejandro Labaka y Río Napo','Barrio 30 de Abril','Av. Padre Miguel Gamboa','Barrio Flor de Oriente','Barrio Con Hogar','Calle Amazonas y Eugenio Espejo','Barrio Paraíso Amazónico','Barrio Los Sauces','Av. Camilo de Torrano','Barrio Los Rosales','Barrio Unión y Progreso'];
    $customers = []; $identifiers = array_fill_keys(array_column($before['customers'], 'id_number'), true);
    $usedPhones = array_fill_keys(array_filter(array_column($before['customers'], 'phone')), true);
    for ($i = 0; $i < 210; $i++) {
        $first = $firstNames[$i % count($firstNames)];
        $surname = $surnames[(intdiv($i, count($firstNames)) + $i * 7) % count($surnames)];
        $other = $surnames[($i * 11 + 13) % count($surnames)];
        do {
            $prefix = $i % 5 !== 0 ? '22' : ['17','09','18','16','15','21'][$i % 6];
            $base = $prefix . mt_rand(0, 5) . sprintf('%06d', mt_rand(1, 999999));
            $sum = 0;
            for ($j = 0; $j < 9; $j++) { $v = (int)$base[$j] * ($j % 2 === 0 ? 2 : 1); $sum += $v > 9 ? $v - 9 : $v; }
            // Deliberately invalid checksum: these are fixture identifiers, never real CIs.
            $ci = $base . (((10 - $sum % 10) % 10 + 1) % 10);
        } while (isset($identifiers[$ci]));
        $identifiers[$ci] = true;
        $slug = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', explode(' ', $first)[0] . '.' . $surname));
        $customers[] = ['name'=>"$first $surname $other", 'id_number'=>$ci,
            'email'=> $slug . '@' . ($i % 4 === 0 ? 'hotmail.com' : 'gmail.com'),
            'phone'=>fixturePhone($usedPhones,fn($min,$max)=>mt_rand($min,$max)),
            'address'=>$addresses[$i % count($addresses)] . ', El Coca', 'first_purchase'=>null];
    }
    $themes = [
        ['Snacks','Galletas','Gaseosas','Gomitas y caramelos','Chocolates','Maltas','Helados'],
        ['Arroces','Aceites y grasas','Granos y cereales','Conservas','Pastas y harinas','Sazonadores y sopas'],
        ['Leches','Yogures','Panadería','Lácteos y huevos','Café e infusiones','Cacao y bebidas en polvo'],
        ['Limpieza del hogar','Cuidado personal','Bebés','Quesos y embutidos','Condimentos y salsas'],
        ['Aguas','Gaseosas','Cervezas','Snacks','Frutos secos y bocaditos','Carnes'],
    ];
    $last = new DateTimeImmutable(LAST_DAY);
    for ($date = new DateTimeImmutable(FIRST_DAY); $date <= $last; $date = $date->modify('+1 day')) {
        $day = $date->format('Y-m-d');
        $dow = (int)$date->format('N');
        $target = $dow >= 5 ? mt_rand(26, 42) : mt_rand(15, 33);
        if (in_array((int)$date->format('j'), [1,15,30], true)) $target = min(42, $target + mt_rand(2, 5));
        $existing = $daily[$day] ?? 0;
        check($existing <= $target, 'Demasiadas ventas preexistentes para el rango diario.');
        for ($n = $existing; $n < $target; $n++) {
            do {
                $hour = mt_rand(1, 100) <= 55 ? [8,9,12,13,17,18,19][mt_rand(0,6)] : mt_rand(8,19);
                $time = $day . ' ' . sprintf('%02d:%02d:%02d', $hour, mt_rand(1,58), mt_rand(5,55));
                $conflict = false;
                for ($gap = -3; $gap <= 3; $gap++) if (isset($occupied[date('Y-m-d H:i:s', strtotime($time) + $gap)])) $conflict = true;
            } while ($conflict);
            $occupied[$time] = true;
            $cheap = mt_rand(1,100) <= 48;
            $count = $cheap ? mt_rand(5,7) : mt_rand(8,14);
            $theme = $themes[mt_rand(0,count($themes)-1)];
            $used = []; $items = []; $subtotal = 0; $iva = 0;
            for ($k = 0; $k < $count; $k++) {
                $p = weightedProduct($products, $used, $cheap, $theme);
                $used[$p['id']] = true;
                $q = $cheap ? 1 : (mt_rand(1,100) <= 82 ? 1 : mt_rand(2,3));
                if (cents($p['price']) >= 1000) $q = 1;
                $base = cents($p['price']) * $q;
                $tax = (int)$p['applies_iva'] === 1 ? (int)round($base * 15 / 100) : 0;
                $items[] = ['product_id'=>(int)$p['id'],'product_name'=>$p['name'],'quantity'=>$q,
                    'unit_price'=>$p['price'],'unit_cost'=>$p['cost_price'], 'tax_rate'=>$p['applies_iva'] ? '15.00' : '0.00',
                    'tax_amount'=>money($tax),'line_total'=>money($base+$tax)];
                $subtotal += $base; $iva += $tax;
            }
            $total = $subtotal + $iva;
            $customerIndex = null;
            if ($total >= 1300 || mt_rand(1,100) <= 14) {
                $customerIndex = mt_rand(0,count($customers)-1);
                if (!$customers[$customerIndex]['first_purchase'] || $time < $customers[$customerIndex]['first_purchase']) $customers[$customerIndex]['first_purchase'] = $time;
            }
            $cash = mt_rand(1,100) <= 86;
            $received = $cash ? (mt_rand(1,100) <= 23 ? $total : (int)(ceil($total / ($total <= 1000 ? 100 : 500)) * ($total <= 1000 ? 100 : 500))) : null;
            $events[] = ['kind'=>'new_sale','time'=>$time,'items'=>$items,'user_id'=>(int)$users[$hour < 14 ? 0 : count($users)-1],
                'customer_index'=>$customerIndex,'purchase_type'=>$customerIndex !== null ? 'FACTURA' : 'CONSUMIDOR_FINAL',
                'payment_method'=>$cash ? 'EFECTIVO' : 'TRANSFERENCIA', 'subtotal'=>money($subtotal),'iva'=>money($iva),'total'=>money($total),
                'amount_received'=>$cash ? money($received) : null,'change_amount'=>$cash ? money($received-$total) : null,
                'transfer_reference'=>$cash ? null : 'TRX-'.str_replace('-','',$day).'-'.sprintf('%03d',$n+1)];
        }
        $daily[$day] = $target;
    }
    usort($events, fn($a,$b) => strcmp($a['time'],$b['time']) ?: strcmp($a['kind'],$b['kind']));
    $balance = $opening; $ledger = []; $allEvents = []; $supplyCount = 0;
    $firstTime = min(array_column($events, 'time'));
    $openingTime = substr($firstTime,0,10) . ' 08:00:00';
    foreach ($balance as $id=>$quantity) $ledger[] = ['product_id'=>(int)$id,'kind'=>'OPENING','quantity'=>$quantity,'balance'=>$quantity,'time'=>$openingTime,'event'=>null,'item'=>null];
    foreach ($events as $event) {
        $isSale = in_array($event['kind'], ['existing_sale','new_sale'], true);
        foreach ($event['items'] as $itemIndex => $item) {
            $id = (int)$item['product_id']; $q = (int)$item['quantity'];
            if ($isSale && isset($products[$id])) {
                $p = $products[$id];
                $bulk = caseSize($p) <= 3;
                $floor = $bulk ? 0 : 4;
                if ($balance[$id] - $q <= $floor) {
                    $needed = $q + ($bulk ? 1 : mt_rand(12,22)) - $balance[$id];
                    $pack = caseSize($p);
                    $add = max($pack, (int)(ceil($needed / $pack) * $pack));
                    $saleTimestamp = strtotime($event['time']);
                    $supply = ['kind'=>'new_supply','time'=>date('Y-m-d H:i:s',$saleTimestamp-1),
                        'created_at'=>date('Y-m-d H:i:s',$saleTimestamp-60), 'paid_at'=>date('Y-m-d H:i:s',$saleTimestamp-30),
                        'user_id'=>(int)$users[0], 'supplier_id'=>(int)$p['supplier_id'],
                        'total'=>money(cents($p['cost_price'])*$add),
                        'items'=>[['product_id'=>$id,'quantity'=>$add,'cost_price'=>$p['cost_price'],'pack_size'=>$pack]]];
                    $supplyIndex = count($allEvents); $allEvents[] = $supply; $supplyCount++;
                    $balance[$id] += $add;
                    $ledger[] = ['product_id'=>$id,'kind'=>'SUPPLY','quantity'=>$add,'balance'=>$balance[$id],'time'=>$supply['time'],'event'=>$supplyIndex,'item'=>0];
                }
            }
        }
        $eventIndex = count($allEvents); $allEvents[] = $event;
        foreach ($event['items'] as $itemIndex=>$item) {
            $id = (int)$item['product_id'];
            $quantity = (int)$item['quantity'] * ($isSale ? -1 : 1);
            $balance[$id] += $quantity;
            check($balance[$id] >= 0, 'Stock negativo al reproducir la cronología.');
            $ledger[] = ['product_id'=>$id,'kind'=>$isSale ? 'SALE' : ($event['kind']==='existing_return' ? 'RETURN' : 'SUPPLY'),
                'quantity'=>$quantity,'balance'=>$balance[$id],'time'=>$event['time'],'event'=>$eventIndex,'item'=>$itemIndex];
        }
    }
    $newSales = array_values(array_filter($allEvents,fn($e)=>$e['kind']==='new_sale'));
    $identified = count(array_filter($newSales,fn($e)=>$e['customer_index']!==null));
    return ['batch_key'=>BATCH_KEY,'is_simulated'=>true,'start'=>FIRST_DAY,'end'=>LAST_DAY,
        'source_hash'=>fingerprint($before),'before'=>$before,'updates'=>$updates,'customers'=>$customers,
        'events'=>$allEvents,'ledger'=>$ledger,'opening'=>$opening,'closing'=>$balance,
        'legacy_customers'=>$legacyCustomers,'customer_links'=>$customerLinks,
        'summary'=>['new_sales'=>count($newSales),'new_customers'=>count(array_filter($customers,fn($c)=>$c['first_purchase']!==null)),
            'identified_sales'=>$identified,'final_consumer_sales'=>count($newSales)-$identified,
            'new_supplies'=>$supplyCount,'new_items'=>array_sum(array_map(fn($e)=>count($e['items']),$newSales)),
            'new_revenue'=>money(array_sum(array_map(fn($e)=>cents($e['total']),$newSales))),
            'daily_min'=>min(array_filter($daily,fn($n,$d)=>$d>=FIRST_DAY && $d<=LAST_DAY,ARRAY_FILTER_USE_BOTH)),
            'daily_max'=>max($daily),'days'=>(new DateTimeImmutable(FIRST_DAY))->diff(new DateTimeImmutable(LAST_DAY))->days+1,
            'legacy_customers_recovered'=>count($legacyCustomers),'legacy_sales_linked'=>count($customerLinks)]];
}

function insertRow($table, $values) {
    global $conn;
    $fields = implode('`,`',array_keys($values));
    $marks = implode(',',array_fill(0,count($values),'?'));
    $stmt = $conn->prepare("INSERT INTO `$table` (`$fields`) VALUES ($marks)");
    $args = array_values($values); $stmt->bind_param(str_repeat('s',count($args)),...$args); $stmt->execute();
    return $conn->insert_id;
}
function updateRow($table,$id,$values) {
    global $conn;
    $fields = implode(',',array_map(fn($key)=>"`$key` = ?",array_keys($values)));
    $stmt = $conn->prepare("UPDATE `$table` SET $fields WHERE id = ?");
    $args = array_values($values); $args[] = $id; $stmt->bind_param(str_repeat('s',count($args)),...$args); $stmt->execute();
}
function auditRecord($batch,$table,$id,$action,$before=null) {
    insertRow('demo_data_records',['batch_id'=>$batch,'table_name'=>$table,'record_id'=>$id,'action'=>$action,
        'before_json'=>$before === null ? null : json_encode($before,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
}
function verifyPlan($plan) {
    $balances = array_fill_keys(array_keys($plan['opening']),0);
    $ordered = $plan['ledger'];
    // Receipt immediately precedes its sale, including multiple items in the same sale.
    usort($ordered,fn($a,$b)=>strcmp($a['time'],$b['time']) ?: (($a['kind']==='OPENING' ? -2 : ($a['kind']==='SALE' ? 1 : 0)) <=> ($b['kind']==='OPENING' ? -2 : ($b['kind']==='SALE' ? 1 : 0))));
    foreach ($ordered as $line) {
        $id=$line['product_id']; $balances[$id]+=$line['quantity'];
        check($balances[$id]>=0,'La cronología ordenada deja un saldo negativo.');
        check($balances[$id]===$line['balance'],'Saldo intermedio inconsistente.');
        check(substr($line['time'],11)>='08:00:00' && substr($line['time'],11)<='20:00:00','Movimiento fuera de horario.');
    }
    foreach ($balances as $id=>$q) check($q===$plan['closing'][$id],'Saldo final inconsistente.');
    foreach ($plan['events'] as $event) if ($event['kind']==='new_sale') {
        check(count($event['items'])>=5 && count($event['items'])<=14,'Cantidad de productos fuera de rango.');
        $base=0;$tax=0;$total=0;$ids=[];
        foreach($event['items'] as $item){
            $b=cents($item['unit_price'])*$item['quantity'];
            $t=(int)round($b*(float)$item['tax_rate']/100);
            check($t===cents($item['tax_amount']) && $b+$t===cents($item['line_total']),'IVA de línea inconsistente.');
            check(!isset($ids[$item['product_id']]),'Producto duplicado en un carrito.'); $ids[$item['product_id']]=true;
            $base+=$b;$tax+=$t;$total+=$b+$t;
        }
        check($base===cents($event['subtotal']) && $tax===cents($event['iva']) && $total===cents($event['total']),'Total de comprobante inconsistente.');
        check($total<1300 || $event['customer_index']!==null,'Venta mayor a $13 sin cliente.');
        if($event['payment_method']==='EFECTIVO') check(cents($event['amount_received'])-$total===cents($event['change_amount']),'Cambio incorrecto.');
    }
}

if ($mode === '--plan') {
    check(!file_exists($planFile),'El plan ya existe; no se sobrescribe.');
    $plan = makePlan(snapshot()); verifyPlan($plan);
    file_put_contents($planFile,json_encode($plan,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    echo json_encode($plan['summary'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
    exit;
}

$plan = json_decode(file_get_contents($planFile),true,512,JSON_THROW_ON_ERROR);
check($plan['batch_key']===BATCH_KEY,'Lote inesperado.'); verifyPlan($plan);

if ($mode === '--apply') {
    check(is_file($directory.'/antes.sql') && filesize($directory.'/antes.sql')>1000,'Falta respaldo SQL completo.');
    check(fingerprint(snapshot())===$plan['source_hash'],'La base cambió desde el plan. Regenerar antes de importar.');
    $relaxedEmail=false;
    foreach($conn->query('SHOW INDEX FROM customers')->fetch_all(MYSQLI_ASSOC) as $index) {
        if($index['Key_name']==='email' && (int)$index['Non_unique']===0) {
            $conn->query('ALTER TABLE customers DROP INDEX email, ADD INDEX idx_customers_email (email)');
            $relaxedEmail=true;break;
        }
    }
    $conn->query("CREATE TABLE IF NOT EXISTS demo_data_batches (
        id INT AUTO_INCREMENT PRIMARY KEY, batch_key VARCHAR(100) NOT NULL UNIQUE,
        is_simulated TINYINT NOT NULL DEFAULT 1, start_date DATE NOT NULL, end_date DATE NOT NULL,
        created_at DATETIME NOT NULL, summary_json LONGTEXT NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS demo_data_records (
        id INT AUTO_INCREMENT PRIMARY KEY, batch_id INT NOT NULL, table_name VARCHAR(64) NOT NULL,
        record_id INT NOT NULL, action ENUM('INSERT','UPDATE') NOT NULL, before_json LONGTEXT NULL,
        INDEX batch_records(batch_id,table_name,record_id), FOREIGN KEY(batch_id) REFERENCES demo_data_batches(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS demo_stock_ledger (
        id INT AUTO_INCREMENT PRIMARY KEY, batch_id INT NOT NULL, product_id INT NOT NULL,
        kind ENUM('OPENING','SALE','SUPPLY','RETURN') NOT NULL, quantity INT NOT NULL, balance INT NOT NULL,
        occurred_at DATETIME NOT NULL, source_table VARCHAR(64) NULL, source_id INT NULL, source_item_id INT NULL,
        INDEX product_history(batch_id,product_id,occurred_at), FOREIGN KEY(batch_id) REFERENCES demo_data_batches(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->begin_transaction();
    try {
        // Lock original rows and recheck to prevent applying a stale inventory snapshot.
        foreach (array_keys($plan['before']) as $table) $conn->query("SELECT id FROM `$table` FOR UPDATE");
        check(fingerprint(snapshot())===$plan['source_hash'],'La base cambió durante la importación.');
        $batch=insertRow('demo_data_batches',['batch_key'=>BATCH_KEY,'is_simulated'=>1,'start_date'=>FIRST_DAY,'end_date'=>LAST_DAY,
            'created_at'=>date('Y-m-d H:i:s'),'summary_json'=>json_encode($plan['summary'],JSON_THROW_ON_ERROR)]);
        foreach ($plan['updates'] as $update) {
            $original=array_column($plan['before'][$update['table']],null,'id')[$update['id']];
            auditRecord($batch,$update['table'],$update['id'],'UPDATE',$original);
            if($update['table']==='customers' && !isset($update['values']['updated_at'])) $update['values']['updated_at']=$original['updated_at'];
            updateRow($update['table'],$update['id'],$update['values']);
        }
        $legacyIds=[];
        foreach($plan['legacy_customers'] as $key=>$customer){
            $id=insertRow('customers',$customer);$legacyIds[$key]=$id;auditRecord($batch,'customers',$id,'INSERT');
        }
        foreach($plan['customer_links'] as $saleId=>$link){
            $original=array_column($plan['before']['purchases'],null,'id')[$saleId];
            auditRecord($batch,'purchases',$saleId,'UPDATE',$original);
            updateRow('purchases',$saleId,['customer_id'=>$link['existing_id']??$legacyIds[$link['legacy_key']]]);
        }
        $customerIds=[];
        foreach($plan['customers'] as $index=>$customer) if($customer['first_purchase']) {
            $first=$customer['first_purchase'];unset($customer['first_purchase']);
            $customer['created_at']=$first;$customer['updated_at']=$first;
            $id=insertRow('customers',$customer); $customerIds[$index]=$id; auditRecord($batch,'customers',$id,'INSERT');
        }
        $eventIds=[];$itemIds=[];
        foreach($plan['events'] as $index=>$event) {
            if($event['kind']==='new_sale') {
                $ci=$event['customer_index'];$c=$ci===null?null:$plan['customers'][$ci];
                $values=array_intersect_key($event,array_flip(['user_id','purchase_type','payment_method','subtotal','iva','total','amount_received','change_amount','transfer_reference']));
                $values+=['customer_id'=>$ci===null?null:$customerIds[$ci],'customer_name'=>$c['name']??null,'customer_email'=>$c['email']??null,
                    'customer_phone'=>$c['phone']??null,'customer_address'=>$c['address']??null,'customer_idnumber'=>$c['id_number']??null,'created_at'=>$event['time']];
                $id=insertRow('purchases',$values);auditRecord($batch,'purchases',$id,'INSERT');
                foreach($event['items'] as $k=>$item){$itemIds[$index][$k]=insertRow('purchase_items',['purchase_id'=>$id]+$item);auditRecord($batch,'purchase_items',$itemIds[$index][$k],'INSERT');}
                $eventIds[$index]=['purchases',$id];
            } elseif($event['kind']==='new_supply') {
                $id=insertRow('stock_purchases',['user_id'=>$event['user_id'],'supplier_id'=>$event['supplier_id'],'total'=>$event['total'],
                    'status'=>'RECIBIDO','created_at'=>$event['created_at'],'paid_at'=>$event['paid_at'],'received_at'=>$event['time']]);
                auditRecord($batch,'stock_purchases',$id,'INSERT');
                foreach($event['items'] as $k=>$item){unset($item['pack_size']);$itemIds[$index][$k]=insertRow('stock_purchase_items',['purchase_id'=>$id]+$item);auditRecord($batch,'stock_purchase_items',$itemIds[$index][$k],'INSERT');}
                $eventIds[$index]=['stock_purchases',$id];
            } else {
                $eventIds[$index]=[match($event['kind']){'existing_sale'=>'purchases','existing_return'=>'sales_returns',default=>'stock_purchases'},$event['id']];
                foreach($event['items'] as $k=>$item)$itemIds[$index][$k]=(int)$item['id'];
            }
        }
        $originalProducts=array_column($plan['before']['products'],null,'id');
        foreach($plan['ledger'] as $line) {
            $event=$line['event'];
            insertRow('demo_stock_ledger',['batch_id'=>$batch,'product_id'=>$line['product_id'],'kind'=>$line['kind'],
                'quantity'=>$line['quantity'],'balance'=>$line['balance'],'occurred_at'=>$line['time'],
                'source_table'=>$event===null?null:$eventIds[$event][0],'source_id'=>$event===null?null:$eventIds[$event][1],
                'source_item_id'=>$event===null?null:$itemIds[$event][$line['item']]]);
            if(in_array($line['kind'],['SALE','SUPPLY'],true) && isset($originalProducts[$line['product_id']])
                && ($plan['events'][$event]['kind']==='new_sale' || $plan['events'][$event]['kind']==='new_supply' || !$plan['before']['inventory_logs'])) {
                $log=insertRow('inventory_logs',['product_id'=>$line['product_id'],'quantity'=>abs($line['quantity']),
                    'type'=>$line['kind']==='SALE'?'sale':'purchase','created_at'=>$line['time']]);
                auditRecord($batch,'inventory_logs',$log,'INSERT');
            }
        }
        $originalProducts=array_column($plan['before']['products'],null,'id');
        foreach($plan['closing'] as $id=>$quantity){
            // Deleted historical items remain in the audit ledger, never recreated or stocked for sale.
            if(!isset($originalProducts[$id])) continue;
            $original=$originalProducts[$id];
            auditRecord($batch,'products',$id,'UPDATE',$original);
            updateRow('products',$id,['stock'=>$quantity]);
        }
        verifyDatabase($plan,$batch);
        $conn->commit();
        file_put_contents($directory.'/aplicado.json',json_encode(['batch_id'=>$batch,'applied_at'=>date('c')]+$plan['summary'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo "Lote $batch aplicado.\n";
    } catch(Throwable $e){
        $conn->rollback();
        if($relaxedEmail)$conn->query('ALTER TABLE customers DROP INDEX idx_customers_email, ADD UNIQUE INDEX email (email)');
        throw $e;
    }
}

// Database verification runs both after application and on explicit --verify.
$b=$conn->query("SELECT id FROM demo_data_batches WHERE batch_key='".BATCH_KEY."'")->fetch_assoc();
check($b!==null,'El lote no fue aplicado.');$batch=(int)$b['id'];
function scalar($sql){global $conn;return $conn->query($sql)->fetch_row()[0];}
function verifyDatabase($plan,$batch){
$checks=[
    'sales_inserted'=>(int)scalar("SELECT COUNT(*) FROM demo_data_records WHERE batch_id=$batch AND table_name='purchases' AND action='INSERT'")===$plan['summary']['new_sales'],
    'line_totals'=>(int)scalar("SELECT COUNT(*) FROM purchases p JOIN demo_data_records d ON d.record_id=p.id AND d.table_name='purchases' AND d.action='INSERT' AND d.batch_id=$batch LEFT JOIN (SELECT purchase_id,SUM(ROUND(unit_price*quantity,2)) subtotal,SUM(tax_amount) iva,SUM(line_total) total,COUNT(*) n FROM purchase_items GROUP BY purchase_id)i ON i.purchase_id=p.id WHERE i.n IS NULL OR i.n<5 OR i.n>14 OR p.subtotal<>i.subtotal OR p.iva<>i.iva OR p.total<>i.total")===0,
    'customer_snapshots'=>(int)scalar("SELECT COUNT(*) FROM purchases p JOIN demo_data_records d ON d.record_id=p.id AND d.table_name='purchases' AND d.action='INSERT' AND d.batch_id=$batch LEFT JOIN customers c ON c.id=p.customer_id WHERE p.purchase_type='FACTURA' AND (c.id IS NULL OR p.customer_name<>c.name OR p.customer_email<>c.email OR p.customer_idnumber<>c.id_number OR p.customer_phone<>c.phone OR p.customer_address<>c.address OR c.created_at>p.created_at)")===0,
    'consumer_final_clean'=>(int)scalar("SELECT COUNT(*) FROM purchases p JOIN demo_data_records d ON d.record_id=p.id AND d.table_name='purchases' AND d.action='INSERT' AND d.batch_id=$batch WHERE p.purchase_type='CONSUMIDOR_FINAL' AND (p.customer_id IS NOT NULL OR p.customer_name IS NOT NULL OR p.customer_email IS NOT NULL)")===0,
    'cash_and_transfer'=>(int)scalar("SELECT COUNT(*) FROM purchases p JOIN demo_data_records d ON d.record_id=p.id AND d.table_name='purchases' AND d.action='INSERT' AND d.batch_id=$batch WHERE (payment_method='EFECTIVO' AND (amount_received IS NULL OR change_amount IS NULL OR amount_received-total<>change_amount OR amount_received<total)) OR (payment_method='TRANSFERENCIA' AND (transfer_reference IS NULL OR amount_received IS NOT NULL OR change_amount IS NOT NULL))")===0,
    'daily_range'=>(int)scalar("SELECT COUNT(*) FROM (SELECT DATE(created_at) day,COUNT(*) n FROM purchases WHERE DATE(created_at) BETWEEN '".FIRST_DAY."' AND '".LAST_DAY."' GROUP BY DATE(created_at) HAVING n<15 OR n>42)x")===0,
    'full_period'=>(int)scalar("SELECT COUNT(DISTINCT DATE(created_at)) FROM purchases WHERE DATE(created_at) BETWEEN '".FIRST_DAY."' AND '".LAST_DAY."'")===$plan['summary']['days'],
    'sale_hours'=>(int)scalar("SELECT COUNT(*) FROM purchases WHERE TIME(created_at)<'08:00:00' OR TIME(created_at)>'20:00:00'")===0,
    'supply_chronology'=>(int)scalar("SELECT COUNT(*) FROM stock_purchases WHERE paid_at<created_at OR received_at<paid_at OR TIME(created_at)<'08:00:00' OR TIME(created_at)>'20:00:00' OR TIME(paid_at)<'08:00:00' OR TIME(paid_at)>'20:00:00' OR TIME(received_at)<'08:00:00' OR TIME(received_at)>'20:00:00'")===0,
    'supplier_totals'=>(int)scalar("SELECT COUNT(*) FROM stock_purchases sp JOIN demo_data_records d ON d.record_id=sp.id AND d.table_name='stock_purchases' AND d.action='INSERT' AND d.batch_id=$batch LEFT JOIN(SELECT purchase_id,SUM(quantity*cost_price) total FROM stock_purchase_items GROUP BY purchase_id)i ON i.purchase_id=sp.id WHERE sp.status<>'RECIBIDO' OR i.total IS NULL OR sp.total<>i.total OR sp.supplier_id IS NULL")===0,
    'ledger_closing'=>(int)scalar("SELECT COUNT(*) FROM products p LEFT JOIN(SELECT product_id,SUM(quantity) quantity FROM demo_stock_ledger WHERE batch_id=$batch GROUP BY product_id)l ON l.product_id=p.id WHERE l.quantity IS NULL OR l.quantity<>p.stock")===0,
    'ledger_nonnegative'=>(int)scalar("SELECT COUNT(*) FROM demo_stock_ledger WHERE batch_id=$batch AND balance<0")===0,
    'placeholder_emails_removed'=>(int)scalar("SELECT (SELECT COUNT(*) FROM customers WHERE email LIKE '%@example.com')+(SELECT COUNT(*) FROM purchases WHERE customer_email LIKE '%@example.com')")===0,
    'legacy_customers_linked'=>(int)scalar("SELECT COUNT(*) FROM purchases p LEFT JOIN customers c ON c.id=p.customer_id WHERE p.purchase_type='FACTURA' AND c.id IS NULL")===0,
];
foreach($checks as $name=>$passed)check($passed,"Falló verificación: $name");
return $checks;
}
$checks=verifyDatabase($plan,$batch);
file_put_contents($directory.'/verificacion.json',json_encode(['checks'=>$checks,'summary'=>$plan['summary']],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode(['checks'=>$checks,'summary'=>$plan['summary']],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
