<?php
if (PHP_SAPI!=='cli') { http_response_code(404);exit; }
require __DIR__.'/../../backend/db.php';
$products=json_decode(file_get_contents('http://127.0.0.1:5173/api/backend/api/products.php'),true,512,JSON_THROW_ON_ERROR);
$plan=json_decode(file_get_contents($argv[2]??__DIR__.'/ampliacion.json'),true,512,JSON_THROW_ON_ERROR);
$expected=count($plan['baseline'])+count($plan['products'])-(isset($plan['remove_id'])?1:0);
if(count($products)!==$expected) throw new RuntimeException('Conteo de productos distinto al esperado: '.$expected);
$byCode=[];$byId=[];$cases=[];
foreach($products as $p) {
    $byId[$p['id']]=$p;
    if(stripos($p['name'],'plátano verde')!==false) throw new RuntimeException('El plátano verde sigue visible.');
    if(mb_strlen($p['name'],'UTF-8')>45) throw new RuntimeException('Nombre excesivo: '.$p['name']);
    if(!$p['supplier_name']||!$p['image']||!getimagesize(__DIR__.'/../../frontend/uploads/'.$p['image'])) throw new RuntimeException('Faltan campos o foto: '.$p['id']);
    foreach($p['scan_codes'] as $code) {
        if(isset($byCode[$code]) && $byCode[$code]!==$p['id']) throw new RuntimeException('Código ambiguo: '.$code);
        $byCode[$code]=$p['id'];
    }
    foreach(array_unique([1,2,3,7,(int)$p['stock']]) as $quantity) {
        // Misma fórmula fiscal por línea que utiliza checkout.php; sin vender ni alterar stock.
        $base=round((float)$p['price']*$quantity,2);
        $tax=round($base*((int)$p['applies_iva']===1?.15:0),2);
        $cases[]=['product'=>['price'=>$p['price'],'applies_iva'=>$p['applies_iva']],'quantity'=>$quantity,'base'=>$base,'tax'=>$tax,'total'=>round($base+$tax,2)];
    }
}
$alternate=0;
foreach($plan['products'] as $row) {
    foreach($row['scan_codes'] as $code) {
        if(!isset($byCode[$code])||$byCode[$code]!==$byCode[$row['barcode']]) throw new RuntimeException('Variante no reconocida: '.$code);
        if($code!==$row['barcode'])$alternate++;
    }
}
// Los stocks de los productos que ya existían se conservan.
foreach($plan['baseline'] as $old) {
    if(isset($plan['remove_id'])&&(int)$old['id']===(int)$plan['remove_id']) continue;
    if((int)$byId[$old['id']]['stock']!==(int)$old['stock'])throw new RuntimeException('Stock anterior alterado: '.$old['id']);
}
if($conn->query('SELECT barcode FROM products GROUP BY barcode HAVING COUNT(*)>1')->num_rows) throw new RuntimeException('Duplicado.');
if(empty($argv[1]))throw new RuntimeException('Falta ruta temporal para casos de prueba.');
file_put_contents($argv[1],json_encode($cases,JSON_THROW_ON_ERROR));
echo json_encode(['products'=>count($products),'images_valid'=>count($products),'unique_scannable_codes'=>count($byCode),'alternate_codes_verified'=>$alternate,'prior_stocks_preserved'=>true,'pricing_cases'=>count($cases)],JSON_PRETTY_PRINT)."\n";
