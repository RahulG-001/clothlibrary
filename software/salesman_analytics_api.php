<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');
require_once('salesman_auth.php');

function analytics_table_exists($con, $table) {
    $t = mysqli_real_escape_string($con, $table);
    $r = mysqli_query($con, "SHOW TABLES LIKE '".$t."'");
    return $r && mysqli_num_rows($r) > 0;
}

function analytics_column_exists($con, $table, $column) {
    $t = mysqli_real_escape_string($con, $table);
    $c = mysqli_real_escape_string($con, $column);
    $r = mysqli_query($con, "SHOW COLUMNS FROM `".$t."` LIKE '".$c."'");
    return $r && mysqli_num_rows($r) > 0;
}

function analytics_month_label($dt) {
    return $dt->format('M Y');
}
function analytics_day_label($dt) {
    return $dt->format('d M');
}
function analytics_week_label_from_key($k) {
    // key format: YYYY-WW
    $parts = explode('-', (string)$k);
    if (count($parts) === 2) {
        return 'W'.$parts[1].' '.$parts[0];
    }
    return (string)$k;
}

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
];

$salesman = salesman_require_auth($con);
$salesmanId = isset($salesman['id']) ? (int)$salesman['id'] : 0;
if ($salesmanId <= 0) {
    $response['message'] = 'Salesman not found.';
    echo json_encode($response);
    exit;
}

$view = isset($_GET['view']) ? strtolower(trim((string)$_GET['view'])) : 'monthly';
if (!in_array($view, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
    $view = 'monthly';
}

$hasSalesOrder = analytics_table_exists($con, 'sales_order');
$hasSalesOrderItem = analytics_table_exists($con, 'sales_order_item');
$hasIndiadata = analytics_table_exists($con, 'indiadata');
$hasPrice = analytics_column_exists($con, 'sales_order_item', 'price');
$hasMeters = analytics_column_exists($con, 'sales_order_item', 'meters');
$hasQty = analytics_column_exists($con, 'sales_order_item', 'quantity');

if (!$hasSalesOrder || !$hasSalesOrderItem || !$hasIndiadata) {
    $response['success'] = true;
    $response['message'] = 'Analytics tables are missing.';
    $response['data'] = [
        'view' => $view,
        'salesman' => [
            'id' => $salesmanId,
            'name' => trim((string)$salesman['first_name'].' '.(string)$salesman['last_name']),
        ],
        'totals' => [
            'order_value' => 0,
            'pcs' => 0,
            'meters' => 0,
            'orders' => 0,
        ],
        'chart' => [
            'labels' => [],
            'order_value' => [],
            'pcs' => [],
            'meters' => [],
        ],
        'breakdown' => [],
    ];
    echo json_encode($response);
    exit;
}

$pcsNormExpr = "UPPER(REPLACE(TRIM(p.type),' ',''))";
$pcsInList = "('PCS','PC','PIECE','PIECES','PC.')";
$pcsCase = "CASE WHEN ".$pcsNormExpr." IN ".$pcsInList." THEN 1 ELSE 0 END";

$orderValueExpr = $hasPrice ? "SUM(COALESCE(oi.price,0))" : "0";
$qtyExpr = $hasQty ? "COALESCE(oi.quantity,0)" : "0";
$metersExpr = $hasMeters ? "COALESCE(oi.meters,0)" : "0";
$pcsAggExpr = "SUM(CASE WHEN (".$pcsCase.")=1 THEN ".$qtyExpr." ELSE 0 END)";
$metersAggExpr = "SUM(CASE WHEN (".$pcsCase.")=1 THEN 0 ELSE ".$metersExpr." END)";

$detailKeys = [];
$labels = [];
$orderValuesByKey = [];
$pcsByKey = [];
$metersByKey = [];
$ordersByKey = [];

$now = new DateTime('now');
if ($view === 'daily') {
    $dayStart = new DateTime('today');
    $dayStart->modify('-29 days');
    $dayEnd = new DateTime('tomorrow');

    $rangeStartSql = $dayStart->format('Y-m-d').' 00:00:00';
    $rangeEndSql = $dayEnd->format('Y-m-d').' 00:00:00';

    $cursor = clone $dayStart;
    while ($cursor < $dayEnd) {
        $key = $cursor->format('Y-m-d');
        $detailKeys[] = $key;
        $labels[] = analytics_day_label($cursor);
        $orderValuesByKey[$key] = 0.0;
        $pcsByKey[$key] = 0.0;
        $metersByKey[$key] = 0.0;
        $ordersByKey[$key] = 0;
        $cursor->modify('+1 day');
    }

    $sql = "
      SELECT
        DATE_FORMAT(o.created_at, '%Y-%m-%d') AS period_key,
        COUNT(DISTINCT o.id) AS orders_count,
        ".$orderValueExpr." AS order_value,
        ".$pcsAggExpr." AS pcs_sold,
        ".$metersAggExpr." AS meters_sold
      FROM sales_order o
      INNER JOIN sales_order_item oi ON oi.order_id = o.id
      INNER JOIN indiadata p ON p.itemcode = oi.itemcode
      WHERE
        o.salesman_id = '".$salesmanId."'
        AND LOWER(TRIM(o.status)) = 'placed'
        AND o.created_at >= '".mysqli_real_escape_string($con, $rangeStartSql)."'
        AND o.created_at <  '".mysqli_real_escape_string($con, $rangeEndSql)."'
      GROUP BY period_key
      ORDER BY period_key ASC
    ";
} elseif ($view === 'weekly') {
    $weekStart = new DateTime('monday this week');
    $weekStart->setTime(0, 0, 0);
    $weekStart->modify('-11 weeks');
    $weekEnd = new DateTime('monday next week');
    $weekEnd->setTime(0, 0, 0);

    $rangeStartSql = $weekStart->format('Y-m-d').' 00:00:00';
    $rangeEndSql = $weekEnd->format('Y-m-d').' 00:00:00';

    $cursor = clone $weekStart;
    while ($cursor < $weekEnd) {
        $key = $cursor->format('o-W');
        $detailKeys[] = $key;
        $labels[] = analytics_week_label_from_key($key);
        $orderValuesByKey[$key] = 0.0;
        $pcsByKey[$key] = 0.0;
        $metersByKey[$key] = 0.0;
        $ordersByKey[$key] = 0;
        $cursor->modify('+1 week');
    }

    $sql = "
      SELECT
        DATE_FORMAT(o.created_at, '%x-%v') AS period_key,
        COUNT(DISTINCT o.id) AS orders_count,
        ".$orderValueExpr." AS order_value,
        ".$pcsAggExpr." AS pcs_sold,
        ".$metersAggExpr." AS meters_sold
      FROM sales_order o
      INNER JOIN sales_order_item oi ON oi.order_id = o.id
      INNER JOIN indiadata p ON p.itemcode = oi.itemcode
      WHERE
        o.salesman_id = '".$salesmanId."'
        AND LOWER(TRIM(o.status)) = 'placed'
        AND o.created_at >= '".mysqli_real_escape_string($con, $rangeStartSql)."'
        AND o.created_at <  '".mysqli_real_escape_string($con, $rangeEndSql)."'
      GROUP BY period_key
      ORDER BY period_key ASC
    ";
} elseif ($view === 'monthly') {
    $monthStart = new DateTime('first day of this month');
    $monthStart->modify('-11 months');
    $monthEnd = new DateTime('first day of next month');

    $rangeStartSql = $monthStart->format('Y-m-d').' 00:00:00';
    $rangeEndSql = $monthEnd->format('Y-m-d').' 00:00:00';

    $cursor = clone $monthStart;
    while ($cursor < $monthEnd) {
        $key = $cursor->format('Y-m');
        $detailKeys[] = $key;
        $labels[] = analytics_month_label($cursor);
        $orderValuesByKey[$key] = 0.0;
        $pcsByKey[$key] = 0.0;
        $metersByKey[$key] = 0.0;
        $ordersByKey[$key] = 0;
        $cursor->modify('+1 month');
    }

    $sql = "
      SELECT
        DATE_FORMAT(o.created_at, '%Y-%m') AS period_key,
        COUNT(DISTINCT o.id) AS orders_count,
        ".$orderValueExpr." AS order_value,
        ".$pcsAggExpr." AS pcs_sold,
        ".$metersAggExpr." AS meters_sold
      FROM sales_order o
      INNER JOIN sales_order_item oi ON oi.order_id = o.id
      INNER JOIN indiadata p ON p.itemcode = oi.itemcode
      WHERE
        o.salesman_id = '".$salesmanId."'
        AND LOWER(TRIM(o.status)) = 'placed'
        AND o.created_at >= '".mysqli_real_escape_string($con, $rangeStartSql)."'
        AND o.created_at <  '".mysqli_real_escape_string($con, $rangeEndSql)."'
      GROUP BY period_key
      ORDER BY period_key ASC
    ";
} else {
    $thisYear = (int)$now->format('Y');
    $minYear = $thisYear - 9;
    for ($y = $minYear; $y <= $thisYear; $y++) {
        $key = (string)$y;
        $detailKeys[] = $key;
        $labels[] = $key;
        $orderValuesByKey[$key] = 0.0;
        $pcsByKey[$key] = 0.0;
        $metersByKey[$key] = 0.0;
        $ordersByKey[$key] = 0;
    }

    $startYearSql = $minYear.'-01-01 00:00:00';
    $endYearSql = ($thisYear + 1).'-01-01 00:00:00';

    $sql = "
      SELECT
        YEAR(o.created_at) AS period_key,
        COUNT(DISTINCT o.id) AS orders_count,
        ".$orderValueExpr." AS order_value,
        ".$pcsAggExpr." AS pcs_sold,
        ".$metersAggExpr." AS meters_sold
      FROM sales_order o
      INNER JOIN sales_order_item oi ON oi.order_id = o.id
      INNER JOIN indiadata p ON p.itemcode = oi.itemcode
      WHERE
        o.salesman_id = '".$salesmanId."'
        AND LOWER(TRIM(o.status)) = 'placed'
        AND o.created_at >= '".mysqli_real_escape_string($con, $startYearSql)."'
        AND o.created_at <  '".mysqli_real_escape_string($con, $endYearSql)."'
      GROUP BY period_key
      ORDER BY period_key ASC
    ";
}

$res = mysqli_query($con, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $key = (string)$row['period_key'];
        if (!isset($orderValuesByKey[$key])) {
            continue;
        }
        $orderValuesByKey[$key] = round((float)($row['order_value'] ?? 0), 2);
        $pcsByKey[$key] = round((float)($row['pcs_sold'] ?? 0), 2);
        $metersByKey[$key] = round((float)($row['meters_sold'] ?? 0), 2);
        $ordersByKey[$key] = (int)($row['orders_count'] ?? 0);
    }
}

$totals = [
    'order_value' => 0.0,
    'pcs' => 0.0,
    'meters' => 0.0,
    'orders' => 0,
];
$chartOrderValues = [];
$chartPcs = [];
$chartMeters = [];
$breakdown = [];

for ($i = 0; $i < count($detailKeys); $i++) {
    $k = $detailKeys[$i];
    $label = $labels[$i];
    $ov = (float)$orderValuesByKey[$k];
    $pcs = (float)$pcsByKey[$k];
    $m = (float)$metersByKey[$k];
    $cnt = (int)$ordersByKey[$k];

    $totals['order_value'] += $ov;
    $totals['pcs'] += $pcs;
    $totals['meters'] += $m;
    $totals['orders'] += $cnt;

    $chartOrderValues[] = round($ov, 2);
    $chartPcs[] = round($pcs, 2);
    $chartMeters[] = round($m, 2);

    $breakdown[] = [
        'key' => $k,
        'label' => $label,
        'order_value' => round($ov, 2),
        'pcs' => round($pcs, 2),
        'meters' => round($m, 2),
        'orders' => $cnt,
    ];
}

$totals['order_value'] = round((float)$totals['order_value'], 2);
$totals['pcs'] = round((float)$totals['pcs'], 2);
$totals['meters'] = round((float)$totals['meters'], 2);

$salesmanName = trim((string)($salesman['first_name'] ?? '').' '.(string)($salesman['last_name'] ?? ''));
if ($salesmanName === '') {
    $salesmanName = 'Salesman #'.$salesmanId;
}

$response['success'] = true;
$response['message'] = 'Salesman analytics fetched successfully.';
$response['data'] = [
    'view' => $view,
    'salesman' => [
        'id' => $salesmanId,
        'name' => $salesmanName,
    ],
    'totals' => $totals,
    'chart' => [
        'labels' => $labels,
        'order_value' => $chartOrderValues,
        'pcs' => $chartPcs,
        'meters' => $chartMeters,
    ],
    'breakdown' => $breakdown,
];

echo json_encode($response);
exit;

