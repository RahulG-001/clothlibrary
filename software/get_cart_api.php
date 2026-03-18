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

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

$authSalesman = salesman_require_auth($con);

// We only care about the current cart for a user + salesman
$salesman_id = (int)$authSalesman['id'];

// Latest cart order (status = cart) for this user and salesman
$orderQuery = "
    SELECT o.id AS order_id, o.user_id, o.salesman_id, o.status, o.created_at, o.updated_at
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

// Items in the cart. meters column in DB = total meters for the line.
$itemsQuery = "
    SELECT 
        oi.id AS line_id,
        oi.itemcode,
        oi.quantity       AS order_quantity,
        COALESCE(oi.meters,0) AS order_total_meters,
        p.id             AS product_id,
        p.description,
        p.image,
        p.width,
        p.type,
        p.quantity       AS db_quantity
    FROM sales_order_item oi
    LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
    WHERE oi.order_id = '".$oid."'
";
$itemsRes = mysqli_query($con, $itemsQuery);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$products = [];
while ($row = mysqli_fetch_assoc($itemsRes)) {
    $img = isset($row['image']) ? $row['image'] : '';
    $row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';

    $q       = (float)$row['order_quantity'];
    $totalM  = (float)$row['order_total_meters'];

    $row['quantity']      = $q;
    $row['total_meters']  = $totalM;
    $row['meters']        = $q > 0 ? round($totalM / $q, 2) : 0; // per-piece meters for UI
    $row['available_quantity'] = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');

    unset($row['order_quantity'], $row['order_total_meters'], $row['db_quantity']);
    $products[] = $row;
}

$response['success'] = true;
$response['message'] = 'Cart fetched successfully.';
$response['data'] = [
    'order_id'     => $oid,
    'user_id'      => $order['user_id'],
    'salesman_id'  => (int)$order['salesman_id'],
    'status'       => $order['status'],
    'created_at'   => $order['created_at'],
    'updated_at'   => $order['updated_at'],
    'products'     => $products
];

echo json_encode($response);
exit;

