<?php
require('db.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__.'/../product_stock_helpers.php');

function admin_column_exists($con, $table, $column) {
    $t = mysqli_real_escape_string($con, $table);
    $c = mysqli_real_escape_string($con, $column);
    $r = mysqli_query($con, "SHOW COLUMNS FROM `".$t."` LIKE '".$c."'");
    return $r && mysqli_num_rows($r) > 0;
}

function bill_rel_web_url($relFromAdmin) {
    $relFromAdmin = str_replace('\\', '/', $relFromAdmin);
    $d = dirname($relFromAdmin);
    $b = basename($relFromAdmin);
    if ($d === '.' || $d === '') {
        return rawurlencode($b);
    }
    return $d.'/'.rawurlencode($b);
}

/** file:// URL for dompdf when $relFromAdmin is relative to this admin directory. */
function bill_rel_file_url($relFromAdmin) {
    $abs = realpath(__DIR__.'/'.str_replace('\\', '/', $relFromAdmin));
    if (!$abs || !is_file($abs)) {
        return '';
    }
    return 'file://'.str_replace('\\', '/', $abs);
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$embed   = isset($_GET['embed']) ? (int)$_GET['embed'] : 0;
$download = isset($_GET['download']) ? (bool)$_GET['download'] : false;
$mode    = isset($_GET['mode']) ? (string)$_GET['mode'] : '';
$asRender = $mode === 'render';
$isAuthed = isset($_SESSION['username']) && trim((string)$_SESSION['username']) !== '';
if ($orderId <= 0) {
    if ($download) {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $download ? 'Invalid order id.' : 'Invalid order id.';
    exit;
}

if (!$isAuthed && !$asRender) {
    header('Location: login.php');
    exit;
}
if (!$isAuthed && $asRender) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unauthorized';
    exit;
}

$hasPrice = admin_column_exists($con, 'sales_order_item', 'price');
$hasMeters = admin_column_exists($con, 'sales_order_item', 'meters');

$orderQ = "SELECT o.id,
                  o.user_id,
                  o.salesman_id,
                  o.status,
                  o.created_at,
                  o.updated_at,
                  o.salesman_comment,
                  u.name AS customer_name,
                  u.address AS customer_address
           FROM sales_order o
           LEFT JOIN users u ON (u.id = o.user_id OR u.userid = o.user_id)
           WHERE o.id = '".$orderId."'
           LIMIT 1";
$orderRes = mysqli_query($con, $orderQ);
if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    echo 'Order not found.';
    exit;
}
$order = mysqli_fetch_assoc($orderRes);

$smRes = mysqli_query(
    $con,
    "SELECT id, first_name, last_name, phone
     FROM salesman
     WHERE id = '".(int)$order['salesman_id']."'
     LIMIT 1"
);
$sm = ($smRes && mysqli_num_rows($smRes) === 1) ? mysqli_fetch_assoc($smRes) : null;
$smName = $sm ? trim($sm['first_name'].' '.$sm['last_name']) : '';

$oiSql = "SELECT oi.id AS line_id,
                 oi.itemcode,
                 oi.quantity,
                 ".($hasMeters ? "COALESCE(oi.meters,0) AS meters" : "0 AS meters").",
                 ".($hasPrice ? "oi.price" : "NULL AS price").",
                 p.description,
                 p.width AS quality,
                 p.trn_date AS reference,
                 p.type AS product_type
          FROM sales_order_item oi
          LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
          WHERE oi.order_id = '".$orderId."'
          ORDER BY oi.id ASC";
$itemsRes = mysqli_query($con, $oiSql);

$items = [];
if ($itemsRes) {
    while ($row = mysqli_fetch_assoc($itemsRes)) {
        $items[] = $row;
    }
}

$orderTotal = 0.0;
if ($hasPrice) {
    foreach ($items as $it) {
        if (isset($it['price']) && $it['price'] !== null && $it['price'] !== '') {
            $orderTotal += (float)$it['price'];
        }
    }
}

$totalMeters = 0.0;
$totalPieces = 0.0;
foreach ($items as $it) {
    $ptype = isset($it['product_type']) ? $it['product_type'] : '';
    if (product_stock_type_is_pcs($ptype)) {
        $totalPieces += (float)$it['quantity'];
    } else {
        $totalMeters += isset($it['meters']) ? (float)$it['meters'] : 0.0;
    }
}

$branding = [];
if (is_file(__DIR__.'/bill_branding.php')) {
    $tmp = include(__DIR__.'/bill_branding.php');
    if (is_array($tmp)) {
        $branding = $tmp;
    }
}

$headerImgRel = isset($branding['bill_header_image']) ? (string)$branding['bill_header_image'] : '../order-sheet/logo_art_2.png';
if (!is_file(__DIR__.'/'.$headerImgRel)) {
    $headerImgRel = '../order-sheet/logo_art_2.png';
}
if (!is_file(__DIR__.'/'.$headerImgRel)) {
    $headerImgRel = isset($branding['logo_image']) ? (string)$branding['logo_image'] : '../images/logo_art_2.png';
}

$headerWebUrl = bill_rel_web_url($headerImgRel);
$headerFileUrl = bill_rel_file_url($headerImgRel);
$headerImgSrc = $asRender ? $headerFileUrl : $headerWebUrl;

$created = isset($order['created_at']) ? (string)$order['created_at'] : '';
$dateStr = $created !== '' ? date('d/m/Y', strtotime($created)) : '';
$customerName = isset($order['customer_name']) ? trim((string)$order['customer_name']) : '';
$customerAddress = isset($order['customer_address']) ? trim((string)$order['customer_address']) : '';
$remarks = isset($order['salesman_comment']) ? trim((string)$order['salesman_comment']) : '';
$deliveryTo = $customerAddress !== '' ? $customerAddress : '';
$customerDisplay = $customerName !== '' ? $customerName : ('User id: '.(string)$order['user_id']);
$valoreStr = $hasPrice ? number_format($orderTotal, 2) : '';
$minItemRows = 19;
$padRows = max(0, $minItemRows - count($items));

if (!$asRender) {
    if ($download) {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="order_sheet_'.$orderId.'.html"');
    } else {
        header('Content-Type: text/html; charset=utf-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order sheet — Order #<?php echo (int)$orderId; ?></title>
  <style>
    body {
      margin: 0;
      padding: <?php echo $asRender ? '8mm 10mm' : '12px'; ?>;
      background: <?php echo $asRender ? '#fff' : '#c4c4c4'; ?>;
      font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
      font-size: 9.5pt;
      color: #000;
    }
    .sheet {
      width: 100%;
      max-width: 210mm;
      margin: 0 auto;
      padding: <?php echo $asRender ? '0' : '10mm 12mm 12mm'; ?>;
      background: #fff;
      <?php if (!$asRender) { ?>box-shadow: 0 2px 12px rgba(0,0,0,0.15);<?php } ?>
    }
    .sheet-header {
      width: 100%;
      border-collapse: collapse;
      border: none;
      margin: 0 0 5mm 0;
    }
    .sheet-header td {
      border: none;
      padding: 0;
      text-align: center;
      vertical-align: middle;
    }
    .header-img {
      display: inline-block;
      max-width: 40%;
      width: auto;
      height: auto;
      vertical-align: middle;
    }
    .label {
      font-size: 8.5pt;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.02em;
      line-height: 1.2;
      display: block;
      margin-bottom: 2mm;
    }
    .form-tbl {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }
    .form-tbl td {
      border: 1px solid #000;
      padding: 2mm 2.5mm 3mm;
      vertical-align: top;
      font-size: 9pt;
    }
    .form-tbl .val {
      font-weight: 400;
      text-transform: none;
      min-height: 6mm;
    }
    .customer-line {
      margin-top: 5mm;
      border-bottom: 1px dotted #000;
      padding-bottom: 1mm;
      min-height: 6mm;
    }
    .remarks-box {
      min-height: 12mm;
      white-space: pre-wrap;
      word-wrap: break-word;
    }
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 0;
      table-layout: fixed;
    }
    .items-table th,
    .items-table td {
      border: 1px solid #000;
      padding: 1.2mm 1.5mm;
      vertical-align: middle;
      text-align: center;
      font-size: 8.5pt;
    }
    .items-table th {
      font-weight: 700;
      text-transform: uppercase;
    }
    .items-table td.data {
      font-weight: 400;
      text-transform: none;
      height: 6mm;
    }
    .items-table .left { text-align: left; }
    .items-table .right { text-align: right; }
    .items-table .th-stack {
      font-size: 7.5pt;
      line-height: 1.15;
      padding: 2mm 1mm;
    }
    .totals-summary {
      width: 100%;
      border-collapse: collapse;
      margin-top: 3mm;
      table-layout: fixed;
    }
    .totals-summary td {
      border: 1px solid #000;
      padding: 2.5mm 3mm;
      font-size: 9pt;
      vertical-align: middle;
    }
    .totals-summary .tot-label {
      font-weight: 700;
      text-transform: uppercase;
      width: 62%;
      text-align: left;
    }
    .totals-summary .tot-value {
      font-weight: 700;
      text-align: right;
      width: 38%;
    }
    .footer-note {
      margin-top: 3mm;
      text-align: right;
      font-size: 7.5pt;
      line-height: 1.35;
      font-weight: 600;
      text-transform: uppercase;
    }
    @media print {
      body { padding: 0; background: #fff; }
      .noPrint { display: none; }
    }
  </style>
</head>
<body>
  <main class="sheet">
    <?php if ($headerImgSrc !== '') { ?>
    <table class="sheet-header" role="presentation" width="100%" cellspacing="0" cellpadding="0">
      <tr>
        <td align="center">
          <img class="header-img" src="<?php echo htmlspecialchars($headerImgSrc); ?>" alt="Lanificio Cucinello" />
        </td>
      </tr>
    </table>
    <?php } ?>

    <table class="form-tbl" cellspacing="0" cellpadding="0">
      <tr>
        <td style="width:20%;">
          <span class="label">N° Cliente / N° Customer</span>
          <div class="val"><?php echo htmlspecialchars((string)$order['user_id']); ?></div>
        </td>
        <td style="width:20%;">
          <span class="label">Data / Date</span>
          <div class="val"><?php echo htmlspecialchars($dateStr); ?></div>
        </td>
        <td style="width:20%;">
          <span class="label">Stagione / Season</span>
          <div class="val"><?php echo htmlspecialchars(isset($order['status']) ? (string)$order['status'] : ''); ?></div>
        </td>
        <td style="width:20%;">
          <span class="label">Valore / Value</span>
          <div class="val"><?php echo htmlspecialchars($valoreStr); ?></div>
        </td>
        <td style="width:20%;">
          <span class="label">Imballaggio / Packing</span>
          <div class="val"></div>
        </td>
      </tr>
      <tr>
        <td colspan="5">
          <span class="label">Cliente / Customer</span>
          <div class="customer-line val"><?php echo htmlspecialchars($customerDisplay); ?></div>
        </td>
      </tr>
      <tr>  
        <td colspan="2" style="width:50%;">
          <span class="label">Conditions / Terms</span>
          <div class="val"></div>
        </td>
        <td colspan="3" style="width:50%;">
          <span class="label">Consegnare a / Delivery to</span>
          <div class="val"><?php echo htmlspecialchars($deliveryTo); ?></div>
        </td>
      </tr>
      <tr>
        <td colspan="2" style="width:34%;">
          <span class="label">Metodo di spedizione / Method of dispatch</span>
          <div class="val"></div>
        </td>
        <td style="width:33%;">
          <span class="label">Data di consegna / Delivery date</span>
          <div class="val"></div>
        </td>
        <td colspan="2" style="width:33%;">
          <span class="label">Agente / Agent</span>
          <div class="val"><?php echo htmlspecialchars($smName !== '' ? $smName : ('ID '.(int)$order['salesman_id'])); ?></div>
        </td>
      </tr>
      <tr>
        <td colspan="3" style="width:60%;">
          <span class="label">Modelli / Patterns — Entichette / Lebels</span>
          <div class="val"></div>
        </td>
        <td colspan="2" style="width:40%;">
          <span class="label">Termini di consegna / Delivery terms</span>
          <div class="val"></div>
        </td>
      </tr>
      <tr>
        <td colspan="5">
          <span class="label">Osservazioni / Remarks</span>
          <div class="remarks-box val"><?php echo htmlspecialchars($remarks); ?></div>
        </td>
      </tr>
    </table>

    <table class="items-table" cellspacing="0" cellpadding="0">
      <colgroup>
        <col style="width:6%;" />
        <col style="width:14%;" />
        <col style="width:16%;" />
        <col style="width:38%;" />
        <col style="width:13%;" />
        <col style="width:13%;" />
      </colgroup>
      <thead>
        <tr>
          <th>S.No.</th>
          <th>Codice prixe</th>
          <th>Quality</th>
          <th>Numero di riferimento / Reference number</th>
          <th class="th-stack">Quantité<br>Quantity</th>
          <th class="th-stack">Prezzo <i>p.m.</i><br>Price <i>p.m.</i></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $n = 1;
        foreach ($items as $it) {
            $ptype = isset($it['product_type']) ? $it['product_type'] : '';
            $isPcs = product_stock_type_is_pcs($ptype);
            $qtyVal = $isPcs ? (float)$it['quantity'] : (float)$it['meters'];
            $unitLabel = $isPcs ? ' pcs' : ' m';
            $unitPrice = null;
            if ($hasPrice && $qtyVal > 0.0000001) {
                $lineTotal = (isset($it['price']) && $it['price'] !== null) ? (float)$it['price'] : 0.0;
                $unitPrice = $lineTotal / $qtyVal;
            }
            $q = isset($it['quality']) ? trim((string)$it['quality']) : '';
            $ref = isset($it['reference']) ? trim((string)$it['reference']) : '';
            if ($ref === '' && isset($it['description']) && $it['description'] !== null) {
                $ref = (string)$it['description'];
            }
        ?>
        <tr>
          <td class="data"><?php echo (int)$n++; ?></td>
          <td class="data left"><?php echo htmlspecialchars((string)$it['itemcode']); ?></td>
          <td class="data"><?php echo htmlspecialchars($q); ?></td>
          <td class="data left"><?php echo htmlspecialchars($ref); ?></td>
          <td class="data right"><?php echo htmlspecialchars(number_format($qtyVal, 2).$unitLabel); ?></td>
          <td class="data right"><?php echo $unitPrice === null ? '—' : htmlspecialchars(number_format($unitPrice, 2).'/-'); ?></td>
        </tr>
        <?php } ?>
        <?php for ($i = 0; $i < $padRows; $i++) { ?>
        <tr>
          <td class="data">&nbsp;</td>
          <td class="data">&nbsp;</td>
          <td class="data">&nbsp;</td>
          <td class="data">&nbsp;</td>
          <td class="data">&nbsp;</td>
          <td class="data">&nbsp;</td>
        </tr>
        <?php } ?>
      </tbody>
    </table>

    <?php
    $showTotMeters = $totalMeters > 0.0000001;
    $showTotPieces = $totalPieces > 0.0000001;
    $showTotAmount = $hasPrice;
    if ($showTotMeters || $showTotPieces || $showTotAmount) {
    ?>
    <table class="totals-summary" cellspacing="0" cellpadding="0">
      <?php if ($showTotMeters) { ?>
      <tr>
        <td class="tot-label">Totale metri / Total meters</td>
        <td class="tot-value"><?php echo htmlspecialchars(number_format($totalMeters, 2).' m'); ?></td>
      </tr>
      <?php } ?>
      <?php if ($showTotPieces) { ?>
      <tr>
        <td class="tot-label">Totale pezzi / Total pieces</td>
        <td class="tot-value"><?php echo htmlspecialchars(number_format($totalPieces, 2).' pcs'); ?></td>
      </tr>
      <?php } ?>
      <?php if ($showTotAmount) { ?>
      <tr>
        <td class="tot-label">Importo totale / Total amount</td>
        <td class="tot-value"><?php echo htmlspecialchars(number_format($orderTotal, 2)); ?></td>
      </tr>
      <?php } ?>
    </table>
    <?php } ?>

    <p class="footer-note">
      Bianca Cucinello - Rosa : Client - Verde : Agente<br />
      White : Cucinello - Pink : Customer - Green : Agent
    </p>

    <?php if (!$download && $embed === 0) { ?>
      <div class="noPrint" style="position: fixed; top: 12px; right: 12px;">
        <button type="button" onclick="window.print()" style="padding: 8px 12px; border: 1px solid #000; background: #fff; cursor: pointer;">Print / Save as PDF</button>
      </div>
    <?php } ?>
  </main>
</body>
</html>
