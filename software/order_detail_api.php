<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');
require_once('quantity_parser.php');
require_once('salesman_auth.php');
require_once('sales_order_helpers.php');
require_once('cart_price.php');
require_once('product_stock_helpers.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

$authSalesman = salesman_require_auth($con);

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    $response['message'] = 'order_id is required.';
    echo json_encode($response);
    exit;
}

// Order header
$commentSel = sales_order_has_salesman_comment_column($con) ? ', salesman_comment' : '';
$orderRes = mysqli_query(
    $con,
    "SELECT id AS order_id, user_id, salesman_id, status, created_at, updated_at".$commentSel."
     FROM sales_order
     WHERE id = '".$order_id."'
       AND salesman_id = '".(int)$authSalesman['id']."'
     LIMIT 1"
);

if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    $response['message'] = 'Order not found.';
    echo json_encode($response);
    exit;
}

$order = mysqli_fetch_assoc($orderRes);
$oid = (int)$order['order_id'];

$salesmanName = trim((string)($authSalesman['first_name'] ?? '').' '.(string)($authSalesman['last_name'] ?? ''));
if ($salesmanName === '') {
    $salesmanName = 'ID '.(int)$order['salesman_id'];
}

// Fetch user/customer name from users table (users.userid OR users.id)
$userName = '';
$userIdEsc = mysqli_real_escape_string($con, (string)$order['user_id']);
$uRes = mysqli_query(
    $con,
    "SELECT name
     FROM users
     WHERE id = '".$userIdEsc."'
        OR userid = '".$userIdEsc."'
     LIMIT 1"
);
if ($uRes && mysqli_num_rows($uRes) === 1) {
    $uRow = mysqli_fetch_assoc($uRes);
    $userName = isset($uRow['name']) ? trim((string)$uRow['name']) : '';
}

$hasLinePrice = sales_order_item_has_price_column($con);
$priceSel = $hasLinePrice ? ', oi.price AS line_unit_price' : '';

// Items + product details
$itemsQuery = "SELECT oi.id AS line_id, oi.itemcode, oi.quantity AS order_qty, COALESCE(oi.meters,0) AS order_total_meters,
                      p.description, p.image, p.quantity AS db_quantity, p.width, p.type AS product_type, p.trn_date".$priceSel."
               FROM sales_order_item oi
               LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
               WHERE oi.order_id = '".$oid."'";
$itemsRes = mysqli_query($con, $itemsQuery);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$items = [];
$totalMeters = 0.0;
$totalPieces = 0.0;
$hasPcsItems = false;
$totalAmount = 0.0;
$primaryProductName = null;
if ($itemsRes) {
    while ($row = mysqli_fetch_assoc($itemsRes)) {
        $img = isset($row['image']) ? $row['image'] : '';
        $ptype = isset($row['product_type']) ? $row['product_type'] : '';
        $qty = isset($row['order_qty']) ? (float)$row['order_qty'] : 0.0;
        $totalM = (float)$row['order_total_meters'];
        if ($primaryProductName === null && !empty($row['description'])) {
            $primaryProductName = $row['description'];
        }
        $isPcs = product_stock_type_is_pcs($ptype);
        if ($isPcs) {
            $hasPcsItems = true;
            $totalPieces += $qty;
        } else {
            $totalMeters += $totalM;
        }
        $priceRow = [
            'total_meters'     => $totalM,
            'line_unit_price'  => isset($row['line_unit_price']) ? $row['line_unit_price'] : null,
        ];
        cart_attach_line_amounts($priceRow, $hasLinePrice);
        $lineQty = $isPcs ? $qty : $totalM;
        $lt = null;
        if ($priceRow['price'] !== null) {
            $lt = round(((float)$priceRow['price']) * (float)$lineQty, 2);
            $totalAmount += (float)$lt;
        }
        $line = [
            'line_id'             => (int)$row['line_id'],
            'itemcode'            => $row['itemcode'],
            'meters'              => $totalM,
            'total_meters'        => $totalM,
            'price'               => $priceRow['price'],
            'line_total'          => $lt,
            'available_quantity'  => parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : ''),
            'product_name'        => isset($row['description']) ? $row['description'] : null,
            'description'         => isset($row['description']) ? $row['description'] : null,
            'image'               => $img,
            'image_url'           => $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '',
            'width'               => isset($row['width']) ? $row['width'] : null,
            'type'                => $ptype !== '' ? $ptype : null,
            'stock_mode'          => $isPcs ? 'PCS' : 'M',
            'pieces'              => $isPcs ? $qty : null,
            'total_pieces'        => $isPcs ? $qty : null,
            'location'            => isset($row['location']) ? $row['location'] : null,
            'trn_date'            => isset($row['trn_date']) ? $row['trn_date'] : null
        ];
        $items[] = $line;
    }
}

$response['success'] = true;
$response['message'] = 'Order details fetched successfully.';
$response['data'] = [
    'order_id'          => $oid,
    'product_name'      => $primaryProductName,
    'line_count'        => count($items),
    'total_meters'      => round($totalMeters, 2),
    'total_pieces'      => $hasPcsItems ? round($totalPieces, 2) : null,
    'total_amount'      => round($totalAmount, 2),
    'order_total'       => round($totalAmount, 2),
    'user_id'           => $order['user_id'],
    'user_name'        => $userName,
    'salesman_id'       => (int)$order['salesman_id'],
    'salesman_name'     => $salesmanName,
    'status'            => $order['status'],
    'created_at'        => $order['created_at'],
    'updated_at'        => $order['updated_at'],
    'salesman_comment'  => isset($order['salesman_comment']) ? (string)$order['salesman_comment'] : '',
    'items'             => $items
];

echo json_encode($response);
exit;

