<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../../backend/db.php';
$source=$argv[1]??'';$target=dirname($source).'/resultado-lote-final.json';
if(!is_file($source)||file_exists($target))throw new RuntimeException('Se requiere auditoría inicial y ejecución única.');
$audit=json_decode(file_get_contents($source),true,512,JSON_THROW_ON_ERROR);
$planPath=__DIR__.'/snacks-plan.json';$plan=json_decode(file_get_contents($planPath),true,512,JSON_THROW_ON_ERROR);
$a=null;$b=null;
foreach($audit['new_products']as$row){if($row['key']==='snacks-doritos420')$a=$row;if($row['key']==='snacks-doritos420limon')$b=$row;}
if(!$a||!$b||$a['price']!==$b['price'])throw new RuntimeException('Variantes no equivalentes.');
$aid=(int)$a['id'];$bid=(int)$b['id'];
foreach(['purchase_items','sale_items','sales_return_items','stock_purchase_items','inventory_logs']as$table){
    if((int)$conn->query("SELECT COUNT(*) AS n FROM `$table` WHERE product_id IN ($aid,$bid)")->fetch_assoc()['n']>0)throw new RuntimeException('Las variantes ya tienen historial.');
}
$variants=[];foreach([$a,$b]as$r)$variants[]=array_intersect_key($r,array_flip(['name','barcode','source_url','reference_price']));
$a['name']='Doritos surtidos 420 g';$a['stock']+=(int)$b['stock'];$a['variants']=$variants;
$a['pack']='24 fundas de 420 g en total; 12 de queso y 12 de Lemon Remix agrupadas';
$a['scan_codes']=array_values(array_unique(array_merge($a['scan_codes'],$b['scan_codes'])));
$a['description']='Doritos de 420 g, sabores queso y Lemon Remix. Venta por funda completa. Stock conjunto de ambas variedades al mismo precio. La foto muestra una variedad representativa.';
$group=['name'=>$a['name'],'pvp'=>$a['pvp'],'variants'=>$variants];
$conn->begin_transaction();
try{
    $current=$conn->query("SELECT id,stock,price,barcode FROM products WHERE id IN ($aid,$bid) FOR UPDATE")->fetch_all(MYSQLI_ASSOC);
    $byId=array_column($current,null,'id');
    if(count($current)!==2||(int)$byId[$aid]['stock']!==12||(int)$byId[$bid]['stock']!==12||$byId[$aid]['barcode']!==$a['barcode']||$byId[$bid]['barcode']!==$b['barcode'])throw new RuntimeException('Las variantes cambiaron antes de agrupar.');
    $stmt=$conn->prepare('UPDATE products SET name=?,stock=?,description=? WHERE id=?');$stmt->bind_param('sisi',$a['name'],$a['stock'],$a['description'],$aid);$stmt->execute();
    $conn->query("UPDATE product_barcodes SET product_id=$aid WHERE product_id=$bid");
    $conn->query("DELETE FROM products WHERE id=$bid");
    $audit['inserted_initially']=$audit['created'];$audit['created']--; $audit['total']--;
    $audit['merged_after_import']=[['kept_id'=>$aid,'removed_id'=>$bid,'reason'=>'Misma marca, presentación de 420 g y precio; stock sumado sin pérdida de unidades']];
    $audit['groups'][]=$group;
    $audit['new_products']=array_values(array_filter(array_map(fn($r)=>$r['id']===$aid?$a:$r,$audit['new_products']),fn($r)=>$r['id']!==$bid));
    $audit['products']=$conn->query('SELECT * FROM products ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    $plan['groups'][]=$group;
    $plan['products']=array_values(array_filter(array_map(fn($r)=>$r['key']===$a['key']?$a:$r,$plan['products']),fn($r)=>$r['key']!==$b['key']));
    file_put_contents($target,json_encode($audit,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    file_put_contents($planPath,json_encode($plan,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    $conn->commit();echo "Agrupado: $target\n";
}catch(Throwable$error){$conn->rollback();throw$error;}
