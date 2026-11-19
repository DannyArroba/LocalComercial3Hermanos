<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../backend/lib/dashboard_options.php';
function expectSame($actual, $expected, $message) {
    if ($actual !== $expected) throw new RuntimeException($message . ': ' . json_encode($actual));
}
$today = '2026-11-18'; $history = '2026-05-01'; $latest = ['year'=>2026,'month'=>11];
$current = dashboardWindow([], $today, $history, $latest);
expectSame($current['endDate'], '2026-11-18 23:59:59', 'Current month stops on today');
expectSame($current['startDate'], '2026-11-01 00:00:00', 'Current month begins on first day');
$past = dashboardWindow(['year'=>'2026','month'=>'10'], $today, $history, $latest);
expectSame($past['endDate'], '2026-10-31 23:59:59', 'Past month remains complete');
$future = dashboardWindow(['year'=>'2030','month'=>'12'], $today, $history, $latest);
expectSame([$future['year'],$future['month'],$future['endDate']], [2026,11,'2026-11-18 23:59:59'], 'Future requests normalize to current month');
$old = dashboardWindow(['year'=>'2020','month'=>'1'], $today, $history, $latest);
expectSame([$old['year'],$old['month'],$old['startDate']], [2026,5,'2026-05-01 00:00:00'], 'Requests before history normalize to first month');
$annual = dashboardWindow(['is_annual'=>'true'], $today, $history, $latest);
expectSame([$annual['startDate'],$annual['endDate']], ['2026-05-01 00:00:00','2026-11-18 23:59:59'], 'Annual view shows elapsed history only');
$leap = dashboardWindow(['year'=>'2024','month'=>'2'], '2026-11-18', '2023-05-01', $latest);
expectSame($leap['endDate'], '2024-02-29 23:59:59', 'Leap month stays complete');
$settings=dashboardStockSettings([]);
expectSame($settings, ['criticalStockThreshold'=>5,'lowStockMin'=>6,'lowStockMax'=>10], 'Stock defaults are separate');
$criticalZero=dashboardStockSettings(['critical_stock_threshold'=>'0','low_stock_min'=>'6','low_stock_max'=>'10']);
expectSame($criticalZero['criticalStockThreshold'], 0, 'Zero threshold is supported');
expectSame([$criticalZero['lowStockMin'],$criticalZero['lowStockMax']], [6,10], 'Changing critical limit does not change soon range');
$soon=dashboardStockSettings(['critical_stock_threshold'=>'5','low_stock_min'=>'8','low_stock_max'=>'15']);
expectSame($soon, ['criticalStockThreshold'=>5,'lowStockMin'=>8,'lowStockMax'=>15], 'Changing soon range preserves critical limit');
$invalid=dashboardStockSettings(['critical_stock_threshold'=>'bad','low_stock_min'=>'12','low_stock_max'=>'3']);
expectSame($invalid, ['criticalStockThreshold'=>5,'lowStockMin'=>12,'lowStockMax'=>12], 'Invalid inputs do not produce inverted range');
echo "Dashboard options: 12 boundary and independent-settings assertions passed.\n";
