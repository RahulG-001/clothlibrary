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

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    $response['message'] = 'order_id is required.';
    echo json_encode($response);
    exit;
}

// Order header
$orderRes = mysqli_query(
    $con,
    "SELECT id AS order_id, user_id, salesman_id, status, created_at, updated_at
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

// Items + product details (no price)
$itemsQuery = "SELECT oi.id AS line_id, oi.itemcode, oi.quantity AS order_quantity, COALESCE(oi.meters,0) AS order_total_meters,
                      p.description, p.image, p.quantity AS db_quantity, p.width, p.type, p.trn_date
               FROM sales_order_item oi
               LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
               WHERE oi.order_id = '".$oid."'";
$itemsRes = mysqli_query($con, $itemsQuery);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$items = [];
if ($itemsRes) {
    while ($row = mysqli_fetch_assoc($itemsRes)) {
        $img = isset($row['image']) ? $row['image'] : '';
        $q = (float)$row['order_quantity'];
        $totalM = (float)$row['order_total_meters'];
        $items[] = [
            'line_id'             => (int)$row['line_id'],
            'itemcode'            => $row['itemcode'],
            'quantity'            => $q,
            'total_meters'        => $totalM,
            'meters'              => $q > 0 ? round($totalM / $q, 2) : 0,
            'available_quantity'  => parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : ''),
            'description'         => isset($row['description']) ? $row['description'] : null,
            'image'               => $img,
            'image_url'           => $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '',
            'width'               => isset($row['width']) ? $row['width'] : null,
            'type'                => isset($row['type']) ? $row['type'] : null,
            'location'            => isset($row['location']) ? $row['location'] : null,
            'trn_date'            => isset($row['trn_date']) ? $row['trn_date'] : null
        ];
    }
}

$response['success'] = true;
$response['message'] = 'Order details fetched successfully.';
$response['data'] = [
    'order_id'     => $oid,
    'user_id'      => $order['user_id'],
    'salesman_id'  => (int)$order['salesman_id'],
    'status'       => $order['status'],
    'created_at'   => $order['created_at'],
    'updated_at'   => $order['updated_at'],
    'items'        => $items
];

echo json_encode($response);
exit;

