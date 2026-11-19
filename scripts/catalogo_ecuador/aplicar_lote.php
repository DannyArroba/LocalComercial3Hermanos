<?php
// Importador administrativo CLI. No expone una ruta HTTP de escritura.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(($argv[1]??'')!=='--aplicar'||empty($argv[2])||empty($argv[3])){fwrite(STDERR,"Uso: php aplicar_lote.php --aplicar PLAN_JSON RESPALDO_SQL\n");exit(1);}
$plan=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR);$backup=$argv[3];
if(!is_file($backup)||filesize($backup)<1000)throw new RuntimeException('Respaldo completo obligatorio.');
$recordPath=dirname($backup).'/resultado-lote.json';
if(file_exists($recordPath))throw new RuntimeException('Lote ya aplicado con este respaldo.');
require __DIR__.'/../../backend/db.php';
function readAll($conn,$table){return $conn->query("SELECT * FROM `$table` ORDER BY 1")->fetch_all(MYSQLI_ASSOC);}
function validBarcode($code){
    if(!preg_match('/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/',$code))return false;
    $sum=0;$weight=3;for($i=strlen($code)-2;$i>=0;$i--){$sum+=(int)$code[$i]*$weight;$weight=4-$weight;}
    return (10-$sum%10)%10===(int)substr($code,-1);
}
$before=readAll($conn,'products');$base=array_column($plan['baseline'],null,'id');
if(count($before)!==count($base))throw new RuntimeException('El catálogo cambió durante la preparación.');
$known=[];$names=[];
foreach($before as $old){
    foreach(['name','barcode','price','applies_iva','cost_price','stock','supplier_id','image','category','description']as$field){
        if((string)$old[$field]!== (string)($base[$old['id']][$field]??''))throw new RuntimeException('Cambio concurrente: producto '.$old['id'].' campo '.$field);
    }
    if($old['barcode'])$known[$old['barcode']]=$old['id'];
    $names[mb_strtolower(trim($old['name']),'UTF-8')]=true;
}
foreach(readAll($conn,'product_barcodes')as$b)$known[$b['barcode']]=$b['product_id'];
$suppliers=array_column(readAll($conn,'suppliers'),'id');$incoming=[];
foreach($plan['products']as$row){
    $name=mb_strtolower(trim($row['name']),'UTF-8');
    if(isset($names[$name]))throw new RuntimeException('Producto duplicado por nombre: '.$row['name']);$names[$name]=true;
    if(!in_array($row['supplier_id'],$suppliers))throw new RuntimeException('Proveedor inexistente.');
    if(!in_array($row['barcode'],$row['scan_codes'],true))throw new RuntimeException('Falta código principal.');
    foreach($row['scan_codes']as$code){
        if(!validBarcode($code)||isset($known[$code])||isset($incoming[$code]))throw new RuntimeException('Código inválido o duplicado: '.$code);
        $incoming[$code]=true;
    }
    if(!getimagesize(__DIR__.'/../../frontend/uploads/'.$row['image']))throw new RuntimeException('Falta foto válida: '.$row['name']);
    if((float)$row['price']<=0||(float)$row['cost_price']<=0||(float)$row['cost_price']>=(float)$row['price']||$row['stock']<1)throw new RuntimeException('Valores inválidos: '.$row['name']);
    if(abs(round((float)$row['price']*($row['applies_iva']?1.15:1),2)-(float)$row['pvp'])>.001)throw new RuntimeException('PVP inconsistente.');
}
$protected=['purchases','purchase_items','sales','sale_items','invoices','sales_returns','sales_return_items','stock_purchases','stock_purchase_items','inventory_logs'];$hashes=[];
foreach($protected as$table)$hashes[$table]=hash('sha256',json_encode(readAll($conn,$table)));
$lock=$conn->query("SELECT GET_LOCK('catalogo_lote',10) AS acquired")->fetch_assoc();
if((int)$lock['acquired']!==1)throw new RuntimeException('No se obtuvo bloqueo de importación.');
$conn->begin_transaction();
try{
    $insert=$conn->prepare('INSERT INTO products(supplier_id,name,barcode,price,applies_iva,cost_price,category,stock,description,image) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $alias=$conn->prepare('INSERT INTO product_barcodes(barcode,product_id) VALUES (?,?)');$created=[];
    foreach($plan['products']as$row){
        $supplier=$row['supplier_id'];$name=$row['name'];$code=$row['barcode'];$price=$row['price'];$iva=$row['applies_iva'];$cost=$row['cost_price'];$cat=$row['category'];$stock=$row['stock'];$desc=$row['description'];$image=$row['image'];
        $insert->bind_param('isssississ',$supplier,$name,$code,$price,$iva,$cost,$cat,$stock,$desc,$image);$insert->execute();$id=$conn->insert_id;
        foreach($row['scan_codes']as$scan){$alias->bind_param('si',$scan,$id);$alias->execute();}
        $row['id']=$id;$row['action']='creado';$created[]=$row;
    }
    $recategorize=$conn->prepare('UPDATE products SET category=? WHERE id=?');
    foreach($plan['category_updates']??[]as$change){
        $id=(int)$change['id'];$cat=$change['category'];
        if(!isset($base[$id]))throw new RuntimeException('Cambio de categoría fuera del catálogo anterior.');
        $recategorize->bind_param('si',$cat,$id);$recategorize->execute();
    }
    $all=readAll($conn,'products');$byId=array_column($all,null,'id');
    if(count($all)!==count($before)+count($created))throw new RuntimeException('Conteo final incorrecto.');
    foreach($before as$old){
        foreach(['name','barcode','price','applies_iva','cost_price','stock','supplier_id','image','description']as$field){
            if((string)$byId[$old['id']][$field]!== (string)$old[$field])throw new RuntimeException('Se alteró un producto anterior.');
        }
    }
    foreach($protected as$table)if($hashes[$table]!==hash('sha256',json_encode(readAll($conn,$table))))throw new RuntimeException('Se alteró historial: '.$table);
    if($conn->query('SELECT b.barcode FROM product_barcodes b JOIN products p ON p.barcode=b.barcode AND p.id<>b.product_id')->num_rows)throw new RuntimeException('Alias ambiguo.');
    $receipt=['batch'=>$plan['batch'],'applied_at'=>date(DATE_ATOM),'backup'=>$backup,'created'=>count($created),'total'=>count($all),'groups'=>$plan['groups'],'skipped'=>$plan['skipped'],'category_updates'=>$plan['category_updates']??[],
        'history_unchanged'=>true,'prior_stocks_unchanged'=>true,'before'=>$before,'new_products'=>$created,'products'=>$all];
    if(file_put_contents($recordPath,json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false)throw new RuntimeException('No se pudo guardar auditoría.');
    $conn->commit();
    echo json_encode(array_intersect_key($receipt,array_flip(['created','total','history_unchanged','prior_stocks_unchanged','skipped'])),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
    echo "Registro: $recordPath\n";
}catch(Throwable$error){$conn->rollback();if(file_exists($recordPath))unlink($recordPath);throw$error;}
finally{$conn->query("SELECT RELEASE_LOCK('catalogo_lote')");}
