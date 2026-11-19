<?php
// Disposable connection-local tables test tax and partial refunds without editing live data or IDs.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../backend/db.php';
require __DIR__.'/../backend/api-bootstrap.php';
$userId=(int)$conn->query('SELECT MIN(id) FROM users')->fetch_row()[0];
$_SESSION['user_id']=$userId;session_abort();$_SERVER['REQUEST_METHOD']='GET';
$annual=($argv[1]??'')==='annual';$_GET=$annual?['is_annual'=>'true']:[];
foreach(['purchases','purchase_items','sales_returns','sales_return_items'] as $table){
    $ddl=$conn->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];
    $ddl=preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $ddl);
    $ddl=implode("\n",array_filter(explode("\n",$ddl),fn($line)=>!preg_match('/^\s*CONSTRAINT /',$line)));
    $ddl=preg_replace('/,(\s*\)\s*ENGINE=)/', '$1', $ddl);
    $conn->query($ddl);
}
$products=$conn->query('SELECT id FROM products ORDER BY id LIMIT 2')->fetch_all(MYSQLI_ASSOC);
if(count($products)!==2)throw new RuntimeException('Se requieren dos productos para la prueba.');
$p1=(int)$products[0]['id'];$p2=(int)$products[1]['id'];$today=date('Y-m-d');
// Five units each: taxable100+VAT15 and zero-rated50; costs70+40.
$conn->query("INSERT INTO purchases(user_id,purchase_type,payment_method,subtotal,iva,total,amount_received,change_amount,created_at)
    VALUES($userId,'CONSUMIDOR_FINAL','EFECTIVO',150,15,165,170,5,'$today 10:00:00')");
$sale=(int)$conn->insert_id;
$conn->query("INSERT INTO purchase_items(purchase_id,product_id,product_name,unit_price,unit_cost,tax_rate,tax_amount,quantity,line_total)
    VALUES($sale,$p1,'Prueba gravada',20,14,15,15,5,115)");$i1=(int)$conn->insert_id;
$conn->query("INSERT INTO purchase_items(purchase_id,product_id,product_name,unit_price,unit_cost,tax_rate,tax_amount,quantity,line_total)
    VALUES($sale,$p2,'Prueba tarifa cero',10,8,0,0,5,50)");$i2=(int)$conn->insert_id;
// Return one unit of each, in the same return: base30+VAT3, refunded33, returned cost22.
$conn->query("INSERT INTO sales_returns(purchase_id,user_id,return_code,customer_name,customer_idnumber,customer_phone,total_refund,created_at)
    VALUES($sale,$userId,'TEST-IVA','Cliente de prueba','0000000000','0900000000',33,'$today 11:00:00')");$ret=(int)$conn->insert_id;
$conn->query("INSERT INTO sales_return_items(return_id,purchase_item_id,product_id,product_name,quantity,unit_price,unit_cost,tax_rate,tax_amount,line_total)
    VALUES($ret,$i1,$p1,'Prueba gravada',1,20,14,15,3,23),($ret,$i2,$p2,'Prueba tarifa cero',1,10,8,0,0,10)");
ob_start();
register_shutdown_function(function()use($annual,$today){
    $data=json_decode(ob_get_clean(),true);
    try{
        if(($data['status']??'')!=='success')throw new RuntimeException('El dashboard no respondió correctamente.');
        $expected=['grossRevenue'=>165,'returns'=>33,'revenue'=>132,'collectedVat'=>15,'returnedVat'=>3,'netVat'=>12,
            'netSalesBeforeTax'=>120,'costOfGoodsSold'=>88,'grossProfit'=>32,'netProfit'=>32,'profitIncludingVat'=>44];
        foreach($expected as $key=>$amount)if((int)round(($data['monthlyStats'][$key]??INF)*100)!==$amount*100)throw new RuntimeException("Importe incorrecto: $key");
        $pointKey=$annual?substr($today,0,7):$today;
        $points=array_column($data['financialSeries'],null,'key');
        foreach(['revenue'=>132,'collectedVat'=>15,'returnedVat'=>3,'netVat'=>12,'netSalesBeforeTax'=>120,'costOfGoodsSold'=>88,'grossProfit'=>32,'profitIncludingVat'=>44,'profit'=>44] as $key=>$amount){
            if((int)round(($points[$pointKey][$key]??INF)*100)!==$amount*100)throw new RuntimeException("Serie incorrecta: $key");
        }
        echo ($annual?'Anual':'Mensual').": estimación con IVA 44.00 y ganancia bruta sin IVA 32.00; devolución parcial correcta. Tablas temporales, sin cambios persistentes.\n";
    }catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
});
require __DIR__.'/../backend/api/dashboard.php';
