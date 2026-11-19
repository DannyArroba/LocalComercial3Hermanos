<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(($argv[1]??'')!=='--apply'||!is_file($argv[2]??'')||filesize($argv[2])<1000)throw new RuntimeException('Se requiere --apply RESPALDO_SQL_COMPLETO.');
require __DIR__.'/../backend/db.php';
$conn->begin_transaction();
try{
    $conn->query("UPDATE users SET name=CONCAT(UPPER(LEFT(SUBSTRING_INDEX(email,'@',1),1)),SUBSTRING(SUBSTRING_INDEX(email,'@',1),2)) WHERE name IS NULL OR TRIM(name)=''");
    $counts=['user_names'=>$conn->affected_rows];
    foreach(['purchases'=>'served_by_name','sales_returns'=>'handled_by_name','stock_purchases'=>'requested_by_name'] as $table=>$field){
        $conn->query("UPDATE `$table` t JOIN users u ON u.id=t.user_id SET t.`$field`=u.name WHERE t.`$field` IS NULL OR TRIM(t.`$field`)=''");
        $counts[$table]=$conn->affected_rows;
    }
    $conn->commit();echo json_encode(['status'=>'success','backfilled'=>$counts],JSON_PRETTY_PRINT)."\n";
}catch(Throwable $e){$conn->rollback();throw $e;}
