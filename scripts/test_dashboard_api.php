<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../backend/db.php';
require __DIR__ . '/../backend/api-bootstrap.php';
$_SESSION['user_id']=(int)$conn->query('SELECT MIN(id) FROM users')->fetch_row()[0];
session_abort();
$_SERVER['REQUEST_METHOD']='GET';
$mode=$argv[1]??'current';
$_GET=match($mode){
    'annual'=>['is_annual'=>'true'],
    'past'=>['year'=>'2026','month'=>'10'],
    'returns'=>['year'=>'2026','month'=>'9'],
    'future'=>['year'=>'2099','month'=>'12'],
    'start'=>['year'=>'2020','month'=>'1'],
    'critical-zero'=>['critical_stock_threshold'=>'0','low_stock_min'=>'6','low_stock_max'=>'10'],
    'stock-boundaries-zero'=>['critical_stock_threshold'=>'0','low_stock_min'=>'6','low_stock_max'=>'10'],
    'soon-range'=>['critical_stock_threshold'=>'5','low_stock_min'=>'8','low_stock_max'=>'15'],
    default=>[],
};
$fixtureRows=[];
if(in_array($mode,['stock-boundaries','stock-boundaries-zero'],true)){
    $conn->begin_transaction();
    try{
        $fixtureRows=$conn->query('SELECT id,stock FROM products ORDER BY id LIMIT 5 FOR UPDATE')->fetch_all(MYSQLI_ASSOC);
        if(count($fixtureRows)!==5)throw new RuntimeException('Five fixture products are required.');
        $quantities=[0,5,6,10,11];
        $stmt=$conn->prepare('UPDATE products SET stock=? WHERE id=?');
        foreach($fixtureRows as $index=>$row){$quantity=$quantities[$index];$id=(int)$row['id'];$stmt->bind_param('ii',$quantity,$id);$stmt->execute();}
    }catch(Throwable $e){$conn->rollback();throw $e;}
}
ob_start();
register_shutdown_function(function() use($mode,$conn,$fixtureRows){
    $data=json_decode(ob_get_clean(),true);
    $failed=false;
    try {
        if(($data['status']??'')!=='success')throw new RuntimeException('API failed');
        $stats=$data['monthlyStats'];$series=$data['financialSeries'];
        if(substr($stats['endDate'],0,10)>$data['currentDate'])throw new RuntimeException('Future dates returned');
        if(substr($stats['startDate'],0,10)<$data['historyStart'])throw new RuntimeException('Dates before history returned');
        $cursor=new DateTimeImmutable($stats['isAnnual']?substr($stats['startDate'],0,7).'-01':substr($stats['startDate'],0,10));
        $last=new DateTimeImmutable(substr($stats['endDate'],0,10));$keys=[];
        while($cursor<=$last){$keys[]=$cursor->format($stats['isAnnual']?'Y-m':'Y-m-d');$cursor=$cursor->modify($stats['isAnnual']?'+1 month':'+1 day');}
        if(array_column($series,'key')!==$keys)throw new RuntimeException('Incomplete or future chart points');
        foreach(['revenue'=>'revenue','expenses'=>'expenses','profit'=>'profitIncludingVat','grossProfit'=>'grossProfit'] as $key=>$field){
            $total=0;foreach($series as $p)$total+=(int)round($p[$key]*100);
            if($total!==(int)round($stats[$field]*100))throw new RuntimeException('Chart totals differ from summary');
        }
        $start=$conn->real_escape_string($stats['startDate']);$end=$conn->real_escape_string($stats['endDate']);
        $salesBase=$conn->query("SELECT COALESCE(SUM(subtotal),0) FROM purchases WHERE created_at BETWEEN '$start' AND '$end'")->fetch_row()[0];
        $refundBase=$conn->query("SELECT COALESCE(SUM(i.line_total-i.tax_amount),0) FROM sales_return_items i JOIN sales_returns r ON r.id=i.return_id WHERE r.created_at BETWEEN '$start' AND '$end'")->fetch_row()[0];
        $profit=(int)round((float)$salesBase*100)-(int)round((float)$refundBase*100)-(int)round((float)$stats['costOfGoodsSold']*100);
        if($profit!==(int)round($stats['grossProfit']*100) || $stats['grossProfit']!==$stats['netProfit'])throw new RuntimeException('VAT or partial returns distort gross profit');
        if((int)round($stats['profitIncludingVat']*100)!==$profit+(int)round($stats['netVat']*100))throw new RuntimeException('VAT-inclusive estimate does not reconcile');
        $settings=$data['stockSettings'];
        foreach(['lowStockProducts'=>"stock <= ".(int)$settings['criticalStockThreshold'],
            'criticalStockProducts'=>"stock BETWEEN ".(int)$settings['lowStockMin']." AND ".(int)$settings['lowStockMax']] as $key=>$where){
            $expected=array_map('intval',array_column($conn->query("SELECT id FROM products WHERE $where ORDER BY stock ASC,name ASC")->fetch_all(MYSQLI_ASSOC),'id'));
            $actual=array_map('intval',array_column($data[$key],'id'));
            if($expected!==$actual)throw new RuntimeException('Incorrect stock classification');
        }
        if((int)$data['stats']['lowStockCount']!==count($data['lowStockProducts']))throw new RuntimeException('Critical count differs from list');
        if($fixtureRows){
            $criticalIds=array_map('intval',array_column($data['lowStockProducts'],'id'));
            $soonIds=array_map('intval',array_column($data['criticalStockProducts'],'id'));
            $quantities=[0,5,6,10,11];
            foreach($fixtureRows as $index=>$row){
                $id=(int)$row['id'];$quantity=$quantities[$index];
                $expectedCritical=$quantity<=($mode==='stock-boundaries-zero'?0:5);
                $expectedSoon=$quantity>=6 && $quantity<=10;
                if(in_array($id,$criticalIds,true)!==$expectedCritical || in_array($id,$soonIds,true)!==$expectedSoon)throw new RuntimeException('Stock boundary classification failed.');
            }
        }
        foreach($data['availablePeriods'] as $period)if($period['period']<'2026-05' || $period['period']>substr($data['currentDate'],0,7))throw new RuntimeException('Unavailable history month');
        echo json_encode(['case'=>$mode,'start'=>$stats['startDate'],'end'=>$stats['endDate'],'points'=>count($series),'critical'=>count($data['lowStockProducts']),'soon'=>count($data['criticalStockProducts']),'totals_match'=>true],JSON_UNESCAPED_UNICODE)."\n";
    }catch(Throwable $e){$failed=true;fwrite(STDERR,$e->getMessage()."\n");}
    finally{
        if($fixtureRows){
            $conn->rollback();
            foreach($fixtureRows as $row){
                $stock=$conn->query('SELECT stock FROM products WHERE id='.(int)$row['id'])->fetch_row()[0];
                if((int)$stock!==(int)$row['stock']){$failed=true;fwrite(STDERR,"Fixture stock was not restored.\n");}
            }
            if(!$failed)echo "Stock boundaries verified; all fixture changes rolled back.\n";
        }
    }
    if($failed)exit(1);
});
require __DIR__ . '/../backend/api/dashboard.php';
