<?php
require(__DIR__.'/admin/db.php');
require_once(__DIR__.'/db_tables.php');
require_once(__DIR__.'/quantity_parser.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$itemcode = isset($_GET['itemcode']) ? trim((string)$_GET['itemcode']) : '';

$indiaTable = get_india_data_table($con);
$product = null;
$error = '';

if ($indiaTable === null) {
    $error = 'Product table not found.';
} else {
    $where = [];
    if ($id > 0) {
        $where[] = "id = '".intval($id)."'";
    }
    if ($itemcode !== '') {
        $itemcodeEsc = mysqli_real_escape_string($con, $itemcode);
        $where[] = "itemcode = '".$itemcodeEsc."'";
    }

    if (count($where) === 0) {
        $error = 'Invalid product link.';
    } else {
        $query = "SELECT id, itemcode, image, description, width, quantity, type, trn_date
                  FROM ".$indiaTable."
                  WHERE ".implode(' AND ', $where)."
                  LIMIT 1";
        $res = mysqli_query($con, $query);
        if ($res && mysqli_num_rows($res) === 1) {
            $product = mysqli_fetch_assoc($res);
        } else {
            $error = 'Product not found.';
        }
    }
}

$imageUrl = '';
if ($product && !empty($product['image'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $imageUrl = $scheme.'://'.$host.$base.'/item_images/'.$product['image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Details</title>
  <style>
    :root {
      --bg: #f2f3f5;
      --surface: #ffffff;
      --soft: #f5f6f8;
      --text: #111827;
      --muted: #6b7280;
      --border: #e5e7eb;
      --primary: #0f1b3f;
    }
    * { box-sizing: border-box; }
    body { margin:0; font-family: Arial, sans-serif; background:#555; color:var(--text); padding:16px 8px; }
    .phone {
      max-width:420px;
      margin:0 auto;
      background:var(--bg);
      border-radius:24px;
      padding:16px 14px 20px;
      min-height:92vh;
      border:1px solid #d9dde3;
    }
    .topbar { display:flex; align-items:center; justify-content:center; position:relative; margin-bottom:10px; }
    .back {
      position:absolute; left:0; top:0;
      width:38px; height:38px; border-radius:50%;
      border:1px solid var(--border); background:var(--surface);
      display:flex; align-items:center; justify-content:center;
      color:#1f2937; text-decoration:none; font-size:20px;
    }
    .title { text-align:center; font-size:30px; font-weight:700; margin:4px 0; letter-spacing:-0.2px; }
    .hero {
      text-align:center;
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:14px;
      padding:16px 12px;
      margin-bottom:12px;
    }
    .img-wrap {
      width:86px; height:86px; margin:0 auto 10px;
      border-radius:14px; overflow:hidden;
      border:1px solid var(--border);
      background:var(--soft);
      display:flex; align-items:center; justify-content:center;
    }
    .img-wrap img { width:100%; height:100%; object-fit:cover; }
    .noimg { color:var(--muted); font-size:12px; }
    .product-name { font-size:18px; font-weight:700; margin:2px 0; }
    .item-code { color:var(--muted); font-size:13px; word-break:break-word; }
    .stats { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:12px; }
    .stat {
      background:var(--surface);
      border:1px solid var(--border);
      border-radius:12px;
      padding:10px;
      text-align:center;
    }
    .stat .k { color:var(--muted); font-size:12px; margin-bottom:2px; }
    .stat .v { color:var(--text); font-weight:700; font-size:15px; }
    .card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:12px; margin-bottom:10px; }
    .card-title { font-size:14px; font-weight:700; margin:0 0 10px; }
    .row { display:flex; justify-content:space-between; gap:10px; padding:8px 0; border-top:1px solid #f0f1f3; }
    .row:first-child { border-top:0; padding-top:0; }
    .label { color:var(--muted); font-size:13px; }
    .value { color:var(--text); font-size:13px; font-weight:600; text-align:right; word-break:break-word; }
    .qty {
      background:#eef3ff;
      color:#142e66;
      border:1px solid #dbe6ff;
      border-radius:12px;
      padding:12px;
      font-size:18px;
      font-weight:700;
      text-align:center;
      margin-top:10px;
    }
    .error { color:#b00020; text-align:center; background:#fff; border:1px solid #ffd1da; padding:12px; border-radius:12px; }
  </style>
</head>
<body>
  <main class="phone">
    <div class="topbar">
      <a class="back" href="javascript:history.back()">&#x2039;</a>
      <div class="title">Product Details</div>
    </div>

    <?php if ($error !== '') { ?>
      <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php } elseif ($product) { ?>
      <section class="hero">
        <div class="img-wrap">
          <?php if ($imageUrl !== '') { ?>
            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Product image">
          <?php } else { ?>
            <div class="noimg">No Image</div>
          <?php } ?>
        </div>
        <div class="product-name"><?php echo htmlspecialchars((string)$product['description']); ?></div>
        <div class="item-code"><?php echo htmlspecialchars($product['itemcode']); ?></div>
      </section>

      <section class="stats">
        <div class="stat">
          <div class="k">Product ID</div>
          <div class="v"><?php echo (int)$product['id']; ?></div>
        </div>
        <div class="stat">
          <div class="k">Available Qty</div>
          <div class="v"><?php echo htmlspecialchars((string)parse_quantity_to_number($product['quantity'])); ?></div>
        </div>
      </section>

      <section class="card">
        <h3 class="card-title">Product Information</h3>
        <div class="row">
          <div class="label">Item Code</div>
          <div class="value"><?php echo htmlspecialchars($product['itemcode']); ?></div>
        </div>
        <div class="row">
          <div class="label">Description</div>
          <div class="value"><?php echo htmlspecialchars((string)$product['description']); ?></div>
        </div>
        <div class="row">
          <div class="label">Width</div>
          <div class="value"><?php echo htmlspecialchars((string)$product['width']); ?></div>
        </div>
        <div class="row">
          <div class="label">Type</div>
          <div class="value"><?php echo htmlspecialchars((string)$product['type']); ?></div>
        </div>
        <div class="qty">Available Quantity: <?php echo htmlspecialchars((string)parse_quantity_to_number($product['quantity'])); ?></div>
      </section>
    <?php } ?>
  </main>
</body>
</html>

