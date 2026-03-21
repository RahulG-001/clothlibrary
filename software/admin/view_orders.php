<?php
require('db.php');
include('auth.php');

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

$ordersError = '';
$orders = null;

if (!admin_table_exists($con, 'sales_order')) {
    $ordersError = 'Table <code>sales_order</code> not found. Run create_sales_order_tables.sql if needed.';
} else {
    $hasPrice = admin_column_exists($con, 'sales_order_item', 'price');
    $sumExpr = $hasPrice ? 'COALESCE(SUM(oi.price), 0)' : '0';

    $sql = "
        SELECT o.id, o.user_id, o.salesman_id, o.status, o.created_at, o.updated_at,
               TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, ''))) AS salesman_name,
               s.phone AS salesman_phone,
               ".$sumExpr." AS order_total
        FROM sales_order o
        LEFT JOIN salesman s ON s.id = o.salesman_id
        LEFT JOIN sales_order_item oi ON oi.order_id = o.id
        WHERE LOWER(TRIM(o.status)) = 'placed'
        GROUP BY o.id, o.user_id, o.salesman_id, o.status, o.created_at, o.updated_at,
                 s.first_name, s.last_name, s.phone
        ORDER BY o.id DESC
    ";
    $orders = mysqli_query($con, $sql);
    if (!$orders) {
        $ordersError = 'Could not load orders: '.htmlspecialchars(mysqli_error($con));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Orders</title>
<script src="../js/modernizr-2.6.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
</head>
<body>
<ul class="topNav">
  <li><a class="admin" href="https://theclothlibrary.com/">Home</a></li>
  <li><a class="admin" href="../region.html">User Login</a></li>
  <li><a class="admin selected" href="login.php">Admin Login</a></li>
  <div class="clear"></div>
</ul>
<div id="mainPanel">
<div id="logo" style="margin-left:10%; margin-right:10%;text-align:center;"><img src="../images/lc_logo_small.jpg" width="100%" height="80px;" alt="The Cloth Library" /></div>

<div id="content_area">
  <div align="right" style="padding-bottom:10px;"><?php echo 'Hello <b>'.htmlspecialchars($_SESSION['username']).'</b>'; ?> | <a href="logout.php"><button type="button" class="btn btn-sm btn-danger">Logout</button></a></div>

  <p>
    <a href="dashboard.php"><button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-modal-window"></span> Dashboard</button></a>
  </p>

  <h3>Sales orders</h3>
  <p class="text-muted">Placed orders only (open carts are hidden). Open an order for line items and notes.</p>

  <?php if ($ordersError !== '') { ?>
    <div class="alert alert-danger"><?php echo $ordersError; ?></div>
  <?php } elseif ($orders) { ?>
  <div class="table-responsive">
    <table class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Status</th>
          <th>Customer</th>
          <th>Salesman</th>
          <th>Total</th>
          <th>Created</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($orders)) { ?>
        <tr>
          <td><?php echo (int)$row['id']; ?></td>
          <td><?php echo htmlspecialchars($row['status']); ?></td>
          <td><?php echo htmlspecialchars($row['user_id']); ?></td>
          <td><?php echo htmlspecialchars(trim($row['salesman_name']) !== '' ? $row['salesman_name'] : ('#'.$row['salesman_id'])); ?>
              <?php if (!empty($row['salesman_phone'])) { ?><br><small><?php echo htmlspecialchars($row['salesman_phone']); ?></small><?php } ?>
          </td>
          <td><?php echo $hasPrice ? number_format((float)$row['order_total'], 2) : '—'; ?></td>
          <td><?php echo htmlspecialchars($row['created_at']); ?></td>
          <td><a href="view_order_detail.php?id=<?php echo (int)$row['id']; ?>"><span class="glyphicon glyphicon-list-alt"></span> Details</a></td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <?php } ?>
</div>

<div id="footer">copyright &copy; 2018 The Cloth Library.</div>
</div>
<script src="../js/jquery-1.9.1.min.js"></script>
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
