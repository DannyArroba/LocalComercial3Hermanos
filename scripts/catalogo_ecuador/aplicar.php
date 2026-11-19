<?php
// Operación administrativa local explícita. Nunca disponible desde HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') !== '--aplicar' || empty($argv[2])) {
    fwrite(STDERR, "Uso: php aplicar.php --aplicar RUTA_RESPALDO_SQL\n"); exit(1);
}
$backup = $argv[2];
if (!is_file($backup) || filesize($backup) < 1000) {
    throw new RuntimeException('Se requiere un respaldo SQL completo previo.');
}
require __DIR__ . '/../../backend/db.php';
$data = json_decode(file_get_contents(__DIR__ . '/catalogo.json'), true, 512, JSON_THROW_ON_ERROR);
$recordPath = dirname($backup) . '/resultado-catalogo.json';
if (file_exists($recordPath)) { throw new RuntimeException('La carga ya fue aplicada con este respaldo.'); }
function rows($conn, $table) {
    return $conn->query("SELECT * FROM `$table` ORDER BY 1")->fetch_all(MYSQLI_ASSOC);
}
function checkCode($code) {
    if (!preg_match('/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/', $code)) return false;
    $sum=0; $weight=3;
    for ($i=strlen($code)-2; $i>=0; $i--) { $sum+=(int)$code[$i]*$weight; $weight=4-$weight; }
    return (10-$sum%10)%10===(int)substr($code,-1);
}
$suppliers=array_column(rows($conn,'suppliers'),'id');
$before=rows($conn,'products');
$byId=array_column($before,null,'id');
$oldIds=array_column($data,'existing_id');
$oldIds=array_values(array_filter($oldIds,fn($id)=>$id!==null));
if (count($oldIds)!==count($before) || array_diff(array_keys($byId),$oldIds)) {
    throw new RuntimeException('El catálogo local cambió desde la inspección. Revisar antes de importar.');
}
$names=[]; $codes=[];
foreach ($data as $row) {
    if (!in_array($row['supplier_id'],$suppliers)) throw new RuntimeException('Proveedor inexistente.');
    $name=mb_strtolower(trim($row['name']),'UTF-8');
    if (isset($names[$name]) || isset($codes[$row['barcode']])) throw new RuntimeException('Duplicado en carga.');
    $names[$name]=true; $codes[$row['barcode']]=true;
    if (!checkCode($row['barcode'])) throw new RuntimeException('Código inválido: '.$row['name'].' '.$row['barcode']);
    if ((float)$row['price']<=0 || (float)$row['cost_price']<=0 || (float)$row['cost_price']>=(float)$row['price'] || $row['stock']<=0) {
        throw new RuntimeException('Precio, costo o stock inválido: '.$row['name']);
    }
    $total=round((float)$row['price']*($row['applies_iva']?1.15:1),2);
    if (abs($total-(float)$row['pvp'])>0.001) throw new RuntimeException('PVP inconsistente: '.$row['name']);
    if (empty($row['image']) || !getimagesize(__DIR__.'/../../frontend/uploads/'.$row['image'])) {
        throw new RuntimeException('Falta imagen válida: '.$row['name']);
    }
    if ($conn->query("SELECT id FROM products WHERE barcode='".$conn->real_escape_string($row['barcode'])."' AND id<>".(int)($row['existing_id']??0))->num_rows) {
        throw new RuntimeException('Código ya utilizado: '.$row['barcode']);
    }
}
// Conservar las tablas históricas byte por byte; esta carga no inventa compras ni ventas.
$protected=['purchases','purchase_items','sales','sale_items','invoices','sales_returns','sales_return_items','stock_purchases','stock_purchase_items','inventory_logs'];
$hashes=[];
foreach($protected as $table) $hashes[$table]=hash('sha256',json_encode(rows($conn,$table)));
$schemaBefore=$conn->query('SHOW CREATE TABLE products')->fetch_row()[1];
file_put_contents(dirname($backup).'/productos-antes.json',json_encode(['schema'=>$schemaBefore,'products'=>$before,'history_hashes'=>$hashes],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
$lock=$conn->query("SELECT GET_LOCK('catalogo_ecuador_20261118',10) AS acquired")->fetch_assoc();
if((int)$lock['acquired']!==1) throw new RuntimeException('No se pudo bloquear la carga.');
$conn->begin_transaction();
try {
    $result=[];
    $update=$conn->prepare('UPDATE products SET supplier_id=?,name=?,barcode=?,price=?,applies_iva=?,cost_price=?,category=?,stock=?,description=?,image=? WHERE id=?');
    $insert=$conn->prepare('INSERT INTO products(supplier_id,name,barcode,price,applies_iva,cost_price,category,stock,description,image) VALUES (?,?,?,?,?,?,?,?,?,?)');
    foreach ($data as $row) {
        $supplier=$row['supplier_id']; $name=$row['name']; $code=$row['barcode']; $price=$row['price']; $iva=$row['applies_iva'];
        $cost=$row['cost_price']; $category=$row['category']; $stock=$row['stock']; $description=$row['description']; $image=$row['image'];
        $id=$row['existing_id'];
        if ($id!==null) {
            $update->bind_param('isssississi',$supplier,$name,$code,$price,$iva,$cost,$category,$stock,$description,$image,$id);
            $update->execute();
            $row['previous']=$byId[$id];
            $row['action']='actualizado';
        } else {
            $insert->bind_param('isssississ',$supplier,$name,$code,$price,$iva,$cost,$category,$stock,$description,$image);
            $insert->execute();
            $id=$conn->insert_id;
            $row['action']='creado';
        }
        $row['id']=$id;
        $result[]=$row;
    }
    foreach($protected as $table) {
        if ($hashes[$table]!==hash('sha256',json_encode(rows($conn,$table)))) throw new RuntimeException('Se alteró el historial: '.$table);
    }
    $all=rows($conn,'products');
    if (count($all)!==count($data)) throw new RuntimeException('Cantidad final inesperada.');
    if ($conn->query('SELECT barcode FROM products GROUP BY barcode HAVING COUNT(*)>1')->num_rows) throw new RuntimeException('Códigos duplicados.');
    $payload=['applied_at'=>date(DATE_ATOM),'backup'=>$backup,'created'=>count(array_filter($result,fn($r)=>$r['action']==='creado')),
        'updated'=>count($oldIds),'total'=>count($all),'history_unchanged'=>true,'products'=>$result];
    if(file_put_contents($recordPath,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false) throw new RuntimeException('No se pudo guardar la auditoría.');
    $conn->commit();
    echo json_encode(['created'=>$payload['created'],'updated'=>$payload['updated'],'total'=>$payload['total'],'history_unchanged'=>true,'record'=>$recordPath],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
} catch (Throwable $error) {
    $conn->rollback();
    if(file_exists($recordPath)) unlink($recordPath);
    throw $error;
} finally {
    $conn->query("SELECT RELEASE_LOCK('catalogo_ecuador_20261118')");
}
