<?php
// CLI integration harness: never creates an authenticated HTTP session.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../backend/db.php';
require __DIR__.'/../backend/api-bootstrap.php';
$_SESSION['user_id']=(int)$conn->query('SELECT MIN(id) FROM users')->fetch_row()[0];
session_abort(); // discard the test session; keep only the in-process session array.
$_SERVER['REQUEST_METHOD']='GET';
$mode=$argv[1]??'dashboard';
if($mode==='annual')$_GET['is_annual']='true';
if($mode==='details'){
    $row=$conn->query('SELECT pi.purchase_id FROM purchase_items pi LEFT JOIN products p ON p.id=pi.product_id WHERE p.id IS NULL LIMIT 1')->fetch_assoc();
    if(!$row)$row=$conn->query('SELECT id purchase_id FROM purchases ORDER BY created_at ASC,id ASC LIMIT 1')->fetch_assoc();
    if(!$row)throw new RuntimeException('No hay ventas para comprobar.');
    $_GET=['action'=>'details','id'=>$row['purchase_id']];
}
ob_start();
register_shutdown_function(function()use($mode,$conn){
    $response=json_decode(ob_get_clean(),true);
    if(($response['status']??'')!=='success'){
        fwrite(STDERR,"La API no devolvió una respuesta correcta en $mode.\n");exit(1);
    }
    if(in_array($mode,['dashboard','annual'],true)){
        $data=$response;$stats=$data['monthlyStats'];$series=$data['financialSeries'];
        $cursor=new DateTimeImmutable($mode==='annual'?substr($stats['startDate'],0,7).'-01':substr($stats['startDate'],0,10));
        $last=new DateTimeImmutable(substr($stats['endDate'],0,10));$keys=[];
        while($cursor<=$last){$keys[]=$cursor->format($mode==='annual'?'Y-m':'Y-m-d');$cursor=$cursor->modify($mode==='annual'?'+1 month':'+1 day');}
        if(array_column($series,'key')!==$keys || substr($stats['endDate'],0,10)>$data['currentDate'])throw new RuntimeException('La serie contiene fechas incorrectas o futuras.');
        $totals=['revenue'=>0,'expenses'=>0,'profit'=>0];
        foreach($series as $point)foreach($totals as $key=>$_)$totals[$key]+=round((float)$point[$key]*100);
        foreach(['revenue'=>'revenue','expenses'=>'expenses','profit'=>'profitIncludingVat'] as $key=>$field){
            if($totals[$key]!==round((float)$stats[$field]*100))throw new RuntimeException("Total de $key no coincide con el dashboard.");
        }
        echo json_encode(['endpoint'=>$mode,'points'=>count($series),'totals_match'=>true],JSON_PRETTY_PRINT)."\n";
    }elseif($mode==='details'){
        $purchase=$response['data']['purchase'];$items=$response['data']['items'];
        $expected=(int)$conn->query('SELECT COUNT(*) FROM purchase_items WHERE purchase_id='.(int)$purchase['id'])->fetch_row()[0];
        if(count($items)!==$expected)throw new RuntimeException('Se perdió un producto histórico en los detalles.');
        foreach($items as $item)if(!$item['product_name'])throw new RuntimeException('Nombre histórico ausente.');
        echo "Detalle histórico: todos los productos y nombres conservados.\n";
    }
});
require __DIR__.'/../backend/api/'.($mode==='details'?'transactions.php':'dashboard.php');
