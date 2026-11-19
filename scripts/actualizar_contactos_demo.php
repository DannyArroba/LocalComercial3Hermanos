<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$backup=$argv[2]??'';
if(($argv[1]??'')!=='--apply'||!is_file($backup)||filesize($backup)<1000)throw new RuntimeException('Uso: --apply RESPALDO_SQL_COMPLETO');
$auditPath=dirname($backup).'/cambios-contactos.json';
if(file_exists($auditPath))throw new RuntimeException('Esta corrección ya fue aplicada con este respaldo.');
require __DIR__.'/../backend/db.php';require __DIR__.'/lib/contact_data.php';
$conn->begin_transaction();
try{
    foreach(['customers','purchases','sales_returns'] as $table)$conn->query("SELECT id FROM `$table` FOR UPDATE");
    $beforeTotals=$conn->query('SELECT COUNT(*) n,SUM(total) total,MAX(id) last_id FROM purchases')->fetch_assoc();
    $beforeStock=$conn->query('SELECT id,stock FROM products ORDER BY id')->fetch_all(MYSQLI_ASSOC);
    $customers=$conn->query("SELECT * FROM customers WHERE email LIKE '%prueba.nexar%' ORDER BY id")->fetch_all(MYSQLI_ASSOC);
    $used=array_fill_keys(array_column($conn->query('SELECT phone FROM customers WHERE phone IS NOT NULL')->fetch_all(MYSQLI_ASSOC),'phone'),true);
    $audit=[];$saleCount=0;$returnCount=0;
    $customerStmt=$conn->prepare('UPDATE customers SET email=?,phone=? WHERE id=?');
    $salesStmt=$conn->prepare('UPDATE purchases SET customer_email=?,customer_phone=? WHERE id=?');
    $returnStmt=$conn->prepare('UPDATE sales_returns SET customer_phone=? WHERE id=?');
    $getSales=$conn->prepare('SELECT * FROM purchases WHERE customer_id=? OR (customer_id IS NULL AND BINARY customer_idnumber=BINARY ?)');
    $getReturns=$conn->prepare('SELECT * FROM sales_returns WHERE BINARY customer_idnumber=BINARY ?');
    foreach($customers as $customer){
        $id=(int)$customer['id'];$ci=$customer['id_number'];
        $domain=strtolower(substr(strrchr($customer['email'],'@'),1));
        $email=fixtureEmail($customer['name'],$domain);$phone=fixturePhone($used);
        $audit[]=['table'=>'customers','id'=>$id,'before'=>$customer,'after'=>['email'=>$email,'phone'=>$phone]];
        $customerStmt->bind_param('ssi',$email,$phone,$id);$customerStmt->execute();
        $getSales->bind_param('is',$id,$ci);$getSales->execute();
        foreach($getSales->get_result()->fetch_all(MYSQLI_ASSOC) as $sale){
            $sid=(int)$sale['id'];$audit[]=['table'=>'purchases','id'=>$sid,'before'=>$sale,'after'=>['customer_email'=>$email,'customer_phone'=>$phone]];
            $salesStmt->bind_param('ssi',$email,$phone,$sid);$salesStmt->execute();$saleCount++;
        }
        $getReturns->bind_param('s',$ci);$getReturns->execute();
        foreach($getReturns->get_result()->fetch_all(MYSQLI_ASSOC) as $return){
            $rid=(int)$return['id'];$audit[]=['table'=>'sales_returns','id'=>$rid,'before'=>$return,'after'=>['customer_phone'=>$phone]];
            $returnStmt->bind_param('si',$phone,$rid);$returnStmt->execute();$returnCount++;
        }
    }
    $tagged=(int)$conn->query("SELECT (SELECT COUNT(*) FROM customers WHERE email LIKE '%prueba.nexar%')+(SELECT COUNT(*) FROM purchases WHERE customer_email LIKE '%prueba.nexar%')")->fetch_row()[0];
    $zeroPhones=(int)$conn->query("SELECT (SELECT COUNT(*) FROM customers WHERE phone LIKE '0900%')+(SELECT COUNT(*) FROM purchases WHERE customer_phone LIKE '0900%')")->fetch_row()[0];
    if($tagged||$zeroPhones)throw new RuntimeException('Quedaron correos o teléfonos pendientes.');
    foreach($audit as $change)if($change['table']==='customers'){
        $id=$change['id'];$expected=$change['after'];
        $mismatch=$conn->query("SELECT COUNT(*) FROM purchases p JOIN customers c ON c.id=p.customer_id WHERE c.id=$id AND (p.customer_email<>c.email OR p.customer_phone<>c.phone)")->fetch_row()[0];
        if((int)$mismatch!==0)throw new RuntimeException('Un comprobante no coincide con su cliente.');
    }
    if($beforeTotals!==$conn->query('SELECT COUNT(*) n,SUM(total) total,MAX(id) last_id FROM purchases')->fetch_assoc()||$beforeStock!==$conn->query('SELECT id,stock FROM products ORDER BY id')->fetch_all(MYSQLI_ASSOC))throw new RuntimeException('Se alteraron importes o stock.');
    $summary=['customers_updated'=>count($customers),'sale_snapshots_updated'=>$saleCount,'returns_updated'=>$returnCount,
        'remaining_tagged_emails'=>$tagged,'remaining_placeholder_phones'=>$zeroPhones,'stock_and_sales_unchanged'=>true];
    if(file_put_contents($auditPath,json_encode(['summary'=>$summary,'changes'=>$audit],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false)throw new RuntimeException('No se pudo guardar la auditoría privada.');
    $conn->commit();echo json_encode($summary,JSON_PRETTY_PRINT)."\n";
}catch(Throwable $error){$conn->rollback();throw $error;}
