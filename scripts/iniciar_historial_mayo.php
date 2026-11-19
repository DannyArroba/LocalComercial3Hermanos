<?php
// One-time, local-only archival of older activity with an inventory carry-forward.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../backend/db.php';
const CUTOFF='2026-05-01 00:00:00';
const ACTION_KEY='inicio-mayo-2026-y-venta-42';
$directory=$argv[1]??'';
if(!is_dir($directory)||!is_file($directory.'/antes.sql')||filesize($directory.'/antes.sql')<1000)throw new RuntimeException('Se requiere directorio con respaldo completo antes.sql.');
function assertHistory($ok,$message){if(!$ok)throw new RuntimeException($message);}
function allRows($sql){global $conn;return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);}
function insertHistoryRow($table,$values){
    global $conn;$fields=implode('`,`',array_keys($values));$marks=implode(',',array_fill(0,count($values),'?'));
    $stmt=$conn->prepare("INSERT INTO `$table` (`$fields`) VALUES ($marks)");$args=array_values($values);
    $stmt->bind_param(str_repeat('s',count($args)),...$args);$stmt->execute();return (int)$conn->insert_id;
}
$conn->query("CREATE TABLE IF NOT EXISTS local_history_archives (
    id INT AUTO_INCREMENT PRIMARY KEY, action_key VARCHAR(100) NOT NULL UNIQUE,
    cutoff_date DATETIME NOT NULL, created_at DATETIME NOT NULL, summary_json LONGTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS local_history_archive_rows (
    archive_id INT NOT NULL, table_name VARCHAR(64) NOT NULL, record_id INT NOT NULL, row_json LONGTEXT NOT NULL,
    PRIMARY KEY(archive_id,table_name,record_id), FOREIGN KEY(archive_id) REFERENCES local_history_archives(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS local_history_id_changes (
    archive_id INT NOT NULL, table_name VARCHAR(64) NOT NULL, old_id INT NOT NULL, new_id INT NOT NULL,
    PRIMARY KEY(archive_id,table_name,old_id), FOREIGN KEY(archive_id) REFERENCES local_history_archives(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
assertHistory(!allRows("SELECT id FROM local_history_archives WHERE action_key='".ACTION_KEY."'"),'Este archivo ya se aplicó; no se repite.');
$conn->begin_transaction();
try{
    foreach(['purchases','purchase_items','sales_returns','sales_return_items','stock_purchases','stock_purchase_items','inventory_logs','demo_stock_ledger','demo_data_records','products'] as $table)$conn->query("SELECT * FROM `$table` FOR UPDATE");
    $stockBefore=allRows('SELECT id,stock FROM products ORDER BY id');
    $financialBefore=allRows("SELECT COUNT(*) n,SUM(total) total FROM purchases WHERE created_at>='".CUTOFF."'");
    $cut=CUTOFF;
    assertHistory((int)$conn->query("SELECT COUNT(*) FROM sales_returns r JOIN purchases p ON p.id=r.purchase_id WHERE p.created_at<'$cut' AND r.created_at>='$cut'")->fetch_row()[0]===0,'Hay devoluciones posteriores de ventas anteriores al corte.');
    assertHistory((int)$conn->query("SELECT COUNT(*) FROM stock_purchases WHERE created_at<'$cut' AND (paid_at>='$cut' OR received_at>='$cut')")->fetch_row()[0]===0,'Un abastecimiento cruza la fecha de corte.');
    $old=[
        'purchases'=>allRows("SELECT * FROM purchases WHERE created_at<'$cut'"),
        'purchase_items'=>allRows("SELECT i.* FROM purchase_items i JOIN purchases p ON p.id=i.purchase_id WHERE p.created_at<'$cut'"),
        'sales_returns'=>allRows("SELECT * FROM sales_returns WHERE created_at<'$cut'"),
        'sales_return_items'=>allRows("SELECT i.* FROM sales_return_items i JOIN sales_returns r ON r.id=i.return_id WHERE r.created_at<'$cut'"),
        'stock_purchases'=>allRows("SELECT * FROM stock_purchases WHERE created_at<'$cut'"),
        'stock_purchase_items'=>allRows("SELECT i.* FROM stock_purchase_items i JOIN stock_purchases p ON p.id=i.purchase_id WHERE p.created_at<'$cut'"),
        'inventory_logs'=>allRows("SELECT * FROM inventory_logs WHERE created_at<'$cut'"),
        'demo_stock_ledger'=>allRows("SELECT * FROM demo_stock_ledger WHERE occurred_at<'$cut'"),
    ];
    $opening=allRows("SELECT batch_id,product_id,SUM(quantity) quantity FROM demo_stock_ledger WHERE occurred_at<'$cut' GROUP BY batch_id,product_id");
    foreach($opening as $line)assertHistory((int)$line['quantity']>=0,'Saldo inicial reconstruido negativo.');
    $sale=allRows('SELECT * FROM purchases WHERE id=42');assertHistory(count($sale)===1,'La venta 42 ya no existe; revisar el cambio.');$sale=$sale[0];
    $saleItems=allRows('SELECT * FROM purchase_items WHERE purchase_id=42');
    $oldSaleReturns=allRows('SELECT * FROM sales_returns WHERE purchase_id=42');
    $newId=(int)$conn->query('SELECT MAX(id)+1 FROM purchases')->fetch_row()[0];
    $summary=['cutoff'=>CUTOFF,'archived_counts'=>array_map('count',$old),'sale_id_before'=>42,'sale_id_after'=>$newId,
        'stock_preserved'=>true,'post_may_financial_values_preserved'=>true];
    $archive=insertHistoryRow('local_history_archives',['action_key'=>ACTION_KEY,'cutoff_date'=>CUTOFF,
        'created_at'=>date('Y-m-d H:i:s'),'summary_json'=>json_encode($summary,JSON_THROW_ON_ERROR)]);
    foreach($old as $table=>$records)foreach($records as $row)insertHistoryRow('local_history_archive_rows',[
        'archive_id'=>$archive,'table_name'=>$table,'record_id'=>$row['id'],'row_json'=>json_encode($row,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    foreach(['purchases'=>[$sale],'purchase_items'=>$saleItems,'sales_returns'=>$oldSaleReturns] as $table=>$records)foreach($records as $row)insertHistoryRow('local_history_archive_rows',[
        'archive_id'=>$archive,'table_name'=>$table,'record_id'=>$row['id'],'row_json'=>json_encode($row,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
    $conn->query("DELETE i FROM sales_return_items i JOIN sales_returns r ON r.id=i.return_id WHERE r.created_at<'$cut'");
    $conn->query("DELETE FROM sales_returns WHERE created_at<'$cut'");
    $conn->query("DELETE i FROM purchase_items i JOIN purchases p ON p.id=i.purchase_id WHERE p.created_at<'$cut'");
    $conn->query("DELETE FROM purchases WHERE created_at<'$cut'");
    $conn->query("DELETE i FROM stock_purchase_items i JOIN stock_purchases p ON p.id=i.purchase_id WHERE p.created_at<'$cut'");
    $conn->query("DELETE FROM stock_purchases WHERE created_at<'$cut'");
    $conn->query("DELETE FROM inventory_logs WHERE created_at<'$cut'");
    $conn->query("DELETE FROM demo_stock_ledger WHERE occurred_at<'$cut'");
    $productIds=array_fill_keys(array_column($stockBefore,'id'),true);
    foreach($opening as $line){
        if(!isset($productIds[$line['product_id']]) && (int)$line['quantity']===0)continue;
        insertHistoryRow('demo_stock_ledger',['batch_id'=>$line['batch_id'],'product_id'=>$line['product_id'],'kind'=>'OPENING',
            'quantity'=>$line['quantity'],'balance'=>$line['quantity'],'occurred_at'=>'2026-05-01 08:00:00',
            'source_table'=>null,'source_id'=>null,'source_item_id'=>null]);
    }
    // Keep all child IDs and their return eligibility. Copy the parent, reattach children, then remove the old parent.
    $replacement=$sale;$replacement['id']=$newId;insertHistoryRow('purchases',$replacement);
    $conn->query("UPDATE purchase_items SET purchase_id=$newId WHERE purchase_id=42");
    $conn->query("UPDATE sales_returns SET purchase_id=$newId WHERE purchase_id=42");
    $conn->query("UPDATE demo_stock_ledger SET source_id=$newId WHERE source_table='purchases' AND source_id=42");
    $conn->query("UPDATE demo_data_records SET record_id=$newId WHERE table_name='purchases' AND record_id=42");
    $conn->query('DELETE FROM purchases WHERE id=42');
    insertHistoryRow('local_history_id_changes',['archive_id'=>$archive,'table_name'=>'purchases','old_id'=>42,'new_id'=>$newId]);
    assertHistory($stockBefore===allRows('SELECT id,stock FROM products ORDER BY id'),'El stock cambió durante el archivo.');
    assertHistory($financialBefore===allRows("SELECT COUNT(*) n,SUM(total) total FROM purchases WHERE created_at>='$cut'"),'Se alteraron ventas posteriores al corte.');
    assertHistory((int)$conn->query("SELECT COUNT(*) FROM products p LEFT JOIN(SELECT product_id,SUM(quantity) q FROM demo_stock_ledger GROUP BY product_id)l ON l.product_id=p.id WHERE l.q IS NULL OR l.q<>p.stock")->fetch_row()[0]===0,'El saldo de apertura no concilia con el inventario.');
    foreach(['purchases','stock_purchases','sales_returns','inventory_logs'] as $table)assertHistory((int)$conn->query("SELECT COUNT(*) FROM `$table` WHERE created_at<'$cut'")->fetch_row()[0]===0,'Quedó actividad anterior a mayo.');
    assertHistory((int)$conn->query('SELECT COUNT(*) FROM purchase_items WHERE purchase_id=42')->fetch_row()[0]===0,'Referencia de venta sin migrar.');
    $newSale=allRows("SELECT * FROM purchases WHERE id=$newId")[0];$newSale['id']=$sale['id'];assertHistory($newSale===$sale,'Se modificó el contenido del comprobante.');
    $conn->commit();
    file_put_contents($directory.'/resultado.json',json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
}catch(Throwable $e){$conn->rollback();throw $e;}
