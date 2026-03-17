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

$order_id    = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id     = isset($_GET['user_id']) ? trim((string)$_GET['user_id']) : '';
$salesman_id = (int)$authSalesman['id'];

if ($order_id <= 0 && ($user_id === '' || $salesman_id <= 0)) {
    $response['message'] = 'Provide order_id OR both user_id and salesman_id.';
    echo json_encode($response);
    exit;
}

// Find order (latest if user_id+salesman_id provided)
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

$whereSql  = ' WHERE '.implode(' AND ', $where);
$orderSql  = "SELECT o.id AS order_id, o.user_id, o.salesman_id, o.status, o.created_at, o.updated_at
              FROM sales_order o".$whereSql." ORDER BY o.id DESC LIMIT 1";
$orderRes = mysqli_query($con, $orderSql);
if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    $response['message'] = 'Order not found.';
    echo json_encode($response);
    exit;
}
$order = mysqli_fetch_assoc($orderRes);
$oid   = (int)$order['order_id'];

// Get items + current stock string
$itemsSql = "SELECT oi.itemcode, oi.quantity AS order_quantity,
                    p.description, p.image, p.quantity AS db_quantity
             FROM sales_order_item oi
             LEFT JOIN indiaData p ON p.itemcode = oi.itemcode
             WHERE oi.order_id = '".$oid."'";
$itemsRes = mysqli_query($con, $itemsSql);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$items = [];
$allOk = true;

if ($itemsRes) {
    while ($row = mysqli_fetch_assoc($itemsRes)) {
        $ordered   = (float)$row['order_quantity'];
        $available = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');
        $ok        = $ordered <= $available;
        $shortBy   = $ok ? 0.0 : round($ordered - $available, 2);

        if (!$ok) {
            $allOk = false;
        }

        $img = isset($row['image']) ? $row['image'] : '';
        $items[] = [
            'itemcode'            => $row['itemcode'],
            'description'         => isset($row['description']) ? $row['description'] : null,
            'image'               => $img,
            'image_url'           => $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '',
            'ordered_quantity'    => $ordered,
            'available_quantity'  => $available,
            'ok'                  => $ok,
            'short_by'            => $shortBy
        ];
    }
}

$response['success'] = $allOk;
$response['message'] = $allOk
    ? 'All ordered quantities are available.'
    : 'Some items do not have enough available quantity.';
$response['data'] = [
    'order_id'    => $oid,
    'user_id'     => $order['user_id'],
    'salesman_id' => (int)$order['salesman_id'],
    'status'      => $order['status'],
    'items'       => $items
];

echo json_encode($response);
exit;

