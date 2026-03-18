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

$order_id   = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$salesman_id = (int)$authSalesman['id'];

if ($order_id <= 0 && $salesman_id <= 0) {
    $response['message'] = 'Provide order_id OR be authenticated as a salesman.';
    echo json_encode($response);
    exit;
}

$where = [];
if ($order_id > 0) {
    $where[] = "o.id = '".$order_id."'";
}
if ($salesman_id > 0) {
    $where[] = "o.salesman_id = '".$salesman_id."'";
}
$where[] = "o.status = 'placed'";
$whereSql = ' WHERE '.implode(' AND ', $where).' ORDER BY o.id DESC LIMIT 1';

$orderQuery = "SELECT o.id AS order_id, o.user_id, o.salesman_id, o.status, o.created_at, o.updated_at
               FROM sales_order o".$whereSql;
$orderRes = mysqli_query($con, $orderQuery);

if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    $response['message'] = 'Order not found.';
    echo json_encode($response);
    exit;
}

$order = mysqli_fetch_assoc($orderRes);
$oid = (int)$order['order_id'];

// Get items with product details; price and available qty from product (price not stored in order)
$itemsQuery = "SELECT oi.id AS line_id, oi.itemcode, oi.quantity AS order_quantity, COALESCE(oi.meters,0) AS order_total_meters,
               p.id AS product_id, p.description, p.image, p.width, p.type, p.quantity AS db_quantity
               FROM sales_order_item oi
               LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
               WHERE oi.order_id = '".$oid."'";
$itemsRes = mysqli_query($con, $itemsQuery);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$products = [];
while ($row = mysqli_fetch_assoc($itemsRes)) {
    $img = isset($row['image']) ? $row['image'] : '';
    $row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';
    $q = (float)$row['order_quantity'];
    $totalM = (float)$row['order_total_meters'];
    $row['quantity'] = $q;
    $row['total_meters'] = $totalM;
    $row['meters'] = $q > 0 ? round($totalM / $q, 2) : 0;
    $row['available_quantity'] = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');
    $row['price'] = isset($row['price']) ? $row['price'] : null;
    unset($row['order_quantity'], $row['order_total_meters'], $row['db_quantity']);
    $products[] = $row;
}

$response['success'] = true;
$response['message'] = 'Order fetched successfully.';
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
