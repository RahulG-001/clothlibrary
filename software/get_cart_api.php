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

$hasLinePrice = sales_order_item_has_price_column($con);

// We only care about the current cart for a user + salesman
$salesman_id = (int)$authSalesman['id'];

// Latest cart order (status = cart) for this user and salesman
$commentSel = sales_order_has_salesman_comment_column($con) ? ', o.salesman_comment' : '';
$orderQuery = "
    SELECT o.id AS order_id, o.user_id, o.salesman_id, o.status, o.created_at, o.updated_at".$commentSel."
    FROM sales_order o
    WHERE o.salesman_id = '".$salesman_id."'
      AND o.status = 'cart'
    ORDER BY o.id DESC
    LIMIT 1
";
$orderRes = mysqli_query($con, $orderQuery);

if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    $response['message'] = 'Cart not found.';
    echo json_encode($response);
    exit;
}

$order = mysqli_fetch_assoc($orderRes);
$oid   = (int)$order['order_id'];

// Safety: never return non-cart orders
if (!isset($order['status']) || $order['status'] !== 'cart') {
    $response['message'] = 'Cart not found.';
    echo json_encode($response);
    exit;
}

// Items: M lines use meters; PCS lines use quantity (pieces), meters 0.
$priceSel = $hasLinePrice ? ', oi.price AS line_unit_price' : '';
$itemsQuery = "
    SELECT 
        oi.id AS line_id,
        oi.itemcode,
        oi.quantity      AS order_qty,
        COALESCE(oi.meters,0) AS order_total_meters,
        p.id             AS product_id,
        p.description,
        p.image,
        p.width,
        p.type           AS product_type,
        p.quantity       AS db_quantity".$priceSel."
    FROM sales_order_item oi
    LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
    WHERE oi.order_id = '".$oid."'
";
$itemsRes = mysqli_query($con, $itemsQuery);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$products = [];
$totalMeters = 0.0;
$totalPieces = 0.0;
$totalAmount = 0.0;
while ($row = mysqli_fetch_assoc($itemsRes)) {
    $img = isset($row['image']) ? $row['image'] : '';
    $row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';

    $ptype = isset($row['product_type']) ? $row['product_type'] : '';
    $qty = isset($row['order_qty']) ? (float)$row['order_qty'] : 0.0;
    $totalM = (float)$row['order_total_meters'];

    if (product_stock_type_is_pcs($ptype)) {
        $row['stock_mode'] = 'PCS';
        $row['pieces'] = $qty;
        $row['total_pieces'] = $qty;
        $row['meters'] = $totalM;
        $row['total_meters'] = $totalM;
        $totalPieces += $qty;
    } else {
        $row['stock_mode'] = 'M';
        $row['meters'] = $totalM;
        $row['total_meters'] = $totalM;
        $row['pieces'] = null;
        $row['total_pieces'] = null;
        $totalMeters += $totalM;
    }

    $row['available_quantity'] = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');

    unset($row['order_qty'], $row['order_total_meters'], $row['db_quantity'], $row['product_type']);
    cart_attach_line_amounts($row, $hasLinePrice);
    // For cart response, line_total should be unit price * ordered qty/meters.
    if ($row['price'] !== null) {
        $lineQty = ($row['stock_mode'] === 'PCS') ? (float)$row['pieces'] : (float)$row['meters'];
        $row['line_total'] = round(((float)$row['price']) * $lineQty, 2);
        $totalAmount += (float)$row['line_total'];
    } else {
        $row['line_total'] = null;
    }
    $products[] = $row;
}

$response['success'] = true;
$response['message'] = 'Cart fetched successfully.';
$response['data'] = [
    'order_id'          => $oid,
    'user_id'           => $order['user_id'],
    'salesman_id'       => (int)$order['salesman_id'],
    'status'            => $order['status'],
    'created_at'        => $order['created_at'],
    'updated_at'        => $order['updated_at'],
    'salesman_comment'  => isset($order['salesman_comment']) ? (string)$order['salesman_comment'] : '',
    'total_meters'      => round($totalMeters, 2),
    'total_pieces'      => round($totalPieces, 2),
    'total_amount'      => round($totalAmount, 2),
    'cart_total'        => round($totalAmount, 2),
    'products'          => $products
];

echo json_encode($response);
exit;

