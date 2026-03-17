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
$user_id    = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
$salesman_id = (int)$authSalesman['id'];

if ($order_id <= 0 && ($user_id === '' || $salesman_id <= 0)) {
    $response['message'] = 'Provide order_id OR both user_id and salesman_id.';
    echo json_encode($response);
    exit;
}

$where = [];
if ($order_id > 0) {
    $where[] = "o.id = '".$order_id."'";
}
if ($user_id !== '') {
    $user_id_esc = mysqli_real_escape_string($con, $user_id);
    $where[] = "o.user_id = '".$user_id_esc."'";
}
if ($salesman_id > 0) {
    $where[] = "o.salesman_id = '".$salesman_id."'";
}
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
$itemsQuery = "SELECT oi.itemcode, oi.quantity AS order_quantity,
               p.id AS product_id, p.description, p.image, p.width, p.type, p.quantity AS db_quantity
               FROM sales_order_item oi
               LEFT JOIN indiaData p ON p.itemcode = oi.itemcode
               WHERE oi.order_id = '".$oid."'";
$itemsRes = mysqli_query($con, $itemsQuery);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$products = [];
while ($row = mysqli_fetch_assoc($itemsRes)) {
    $img = isset($row['image']) ? $row['image'] : '';
    $row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';
    $row['quantity'] = (float)$row['order_quantity'];
    $row['available_quantity'] = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');
    $row['price'] = isset($row['price']) ? $row['price'] : null;
    unset($row['order_quantity'], $row['db_quantity']);
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
