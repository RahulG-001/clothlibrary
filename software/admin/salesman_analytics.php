<?php
require_once('db.php');
include('auth.php'); // secure page
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

function normalize_month_label($dt) {
    // Example: "Jan 2026"
    return $dt->format('M Y');
}
function normalize_day_label($dt) {
    return $dt->format('d M');
}
function normalize_week_label_from_key($k) {
    $parts = explode('-', (string)$k);
    if (count($parts) === 2) {
        return 'W'.$parts[1].' '.$parts[0];
    }
    return (string)$k;
}

// Controls
$view = isset($_GET['view']) ? strtolower((string)$_GET['view']) : 'monthly';
if (!in_array($view, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
    $view = 'monthly';
}

$selectedSalesmanId = isset($_GET['salesman_id']) ? (int)$_GET['salesman_id'] : 0;

$hasSalesOrder = admin_table_exists($con, 'sales_order');
$hasSalesOrderItem = admin_table_exists($con, 'sales_order_item');
$hasIndiadata = admin_table_exists($con, 'indiadata');

$hasPrice = admin_column_exists($con, 'sales_order_item', 'price');
$hasMeters = admin_column_exists($con, 'sales_order_item', 'meters');
$hasQty    = admin_column_exists($con, 'sales_order_item', 'quantity');

// Load salesmen for dropdown
$salesmen = [];
if (admin_table_exists($con, 'salesman')) {
    $smRes = mysqli_query($con, "SELECT id, first_name, last_name FROM salesman ORDER BY id DESC");
    if ($smRes) {
        while ($sm = mysqli_fetch_assoc($smRes)) {
            $sid = (int)$sm['id'];
            $name = trim((string)($sm['first_name'] ?? '').' '.($sm['last_name'] ?? ''));
            if ($name === '') {
                $name = 'Salesman #'.$sid;
            }
            $salesmen[$sid] = ['id' => $sid, 'name' => $name];
        }
    }
}

if ($selectedSalesmanId <= 0 && !empty($salesmen)) {
    $selectedSalesmanId = (int)array_keys($salesmen)[0];
}

// Data containers
$detailKeys = [];
$detailLabels = [];
$detailOrderValues = [];
$detailPcs = [];
$detailMeters = [];
$detailOrdersCount = [];
$detailTotals = ['order_value' => 0.0, 'pcs' => 0.0, 'meters' => 0.0, 'orders' => 0];

// Summary for ranking (last 12 months)
$summaryBySalesman = [];

// Stock-mode helper expression for SQL
$pcsNormExpr = "UPPER(REPLACE(TRIM(p.type),' ',''))";
$pcsInList = "('PCS','PC','PIECE','PIECES','PC.')";
$pcsCase = "CASE WHEN ".$pcsNormExpr." IN ".$pcsInList." THEN 1 ELSE 0 END";

$canCompute = $hasSalesOrder && $hasSalesOrderItem && $hasIndiadata && !empty($salesmen) && $selectedSalesmanId > 0;

if ($canCompute) {
    $now = new DateTime('now');
    $orderValueExpr = $hasPrice ? "SUM(COALESCE(oi.price,0))" : "0";
    $qtyExpr = $hasQty ? "COALESCE(oi.quantity,0)" : "0";
    $metersExpr = $hasMeters ? "COALESCE(oi.meters,0)" : "0";
    $pcsAggExpr = "SUM(CASE WHEN (".$pcsCase.")=1 THEN ".$qtyExpr." ELSE 0 END)";
    $metersAggExpr = "SUM(CASE WHEN (".$pcsCase.")=1 THEN 0 ELSE ".$metersExpr." END)";

    if ($view === 'daily') {
        $periodStart = new DateTime('today');
        $periodEnd = new DateTime('tomorrow');
        $rangeStartSql = $periodStart->format('Y-m-d').' 00:00:00';
        $rangeEndSql   = $periodEnd->format('Y-m-d').' 00:00:00';
        $key = $periodStart->format('Y-m-d');
        $detailKeys[] = $key;
        $detailLabels[] = normalize_day_label($periodStart);
        $detailOrderValues[$key] = 0.0;
        $detailPcs[$key] = 0.0;
        $detailMeters[$key] = 0.0;
        $detailOrdersCount[$key] = 0;
        $periodKeyExpr = "DATE_FORMAT(o.created_at, '%Y-%m-%d')";
    } elseif ($view === 'weekly') {
        $periodStart = new DateTime('monday this week');
        $periodStart->setTime(0, 0, 0);
        $periodEnd = new DateTime('monday next week');
        $periodEnd->setTime(0, 0, 0);
        $rangeStartSql = $periodStart->format('Y-m-d').' 00:00:00';
        $rangeEndSql   = $periodEnd->format('Y-m-d').' 00:00:00';
        $key = $periodStart->format('o-W');
        $detailKeys[] = $key;
        $detailLabels[] = normalize_week_label_from_key($key);
        $detailOrderValues[$key] = 0.0;
        $detailPcs[$key] = 0.0;
        $detailMeters[$key] = 0.0;
        $detailOrdersCount[$key] = 0;
        $periodKeyExpr = "DATE_FORMAT(o.created_at, '%x-%v')";
    } elseif ($view === 'monthly') {
        $periodStart = new DateTime('first day of this month');
        $periodEnd = new DateTime('first day of next month');
        $rangeStartSql = $periodStart->format('Y-m-d').' 00:00:00';
        $rangeEndSql   = $periodEnd->format('Y-m-d').' 00:00:00';
        $key = $periodStart->format('Y-m');
        $detailKeys[] = $key;
        $detailLabels[] = normalize_month_label($periodStart);
        $detailOrderValues[$key] = 0.0;
        $detailPcs[$key] = 0.0;
        $detailMeters[$key] = 0.0;
        $detailOrdersCount[$key] = 0;
        $periodKeyExpr = "DATE_FORMAT(o.created_at, '%Y-%m')";
    } else {
        $thisYear = (int)$now->format('Y');
        $minYear = $thisYear;
        $rangeStartSql = $minYear.'-01-01 00:00:00';
        $rangeEndSql   = ($thisYear + 1).'-01-01 00:00:00';
        $key = (string)$thisYear;
        $detailKeys[] = $key;
        $detailLabels[] = $key;
        $detailOrderValues[$key] = 0.0;
        $detailPcs[$key] = 0.0;
        $detailMeters[$key] = 0.0;
        $detailOrdersCount[$key] = 0;
        $periodKeyExpr = "YEAR(o.created_at)";
    }

    $sql = "
      SELECT
        ".$periodKeyExpr." AS period_key,
        COUNT(DISTINCT o.id) AS orders_count,
        ".$orderValueExpr." AS order_value,
        ".$pcsAggExpr." AS pcs_sold,
        ".$metersAggExpr." AS meters_sold
      FROM sales_order o
      INNER JOIN sales_order_item oi ON oi.order_id = o.id
      INNER JOIN indiadata p ON p.itemcode = oi.itemcode
      WHERE
        o.salesman_id = '".(int)$selectedSalesmanId."'
        AND LOWER(TRIM(o.status)) = 'placed'
        AND o.created_at >= '".mysqli_real_escape_string($con, $rangeStartSql)."'
        AND o.created_at <  '".mysqli_real_escape_string($con, $rangeEndSql)."'
      GROUP BY period_key
      ORDER BY period_key ASC
    ";
    $res = mysqli_query($con, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $key = (string)$row['period_key'];
            if (!isset($detailOrderValues[$key])) {
                continue;
            }
            $detailOrderValues[$key] = round((float)($row['order_value'] ?? 0), 2);
            $detailPcs[$key] = round((float)($row['pcs_sold'] ?? 0), 2);
            $detailMeters[$key] = round((float)($row['meters_sold'] ?? 0), 2);
            $detailOrdersCount[$key] = (int)($row['orders_count'] ?? 0);
        }
    }

    // Totals for selected salesman
    foreach ($detailKeys as $k) {
        $detailTotals['order_value'] += (float)($detailOrderValues[$k] ?? 0);
        $detailTotals['pcs'] += (float)($detailPcs[$k] ?? 0);
        $detailTotals['meters'] += (float)($detailMeters[$k] ?? 0);
        $detailTotals['orders'] += (int)($detailOrdersCount[$k] ?? 0);
    }

    // Ranking summary for selected range
    foreach ($salesmen as $sid => $sm) {
        $summaryBySalesman[(int)$sid] = [
            'order_value' => 0.0,
            'pcs' => 0.0,
            'meters' => 0.0,
        ];
    }

    $orderValueExpr = $hasPrice ? "SUM(COALESCE(oi.price,0))" : "0";
    $qtyExpr = $hasQty ? "COALESCE(oi.quantity,0)" : "0";
    $metersExpr = $hasMeters ? "COALESCE(oi.meters,0)" : "0";
    $pcsAggExpr = "SUM(CASE WHEN (".$pcsCase.")=1 THEN ".$qtyExpr." ELSE 0 END)";
    $metersAggExpr = "SUM(CASE WHEN (".$pcsCase.")=1 THEN 0 ELSE ".$metersExpr." END)";

    $sql = "
      SELECT
        o.salesman_id AS salesman_id,
        ".$orderValueExpr." AS order_value,
        ".$pcsAggExpr." AS pcs_sold,
        ".$metersAggExpr." AS meters_sold
      FROM sales_order o
      INNER JOIN sales_order_item oi ON oi.order_id = o.id
      INNER JOIN indiadata p ON p.itemcode = oi.itemcode
      WHERE
        LOWER(TRIM(o.status)) = 'placed'
        AND o.created_at >= '".mysqli_real_escape_string($con, $rangeStartSql)."'
        AND o.created_at <  '".mysqli_real_escape_string($con, $rangeEndSql)."'
      GROUP BY o.salesman_id
      ORDER BY order_value DESC
    ";

    $res = mysqli_query($con, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $sid = (int)$row['salesman_id'];
            if (!isset($summaryBySalesman[$sid])) {
                continue;
            }
            $summaryBySalesman[$sid]['order_value'] = round((float)($row['order_value'] ?? 0), 2);
            $summaryBySalesman[$sid]['pcs'] = round((float)($row['pcs_sold'] ?? 0), 2);
            $summaryBySalesman[$sid]['meters'] = round((float)($row['meters_sold'] ?? 0), 2);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LC : Salesman Analytics</title>
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
    <img src="../images/lc_logo_small.jpg" width="100%" height="80px;" alt="Kalidioscope" />
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

    <h3>Salesman Analytics</h3>
    <p class="text-muted">Order value, total PCS and meters sold. Placed orders only.</p>

    <form method="get" action="salesman_analytics.php">
      <div class="row" style="margin-bottom:10px;">
        <div class="col-sm-4">
          <label>Salesman</label>
          <select name="salesman_id" class="form-control" onchange="this.form.submit()">
            <?php foreach ($salesmen as $sid => $sm) { ?>
              <option value="<?php echo (int)$sid; ?>" <?php echo ((int)$selectedSalesmanId === (int)$sid) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($sm['name']); ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div class="col-sm-4">
          <label>View</label>
          <div>
            <a class="btn btn-sm <?php echo $view==='daily'?'btn-primary':'btn-default'; ?>"
               href="salesman_analytics.php?salesman_id=<?php echo (int)$selectedSalesmanId; ?>&view=daily">Daily</a>
            <a class="btn btn-sm <?php echo $view==='weekly'?'btn-primary':'btn-default'; ?>"
               href="salesman_analytics.php?salesman_id=<?php echo (int)$selectedSalesmanId; ?>&view=weekly">Weekly</a>
            <a class="btn btn-sm <?php echo $view==='monthly'?'btn-primary':'btn-default'; ?>"
               href="salesman_analytics.php?salesman_id=<?php echo (int)$selectedSalesmanId; ?>&view=monthly">Monthly</a>
            <a class="btn btn-sm <?php echo $view==='yearly'?'btn-primary':'btn-default'; ?>"
               href="salesman_analytics.php?salesman_id=<?php echo (int)$selectedSalesmanId; ?>&view=yearly">Yearly</a>
          </div>
        </div>
        <div class="col-sm-4">
          <label>&nbsp;</label>
          <div>
            <span class="label label-success" style="font-size:12px;">
              Orders: <?php echo (int)($detailTotals['orders'] ?? 0); ?>
            </span>
          </div>
        </div>
      </div>
    </form>

    <div class="row" style="margin-bottom:10px;">
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">Total Order Value</div>
          <div class="panel-body">
            <b><?php echo htmlspecialchars(number_format((float)($detailTotals['order_value'] ?? 0), 2)); ?></b>
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">Total PCS Sold</div>
          <div class="panel-body">
            <b><?php echo htmlspecialchars(number_format((float)($detailTotals['pcs'] ?? 0), 2)); ?></b>
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">Total Meters Sold</div>
          <div class="panel-body">
            <b><?php echo htmlspecialchars(number_format((float)($detailTotals['meters'] ?? 0), 2)); ?></b>
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">Salesmen Ranking (Selected Range)</div>
          <div class="panel-body" style="max-height:120px; overflow:auto;">
            <table class="table table-condensed" style="margin-bottom:0;">
              <thead>
                <tr>
                  <th>Salesman</th>
                  <th style="text-align:right;">Value</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($summaryBySalesman)) { arsort($summaryBySalesman); ?>
                  <?php foreach ($summaryBySalesman as $sid => $sdat) { ?>
                    <tr>
                      <td><?php echo htmlspecialchars($salesmen[$sid]['name'] ?? ('#'.(int)$sid)); ?></td>
                      <td style="text-align:right;"><?php echo htmlspecialchars(number_format((float)($sdat['order_value'] ?? 0), 2)); ?></td>
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
        <canvas id="analyticsOrderValueChart" height="120"></canvas>
      </div>
      <div class="col-sm-6">
        <h4>PCS vs Meters</h4>
        <canvas id="analyticsQtyChart" height="120"></canvas>
      </div>
    </div>

    <div class="row" style="margin-top:10px;">
      <div class="col-sm-12">
        <h4>Breakdown</h4>
        <div class="table-responsive">
          <table class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th><?php echo $view==='daily' ? 'Day' : ($view==='weekly' ? 'Week' : ($view==='monthly' ? 'Month' : 'Year')); ?></th>
                <th style="text-align:right;">Order Value</th>
                <th style="text-align:right;">PCS Sold</th>
                <th style="text-align:right;">Meters Sold</th>
                <th style="text-align:right;">Orders</th>
              </tr>
            </thead>
            <tbody>
              <?php
                if (!empty($detailKeys)) {
                    for ($i = 0; $i < count($detailKeys); $i++) {
                        $k = $detailKeys[$i];
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars((string)$detailLabels[$i]).'</td>';
                        echo '<td style="text-align:right;">'.htmlspecialchars(number_format((float)($detailOrderValues[$k] ?? 0), 2)).'</td>';
                        echo '<td style="text-align:right;">'.htmlspecialchars(number_format((float)($detailPcs[$k] ?? 0), 2)).'</td>';
                        echo '<td style="text-align:right;">'.htmlspecialchars(number_format((float)($detailMeters[$k] ?? 0), 2)).'</td>';
                        echo '<td style="text-align:right;">'.htmlspecialchars((string)($detailOrdersCount[$k] ?? 0)).'</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5" style="color:#999;">No data</td></tr>';
                }
              ?>
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
// JS arrays in the exact order of labels/keys.
$chartOrderValuesArr = [];
$chartPcsArr = [];
$chartMetersArr = [];
for ($i = 0; $i < count($detailKeys); $i++) {
    $k = $detailKeys[$i];
    $chartOrderValuesArr[] = (float)($detailOrderValues[$k] ?? 0);
    $chartPcsArr[] = (float)($detailPcs[$k] ?? 0);
    $chartMetersArr[] = (float)($detailMeters[$k] ?? 0);
}
?>
<script>
  (function() {
    const labels = <?php echo json_encode($detailLabels); ?>;
    const orderValue = <?php echo json_encode($chartOrderValuesArr); ?>;
    const pcs = <?php echo json_encode($chartPcsArr); ?>;
    const meters = <?php echo json_encode($chartMetersArr); ?>;

    const orderCtx = document.getElementById('analyticsOrderValueChart');
    const qtyCtx = document.getElementById('analyticsQtyChart');
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
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
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

