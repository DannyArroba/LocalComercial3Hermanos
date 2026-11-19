<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') !== '--aplicar' || empty($argv[2])) { fwrite(STDERR, "Uso: php aplicar_ampliacion.php --aplicar RESPALDO_SQL\n"); exit(1); }
$backup=$argv[2];
if (!is_file($backup) || filesize($backup)<1000) throw new RuntimeException('Se requiere respaldo SQL completo.');
$recordPath=dirname($backup).'/resultado-ampliacion.json';
if(file_exists($recordPath)) throw new RuntimeException('Esta carga ya se aplicó con este respaldo.');
require __DIR__.'/../../backend/db.php';
$plan=json_decode(file_get_contents(__DIR__.'/ampliacion.json'),true,512,JSON_THROW_ON_ERROR);
function readRows($conn,$table) { return $conn->query("SELECT * FROM `$table` ORDER BY 1")->fetch_all(MYSQLI_ASSOC); }
function codeValid($code) {
    if(!preg_match('/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/',$code)) return false;
    $sum=0;$weight=3;
    for($i=strlen($code)-2;$i>=0;$i--) {$sum+=(int)$code[$i]*$weight;$weight=4-$weight;}
    return (10-$sum%10)%10===(int)substr($code,-1);
}
$before=readRows($conn,'products'); $byId=array_column($before,null,'id');
$baseline=array_column($plan['baseline'],null,'id');
if(count($before)!==count($baseline)) throw new RuntimeException('Cambió el catálogo desde la preparación.');
foreach($before as $old) {
    foreach(['name','barcode','price','stock','image','cost_price','applies_iva','supplier_id','category','description'] as $field) {
        if((string)$old[$field] !== (string)($baseline[$old['id']][$field]??'')) throw new RuntimeException('Cambio concurrente en producto '.$old['id'].' campo '.$field);
    }
}
$remove=(int)$plan['remove_id'];
if(($byId[$remove]['barcode']??'')!=='2000010000357') throw new RuntimeException('El producto a quitar no es el plátano verde esperado.');
foreach(['purchase_items','sale_items','sales_return_items','stock_purchase_items','inventory_logs'] as $table) {
    if((int)$conn->query("SELECT COUNT(*) AS n FROM `$table` WHERE product_id=$remove")->fetch_assoc()['n']>0) throw new RuntimeException('El verde tiene historial; requiere archivo lógico, no borrado.');
}
$protected=['purchases','purchase_items','sales','sale_items','invoices','sales_returns','sales_return_items','stock_purchases','stock_purchase_items','inventory_logs'];
$hashes=[];
foreach($protected as $table) $hashes[$table]=hash('sha256',json_encode(readRows($conn,$table)));
$supplierIds=array_column(readRows($conn,'suppliers'),'id');
$codes=[];$names=[];
foreach($plan['products'] as $row) {
    if(!in_array($row['supplier_id'],$supplierIds)) throw new RuntimeException('Proveedor inexistente.');
    $name=mb_strtolower($row['name'],'UTF-8');
    if(isset($names[$name])) throw new RuntimeException('Nombre duplicado: '.$name);
    $names[$name]=true;
    foreach($row['scan_codes'] as $code) {
        if(!codeValid($code)||isset($codes[$code])) throw new RuntimeException('Código inválido o duplicado: '.$code);
        $codes[$code]=true;
        $stmt=$conn->prepare('SELECT id FROM products WHERE barcode = ? AND id <> ?');
        $id=(int)($row['existing_id']??0); $stmt->bind_param('si',$code,$id); $stmt->execute();
        if($stmt->get_result()->num_rows) throw new RuntimeException('Código ya usado: '.$code);
    }
    if((float)$row['cost_price']<=0||(float)$row['cost_price']>=(float)$row['price']||$row['stock']<1) throw new RuntimeException('Costo/stock inválido.');
    if(abs(round((float)$row['price']*($row['applies_iva']?1.15:1),2)-(float)$row['pvp'])>.001) throw new RuntimeException('PVP inválido.');
    if(!getimagesize(__DIR__.'/../../frontend/uploads/'.$row['image'])) throw new RuntimeException('Imagen inválida: '.$row['name']);
}
// Migración aditiva de esquema, fuera de la transacción de datos.
$conn->query(file_get_contents(__DIR__.'/../../backend/migrations/20261118_product_barcodes.sql'));
$lock=$conn->query("SELECT GET_LOCK('catalogo_ampliacion_20261118',10) AS acquired")->fetch_assoc();
if((int)$lock['acquired']!==1) throw new RuntimeException('No se obtuvo el bloqueo administrativo.');
$conn->begin_transaction();
try {
    $conn->query("DELETE FROM products WHERE id=$remove");
    $shortNames=[
        5=>'Pollo fresco 1 kg',6=>'Arroz blanco 1 lb (454 g)',7=>'Chocolate blanco crocante 20 g',8=>'Coca-Cola 2,5 L',9=>'Agua sin gas 500 ml',10=>'Néctar Del Valle naranja 1 L',11=>'Leche Nutri entera 1 L',
        12=>'Yogur Miraflores fresa 1 kg',13=>'Queso González fresco 500 g',14=>'Arroz Real 5 kg',15=>'Aceite Girasoli 1 L',16=>'Pan de molde Moderna',17=>'Café El Artesanal 250 g',18=>'Gomitas surtidas 50 g',19=>'Mayonesa Los Andes 200 g',
        20=>'Azúcar blanca 1 lb (454 g)',21=>'Aceite La Favorita 1 L',22=>'Huevos cubeta 30 u',23=>'Spaghetti Paca 400 g',24=>'Atún ILE aceite 170 g',25=>'Harina La Original 500 g',26=>'Sal Cris-Sal 1 kg',27=>'Avena La Pradera 500 g',28=>'Lenteja 1 lb (454 g)',29=>'Fréjol rojo 1 lb (454 g)',30=>'Café Alto Cayetano 100 g',31=>'Salsa tomate Los Andes 200 g',32=>'Galletas María 145 g',33=>'Ruffles picantes 38 g',34=>'Chifles Tortolines 45 g',35=>'Caramelos surtidos 50 g',36=>'Coca-Cola 500 ml',37=>'Coca-Cola 3,05 L',38=>'Schweppes con gas 1,5 L',39=>'Red Bull original 250 ml',40=>'Yogur El Pino natural 1 L',41=>'Mantequilla Nutri 250 g',42=>'Papa chola 1 kg',43=>'Cebolla colorada 1 kg',44=>'Tomate riñón 1 kg',46=>'Detergente Surf 1 kg',47=>'Cloro Livy 1 L',48=>'Jabón El Macho 480 g',49=>'Jabón Jolly manzanilla 100 g',50=>'Papel Elite 4 rollos × 38 m',51=>'Pasta Colgate menta 75 ml',52=>'Shampoo Gama 400 ml',53=>'Desodorante Dr. Dry 50 ml',54=>'Toallas Ladysoft 10 u',55=>'Pañales Huggies G 20 u',56=>'Margarina Imperial 250 g',57=>'Sardina Real 155 g',58=>'Maicena Iris 200 g',59=>'Servilletas Elite 100 u',60=>'Lavavajilla Lavatodo 450 g',61=>'Suavizante Livy 1 L',62=>'Volt original 400 ml',63=>'Pilsener retornable 1 L',64=>'Pilsener Light retornable 1 L',65=>'Cerveza negra Ballesta 300 ml'];
    $rename=$conn->prepare('UPDATE products SET name=?,category=? WHERE id=?');
    foreach($shortNames as $id=>$name) {
        if(!isset($byId[$id])) continue;
        $cat=$byId[$id]['category'];
        if(in_array($id,[7])) $cat='Chocolates';
        if(in_array($id,[18,35])) $cat='Gomitas y caramelos';
        if(in_array($id,[32])) $cat='Galletas';
        if(in_array($id,[33,34])) $cat='Snacks';
        if(in_array($id,[8,36,37])) $cat='Gaseosas';
        if(in_array($id,[11])) $cat='Leches';
        if(in_array($id,[12,40])) $cat='Yogures';
        if(in_array($id,[13])) $cat='Quesos y embutidos';
        $rename->bind_param('ssi',$name,$cat,$id);$rename->execute();
    }
    // Alinear Coca-Cola personal con el PVP de $0,80 solicitado y publicado por el fabricante.
    $conn->query("UPDATE products SET price=0.69 WHERE id=36");
    $insert=$conn->prepare('INSERT INTO products(supplier_id,name,barcode,price,applies_iva,cost_price,category,stock,description,image) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $alias=$conn->prepare('INSERT INTO product_barcodes(barcode,product_id) VALUES (?,?)');
    $created=[];
    foreach($plan['products'] as $row) {
        if($row['existing_id']!==null) throw new RuntimeException('Revisar coincidencia existente antes de insertar.');
        $supplier=$row['supplier_id'];$name=$row['name'];$code=$row['barcode'];$price=$row['price'];$iva=$row['applies_iva'];$cost=$row['cost_price'];$cat=$row['category'];$stock=$row['stock'];$desc=$row['description'];$image=$row['image'];
        $insert->bind_param('isssississ',$supplier,$name,$code,$price,$iva,$cost,$cat,$stock,$desc,$image);$insert->execute();
        $id=$conn->insert_id;
        foreach($row['scan_codes'] as $scan) { $alias->bind_param('si',$scan,$id);$alias->execute(); }
        $row['id']=$id;$row['action']='creado';$created[]=$row;
    }
    $all=readRows($conn,'products');
    if(count($all)!==count($before)-1+count($created)) throw new RuntimeException('Conteo inesperado.');
    if($conn->query('SELECT barcode FROM products GROUP BY barcode HAVING COUNT(*)>1')->num_rows) throw new RuntimeException('Código principal duplicado.');
    if($conn->query('SELECT LOWER(TRIM(name)) FROM products GROUP BY LOWER(TRIM(name)) HAVING COUNT(*)>1')->num_rows) throw new RuntimeException('Nombre duplicado.');
    if($conn->query('SELECT b.barcode FROM product_barcodes b JOIN products p ON p.barcode=b.barcode AND p.id<>b.product_id')->num_rows) throw new RuntimeException('Alias apunta a otro producto.');
    foreach($protected as $table) if($hashes[$table]!==hash('sha256',json_encode(readRows($conn,$table)))) throw new RuntimeException('Se alteró historial: '.$table);
    $payload=['applied_at'=>date(DATE_ATOM),'backup'=>$backup,'before'=>$before,'created'=>count($created),'removed'=>$byId[$remove],'total'=>count($all),'history_unchanged'=>true,'groups'=>$plan['groups'],'new_products'=>$created,'products'=>$all];
    if(file_put_contents($recordPath,json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false) throw new RuntimeException('No se pudo guardar auditoría.');
    $conn->commit();
    echo json_encode(['created'=>count($created),'removed'=>$byId[$remove]['name'],'total'=>count($all),'groups'=>count($plan['groups']),'history_unchanged'=>true,'record'=>$recordPath],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
} catch(Throwable $error) {
    $conn->rollback();if(file_exists($recordPath)) unlink($recordPath);throw $error;
} finally { $conn->query("SELECT RELEASE_LOCK('catalogo_ampliacion_20261118')"); }
