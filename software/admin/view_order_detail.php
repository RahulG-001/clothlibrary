<?php
require('db.php');
include('auth.php');
require_once('../product_stock_helpers.php');

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

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$order = null;
$items = null;
$orderTotal = 0.0;
$hasPrice = admin_column_exists($con, 'sales_order_item', 'price');
$hasMeters = admin_column_exists($con, 'sales_order_item', 'meters');

if ($orderId <= 0) {
    $error = 'Invalid order id.';
} elseif (!admin_table_exists($con, 'sales_order')) {
    $error = 'Table sales_order not found.';
} else {
    $oid = (int)$orderId;
    $commentSel = admin_column_exists($con, 'sales_order', 'salesman_comment') ? ', o.salesman_comment' : '';
    $q = "SELECT o.id, o.user_id, o.salesman_id, o.status, o.created_at, o.updated_at".$commentSel."
          FROM sales_order o WHERE o.id = '".$oid."' LIMIT 1";
    $res = mysqli_query($con, $q);
    if (!$res || mysqli_num_rows($res) === 0) {
        $error = 'Order not found.';
    } else {
        $order = mysqli_fetch_assoc($res);
        $sm = mysqli_query($con, "SELECT id, first_name, last_name, phone FROM salesman WHERE id = '".(int)$order['salesman_id']."' LIMIT 1");
        $salesman = ($sm && mysqli_num_rows($sm) === 1) ? mysqli_fetch_assoc($sm) : null;

            $customerName = '';
            $customerAddress = '';
            $userIdEsc = mysqli_real_escape_string($con, (string)$order['user_id']);
            $uRes = mysqli_query(
                $con,
                "SELECT name, address
                 FROM users
                 WHERE id = '".$userIdEsc."'
                    OR userid = '".$userIdEsc."'
                 LIMIT 1"
            );
            if ($uRes && mysqli_num_rows($uRes) === 1) {
                $uRow = mysqli_fetch_assoc($uRes);
                $customerName = isset($uRow['name']) ? trim((string)$uRow['name']) : '';
                $customerAddress = isset($uRow['address']) ? trim((string)$uRow['address']) : '';
            }

        $oiSql = "SELECT oi.id AS line_id, oi.itemcode, oi.quantity";
        if ($hasMeters) {
            $oiSql .= ", COALESCE(oi.meters, 0) AS meters";
        } else {
            $oiSql .= ", 0 AS meters";
        }
        if ($hasPrice) {
            $oiSql .= ", oi.price";
        } else {
            $oiSql .= ", NULL AS price";
        }
        $oiSql .= ", p.description, p.image
                   , p.type AS product_type
            FROM sales_order_item oi
            LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
            WHERE oi.order_id = '".$oid."'
            ORDER BY oi.id ASC";
        $items = mysqli_query($con, $oiSql);
        if (!$items) {
            $error = 'Could not load order lines: '.htmlspecialchars(mysqli_error($con));
        } elseif ($hasPrice) {
            while ($r = mysqli_fetch_assoc($items)) {
                $orderTotal += (float)$r['price'];
            }
            mysqli_data_seek($items, 0);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Order #<?php echo $orderId > 0 ? (int)$orderId : ''; ?></title>
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
    <a href="view_orders.php"><button type="button" class="btn btn-sm btn-default"><span class="glyphicon glyphicon-arrow-left"></span> All orders</button></a>
  </p>

  <?php if ($error !== '') { ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
  <?php } elseif ($order) {
      $smName = $salesman ? trim($salesman['first_name'].' '.$salesman['last_name']) : '';
  ?>
  <h3>Order #<?php echo (int)$order['id']; ?></h3>
  <p style="margin-top:10px;">
    <a class="btn btn-sm btn-primary" target="_blank" rel="noopener" href="order_bill_pdf.php?id=<?php echo (int)$order['id']; ?>&download=0">
      Print / Save as PDF
    </a>
    <a class="btn btn-sm btn-default" href="order_bill_pdf.php?id=<?php echo (int)$order['id']; ?>&download=1">
      Download Bill (PDF)
    </a>
  </p>

  <div class="panel panel-default">
    <div class="panel-heading"><strong>Summary</strong></div>
    <div class="panel-body">
      <table class="table table-condensed" style="margin-bottom:0;">
        <tr><th style="width:180px;">Status</th><td><?php echo htmlspecialchars($order['status']); ?></td></tr>
        <tr><th>Customer (user id)</th><td><?php echo htmlspecialchars($order['user_id']); ?></td></tr>
        <tr><th>Salesman</th><td>
          <?php echo $smName !== '' ? htmlspecialchars($smName) : htmlspecialchars('ID '.$order['salesman_id']); ?>
          <?php if ($salesman && !empty($salesman['phone'])) { ?><br><small>Phone: <?php echo htmlspecialchars($salesman['phone']); ?></small><?php } ?>
        </td></tr>
        <tr><th>Created</th><td><?php echo htmlspecialchars($order['created_at']); ?></td></tr>
        <tr><th>Updated</th><td><?php echo htmlspecialchars($order['updated_at']); ?></td></tr>
        <?php if (isset($order['salesman_comment']) && $order['salesman_comment'] !== null && $order['salesman_comment'] !== '') { ?>
        <tr><th>Salesman comment</th><td><?php echo nl2br(htmlspecialchars($order['salesman_comment'])); ?></td></tr>
        <?php } ?>
        <?php if ($hasPrice) { ?>
        <tr><th>Order total</th><td><strong><?php echo number_format($orderTotal, 2); ?></strong></td></tr>
        <?php } ?>
      </table>
    </div>
  </div>

  <h4>Line items</h4>
  <?php if ($items && mysqli_num_rows($items) === 0) { ?>
    <p class="text-muted">No lines on this order.</p>
  <?php } elseif ($items) { ?>
  <div class="table-responsive">
    <table class="table table-striped table-bordered">
      <thead>
        <tr>
          <th style="width:110px;">Image</th>
          <th>#</th>
          <th>Item code</th>
          <th>Description</th>
          <th>Quantity</th>
          <?php if ($hasPrice) { ?><th>Price (line)</th><?php } ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $n = 1;
        while ($line = mysqli_fetch_assoc($items)) {
            $img = isset($line['image']) ? trim((string)$line['image']) : '';
            $imgFile = $img !== '' ? basename(str_replace('\\', '/', $img)) : '';
            $imgUrl = $imgFile !== '' ? '../item_images/'.rawurlencode($imgFile) : '';
        ?>
        <tr>
          <td style="vertical-align:middle;">
            <?php if ($imgUrl !== '') { ?>
            <a href="<?php echo htmlspecialchars($imgUrl); ?>" target="_blank" rel="noopener" title="Open full size">
              <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="" class="img-thumbnail" style="max-width:96px; max-height:96px; width:auto; height:auto; display:block;" />
            </a>
            <?php } else { ?>
            <span class="text-muted">—</span>
            <?php } ?>
          </td>
          <td><?php echo $n++; ?></td>
          <td><?php echo htmlspecialchars($line['itemcode']); ?></td>
          <td><?php echo htmlspecialchars(isset($line['description']) ? $line['description'] : ''); ?></td>
          <td>
            <?php
              $ptypeLine = isset($line['product_type']) ? $line['product_type'] : '';
              $isPcsLine = product_stock_type_is_pcs($ptypeLine);
              $qtyLine = $isPcsLine ? (float)$line['quantity'] : (float)$line['meters'];
              echo htmlspecialchars(number_format((float)$qtyLine, 2));
            ?>
          </td>
          <?php if ($hasPrice) { ?>
          <td><?php echo $line['price'] !== null && $line['price'] !== '' ? number_format((float)$line['price'], 2) : '—'; ?></td>
          <?php } ?>
        </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <?php } ?>
  <?php } ?>

  <?php if ($order) { ?>
    <h4 style="margin-top:20px;">Client Bill</h4>
    <div class="panel panel-default">
      <div class="panel-body" style="padding:0;">
        <iframe
          src="order_bill_print.php?id=<?php echo (int)$order['id']; ?>&embed=1"
          style="width:100%; height:1040px; border:0;"
        ></iframe>
      </div>
    </div>
  <?php } ?>
</div>

<div id="footer">copyright &copy; 2018 The Cloth Library.</div>
</div>
<script src="../js/jquery-1.9.1.min.js"></script>
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
