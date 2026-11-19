<?php

function dashboardInteger(array $query, string $key, int $default, int $minimum, int $maximum): int {
    $value = filter_var($query[$key] ?? null, FILTER_VALIDATE_INT);
    if ($value === false || $value === null) $value = $default;
    return max($minimum, min($maximum, $value));
}

/** Bounds both the aggregates and the plotted dates to elapsed Ecuador calendar dates. */
function dashboardWindow(array $query, string $today, string $historyStart, array $latestPeriod): array {
    $firstMonth = substr($historyStart, 0, 7);
    $lastMonth = substr($today, 0, 7);
    $firstYear = (int)substr($firstMonth, 0, 4);
    $lastYear = (int)substr($lastMonth, 0, 4);
    $year = dashboardInteger($query, 'year', (int)$latestPeriod['year'], $firstYear, $lastYear);
    $month = dashboardInteger($query, 'month', (int)$latestPeriod['month'], 1, 12);
    $period = max($firstMonth, min($lastMonth, sprintf('%04d-%02d', $year, $month)));
    $year = (int)substr($period, 0, 4);
    $month = (int)substr($period, 5, 2);
    $isAnnual = ($query['is_annual'] ?? '') === 'true';
    $start = $isAnnual ? sprintf('%04d-01-01', $year) : $period . '-01';
    $end = $isAnnual ? sprintf('%04d-12-31', $year) : (new DateTimeImmutable($start))->format('Y-m-t');
    return [
        'year' => $year, 'month' => $month, 'isAnnual' => $isAnnual,
        'startDate' => max($historyStart, $start) . ' 00:00:00',
        'endDate' => min($today, $end) . ' 23:59:59',
    ];
}

function dashboardStockSettings(array $query): array {
    $critical = dashboardInteger($query, 'critical_stock_threshold', 5, 0, 999);
    $minimum = dashboardInteger($query, 'low_stock_min', 6, 0, 999);
    $maximum = dashboardInteger($query, 'low_stock_max',
        dashboardInteger($query, 'low_stock_threshold', 10, 0, 999), 0, 999);
    return [
        'criticalStockThreshold' => $critical,
        'lowStockMin' => $minimum,
        'lowStockMax' => max($minimum, $maximum),
    ];
}
