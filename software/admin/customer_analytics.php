<?php
require_once('db.php');
include('auth.php');
require_once(__DIR__.'/../product_stock_helpers.php');

function admin_table_exists($con, $table) {
    $t = mysqli_real_escape_string($con, $table);
    $r = mysqli_query($con, "SHOW TABLES LIKE '".$t."'");
    return $r && mysqli_num_rows($r) > 0;
}

function admin_column_exists($con, $table, $column) {
    $t = mysqli_real_escape_string($con, $table);
    $c = mysqli_real_escape_string($con, $column);
    $r = mysqli_query($con, "SHOW COLUMNS FROM `".$t."` LIKE '".$c."'");
    return $r && mysqli_num_rows($r) > 0;
}

function analytics_week_label($weekStart) {
    // ISO week label
    /** @var DateTime $weekStart */
    $wk = $weekStart->format('W');
    $yr = $weekStart->format('o');
    return 'W'.$wk.' '.$yr;
}

$view = isset($_GET['view']) ? strtolower((string)$_GET['view']) : 'monthly';
if (!in_array($view, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
    $view = 'monthly';
}

// Range + label
$rangeStartSql = '';
$rangeEndSql = '';
$periodLabel = '';

// Default: current period
$now = new DateTime('now');
if ($view === 'daily') {
    $ts = new DateTime('today');
    $te = new DateTime('tomorrow');
    $rangeStartSql = $ts->format('Y-m-d').' 00:00:00';
    $rangeEndSql = $te->format('Y-m-d').' 00:00:00';
    $periodLabel = $ts->format('d M Y');
} elseif ($view === 'weekly') {
    $ws = new DateTime('monday this week');
    $ws->setTime(0,0,0);
    $we = new DateTime('monday next week');
    $we->setTime(0,0,0);
    $rangeStartSql = $ws->format('Y-m-d').' 00:00:00';
    $rangeEndSql = $we->format('Y-m-d').' 00:00:00';
    $periodLabel = analytics_week_label($ws);
} elseif ($view === 'monthly') {
    $ms = new DateTime('first day of this month');
    $me = new DateTime('first day of next month');
    $rangeStartSql = $ms->format('Y-m-d').' 00:00:00';
    $rangeEndSql = $me->format('Y-m-d').' 00:00:00';
    $periodLabel = $ms->format('M Y');
} else {
    $y = (int)$now->format('Y');
    $rangeStartSql = $y.'-01-01 00:00:00';
    $rangeEndSql = ($y+1).'-01-01 00:00:00';
    $periodLabel = (string)$y;
}

$hasSalesOrder = admin_table_exists($con, 'sales_order');
$hasSalesOrderItem = admin_table_exists($con, 'sales_order_item');
$hasIndiadata = admin_table_exists($con, 'indiadata');

$hasLinePrice = admin_column_exists($con, 'sales_order_item', 'price');
$hasMeters = admin_column_exists($con, 'sales_order_item', 'meters');
$hasQty = admin_column_exists($con, 'sales_order_item', 'quantity');

$pcsNormExpr = "UPPER(REPLACE(TRIM(p.type),' ',''))";
$pcsInList = "('PCS','PC','PIECE','PIECES','PC.')";
$pcsCase = "CASE WHEN ".$pcsNormExpr." IN ".$pcsInList." THEN 1 ELSE 0 END";

$qtyExpr = $hasQty ? "COALESCE(oi.quantity,0)" : "0";
$metersExpr = $hasMeters ? "COALESCE(oi.meters,0)" : "0";
$nonPcsQtyExpr = $hasMeters ? $metersExpr : $qtyExpr;

$orderValueExpr = $hasLinePrice
    ? "SUM(COALESCE(oi.price,0) * (CASE WHEN (".$pcsCase.")=1 THEN ".$qtyExpr." ELSE ".$nonPcsQtyExpr." END))"
    : "0";
$pcsAggExpr = "SUM(CASE WHEN (".$pcsCase.")=1 THEN ".$qtyExpr." ELSE 0 END)";
$metersAggExpr = "SUM(CASE WHEN (".$pcsCase.")=1 THEN 0 ELSE ".$nonPcsQtyExpr." END)";

// Customer dropdown: load customers that have placed orders
$customerKey = isset($_GET['customer_userid']) ? trim((string)$_GET['customer_userid']) : '';
$customers = [];
if ($hasSalesOrder && $hasIndiadata) {
    $sqlCustomers = "
      SELECT DISTINCT o.user_id AS customer_key,
             u.name AS customer_name
      FROM sales_order o
      LEFT JOIN users u ON (u.id = o.user_id OR u.userid = o.user_id)
      WHERE LOWER(TRIM(o.status)) = 'placed'
      ORDER BY COALESCE(u.name, o.user_id) ASC
    ";
    $cr = mysqli_query($con, $sqlCustomers);
    if ($cr) {
        while ($c = mysqli_fetch_assoc($cr)) {
            $key = (string)$c['customer_key'];
            $name = trim((string)($c['customer_name'] ?? ''));
            if ($name === '') {
                $name = $key;
            }
            $customers[$key] = ['key' => $key, 'name' => $name];
        }
    }
}

if ($customerKey === '' && !empty($customers)) {
    $customerKey = (string)array_keys($customers)[0];
}

// Totals for selected customer
$totals = [
    'order_value' => 0.0,
    'pcs' => 0.0,
    'meters' => 0.0,
    'orders' => 0,
];
$chartLabels = [$periodLabel];
$chartOrderValues = [0.0];
$chartPcs = [0.0];
$chartMeters = [0.0];
$breakdown = [
    [
        'label' => $periodLabel,
        'order_value' => 0.0,
        'pcs' => 0.0,
        'meters' => 0.0,
        'orders' => 0,
    ]
];

$ranking = [];
if ($hasSalesOrder && $hasSalesOrderItem && $hasIndiadata && $customerKey !== '') {
    $custEsc = mysqli_real_escape_string($con, $customerKey);

    // Selected customer aggregates
    $totSql = "
      SELECT
        COUNT(DISTINCT o.id) AS orders_count,
        ".$orderValueExpr." AS order_value,
        ".$pcsAggExpr." AS pcs_sold,
        ".$metersAggExpr." AS meters_sold
      FROM sales_order o
      INNER JOIN sales_order_item oi ON oi.order_id = o.id
      INNER JOIN indiadata p ON p.itemcode = oi.itemcode
      WHERE
        o.user_id = '".$custEsc."'
        AND LOWER(TRIM(o.status)) = 'placed'
        AND o.created_at >= '".mysqli_real_escape_string($con, $rangeStartSql)."'
        AND o.created_at <  '".mysqli_real_escape_string($con, $rangeEndSql)."'
    ";
    $tr = mysqli_query($con, $totSql);
    if ($tr && mysqli_num_rows($tr) === 1) {
        $row = mysqli_fetch_assoc($tr);
        $totals['orders'] = (int)($row['orders_count'] ?? 0);
        $totals['order_value'] = round((float)($row['order_value'] ?? 0), 2);
        $totals['pcs'] = round((float)($row['pcs_sold'] ?? 0), 2);
        $totals['meters'] = round((float)($row['meters_sold'] ?? 0), 2);
        $chartOrderValues = [$totals['order_value']];
        $chartPcs = [$totals['pcs']];
        $chartMeters = [$totals['meters']];
        $breakdown = [[
            'label' => $periodLabel,
            'order_value' => $totals['order_value'],
            'pcs' => $totals['pcs'],
            'meters' => $totals['meters'],
            'orders' => $totals['orders'],
        ]];
    }

    // Ranking (top customers) for the same range
    $rankSql = "
      SELECT
        o.user_id AS customer_key,
        COALESCE(u.name, o.user_id) AS customer_name,
        ".$orderValueExpr." AS order_value
      FROM sales_order o
      LEFT JOIN users u ON (u.id = o.user_id OR u.userid = o.user_id)
      INNER JOIN sales_order_item oi ON oi.order_id = o.id
      INNER JOIN indiadata p ON p.itemcode = oi.itemcode
      WHERE
        LOWER(TRIM(o.status)) = 'placed'
        AND o.created_at >= '".mysqli_real_escape_string($con, $rangeStartSql)."'
        AND o.created_at <  '".mysqli_real_escape_string($con, $rangeEndSql)."'
      GROUP BY o.user_id, COALESCE(u.name, o.user_id)
      ORDER BY order_value DESC
      LIMIT 10
    ";
    $rr = mysqli_query($con, $rankSql);
    if ($rr) {
        while ($rrow = mysqli_fetch_assoc($rr)) {
            $ranking[] = [
                'key' => (string)($rrow['customer_key'] ?? ''),
                'name' => (string)($rrow['customer_name'] ?? ''),
                'order_value' => round((float)($rrow['order_value'] ?? 0), 2),
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LC : Customer Analytics</title>
  <script src="../js/modernizr-2.6.2.min.js"></script>
  <link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
  <link rel="stylesheet" type="text/css" href="../css/style.css" />
</head>
<body>
<ul class="topNav">
  <li><a class="admin" href="https://theclothlibrary.com/">Home</a></li>
  <li><a class="admin" href="../region.html">User Login</a></li>
  <li class="admin selected"><a href="login.php">Admin Login</a></li>
  <div class="clear"></div>
</ul>

<div id="mainPanel">
  <div id="logo" style="margin-left:10%; margin-right:10%;text-align:center;">
    <img src="../images/lc_logo_small.jpg" width="100%" height="80px;" alt="The Cloth Library" />
  </div>

  <div id="content_area">
    <div align="right" style="padding-bottom:10px;">
      <?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
        <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a>
    </div>

    <p>
      <a href="dashboard.php">
        <button type="button" class="btn btn-sm btn-primary">Back to Dashboard</button>
      </a>
    </p>

    <h3>Customer Analytics</h3>
    <p class="text-muted">Order value, total PCS and meters sold. Placed orders only.</p>

    <form method="get" action="customer_analytics.php">
      <div class="row" style="margin-bottom:10px;">
        <div class="col-sm-4">
          <label>Customer</label>
          <select name="customer_userid" class="form-control" onchange="this.form.submit()">
            <?php foreach ($customers as $ckey => $c) { ?>
              <option value="<?php echo htmlspecialchars($ckey); ?>" <?php echo ((string)$customerKey === (string)$ckey) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['name']); ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div class="col-sm-4">
          <label>View</label>
          <div>
            <a class="btn btn-sm <?php echo $view==='daily'?'btn-primary':'btn-default'; ?>" href="customer_analytics.php?customer_userid=<?php echo urlencode($customerKey); ?>&view=daily">Daily</a>
            <a class="btn btn-sm <?php echo $view==='weekly'?'btn-primary':'btn-default'; ?>" href="customer_analytics.php?customer_userid=<?php echo urlencode($customerKey); ?>&view=weekly">Weekly</a>
            <a class="btn btn-sm <?php echo $view==='monthly'?'btn-primary':'btn-default'; ?>" href="customer_analytics.php?customer_userid=<?php echo urlencode($customerKey); ?>&view=monthly">Monthly</a>
            <a class="btn btn-sm <?php echo $view==='yearly'?'btn-primary':'btn-default'; ?>" href="customer_analytics.php?customer_userid=<?php echo urlencode($customerKey); ?>&view=yearly">Yearly</a>
          </div>
        </div>
        <div class="col-sm-4">
          <label>&nbsp;</label>
          <div>
            <span class="label label-success" style="font-size:12px;">
              Orders: <?php echo (int)($totals['orders'] ?? 0); ?>
            </span>
          </div>
        </div>
      </div>
    </form>

    <div class="row" style="margin-bottom:10px;">
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">Total Order Value</div>
          <div class="panel-body"><b><?php echo htmlspecialchars(number_format((float)($totals['order_value'] ?? 0), 2)); ?></b></div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">Total PCS Sold</div>
          <div class="panel-body"><b><?php echo htmlspecialchars(number_format((float)($totals['pcs'] ?? 0), 2)); ?></b></div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">Total Meters Sold</div>
          <div class="panel-body"><b><?php echo htmlspecialchars(number_format((float)($totals['meters'] ?? 0), 2)); ?></b></div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">Top Customers</div>
          <div class="panel-body" style="max-height:120px; overflow:auto;">
            <table class="table table-condensed" style="margin-bottom:0;">
              <thead><tr><th>Customer</th><th style="text-align:right;">Value</th></tr></thead>
              <tbody>
                <?php if (!empty($ranking)) { ?>
                  <?php foreach ($ranking as $r) { ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['name']); ?></td>
                      <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$r['order_value'], 2)); ?></td>
                    </tr>
                  <?php } ?>
                <?php } else { ?>
                  <tr><td colspan="2" style="color:#999;">No data</td></tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-6">
        <h4>Order Value</h4>
        <canvas id="custAnalyticsOrderValueChart" height="120"></canvas>
      </div>
      <div class="col-sm-6">
        <h4>PCS vs Meters</h4>
        <canvas id="custAnalyticsQtyChart" height="120"></canvas>
      </div>
    </div>

    <div class="row" style="margin-top:10px;">
      <div class="col-sm-12">
        <h4>Breakdown</h4>
        <div class="table-responsive">
          <table class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th>Period</th>
                <th style="text-align:right;">Order Value</th>
                <th style="text-align:right;">PCS Sold</th>
                <th style="text-align:right;">Meters Sold</th>
                <th style="text-align:right;">Orders</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($breakdown as $b) { ?>
                <tr>
                  <td><?php echo htmlspecialchars((string)$b['label']); ?></td>
                  <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$b['order_value'], 2)); ?></td>
                  <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$b['pcs'], 2)); ?></td>
                  <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)$b['meters'], 2)); ?></td>
                  <td style="text-align:right;"><?php echo htmlspecialchars((string)$b['orders']); ?></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<div id="footer">
  copyright &copy; 2018 The Cloth Library.
</div>
<script src="../js/jquery-1.9.1.min.js"></script>
<script src="../bootstarp/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<?php
$custLabels = $chartLabels;
$custOrderValueArr = $chartOrderValues;
$custPcsArr = $chartPcs;
$custMetersArr = $chartMeters;
?>
<script>
  (function() {
    const labels = <?php echo json_encode($custLabels); ?>;
    const orderValue = <?php echo json_encode($custOrderValueArr); ?>;
    const pcs = <?php echo json_encode($custPcsArr); ?>;
    const meters = <?php echo json_encode($custMetersArr); ?>;

    const orderCtx = document.getElementById('custAnalyticsOrderValueChart');
    const qtyCtx = document.getElementById('custAnalyticsQtyChart');
    if (!orderCtx || !qtyCtx || labels.length === 0) return;

    new Chart(orderCtx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Order Value',
          data: orderValue,
          backgroundColor: 'rgba(54, 162, 235, 0.35)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(qtyCtx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'PCS',
          data: pcs,
          backgroundColor: 'rgba(75, 192, 192, 0.35)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1,
          yAxisID: 'y'
        }, {
          label: 'Meters',
          data: meters,
          backgroundColor: 'rgba(255, 159, 64, 0.35)',
          borderColor: 'rgba(255, 159, 64, 1)',
          borderWidth: 1,
          yAxisID: 'y1'
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: {
          y: { beginAtZero: true, position: 'left', grid: { drawOnChartArea: true } },
          y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
        }
      }
    });
  })();
</script>
</body>
</html>

