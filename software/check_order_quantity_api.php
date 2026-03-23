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
require_once('product_stock_helpers.php');

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

// Per itemcode: sum meters (type M) or pieces (type PCS) vs indiadata.quantity in same units
$itemsSql = "SELECT oi.itemcode, oi.quantity AS order_qty, COALESCE(oi.meters,0) AS order_total_meters,
                    p.description, p.image, p.quantity AS db_quantity, p.type AS product_type
             FROM sales_order_item oi
             LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
             WHERE oi.order_id = '".$oid."'";
$itemsRes = mysqli_query($con, $itemsSql);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$requiredByCode = [];
$metaByCode = [];
if ($itemsRes) {
    while ($row = mysqli_fetch_assoc($itemsRes)) {
        $ic = $row['itemcode'];
        $ptype = isset($row['product_type']) ? $row['product_type'] : '';
        if (!isset($requiredByCode[$ic])) {
            $requiredByCode[$ic] = ['m' => 0.0, 'pcs' => 0.0];
        }
        if (product_stock_type_is_pcs($ptype)) {
            $requiredByCode[$ic]['pcs'] += isset($row['order_qty']) ? (float)$row['order_qty'] : 0.0;
        } else {
            $requiredByCode[$ic]['m'] += (float)$row['order_total_meters'];
        }
        if (!isset($metaByCode[$ic])) {
            $metaByCode[$ic] = [
                'description' => isset($row['description']) ? $row['description'] : null,
                'image' => isset($row['image']) ? $row['image'] : '',
                'available' => parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : ''),
                'product_type' => $ptype
            ];
        }
    }
}

$items = [];
$allOk = true;
foreach ($requiredByCode as $ic => $req) {
    $ptype = isset($metaByCode[$ic]['product_type']) ? $metaByCode[$ic]['product_type'] : '';
    $isPcs = product_stock_type_is_pcs($ptype);
    $totalRequired = $isPcs ? $req['pcs'] : $req['m'];
    $available = $metaByCode[$ic]['available'];
    $ok = $totalRequired <= $available;
    $shortBy = $ok ? 0.0 : round($totalRequired - $available, 2);
    if (!$ok) { $allOk = false; }
    $img = $metaByCode[$ic]['image'];
    $items[] = [
        'itemcode' => $ic,
        'description' => $metaByCode[$ic]['description'],
        'image' => $img,
        'image_url' => $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '',
        'stock_mode' => $isPcs ? 'PCS' : 'M',
        'ordered_total_meters' => $isPcs ? 0.0 : $totalRequired,
        'ordered_total_pieces' => $isPcs ? $totalRequired : null,
        'available_quantity' => $available,
        'ok' => $ok,
        'short_by' => $shortBy
    ];
}

$response['success'] = $allOk;
$response['message'] = $allOk
    ? 'All ordered quantities are in stock.'
    : 'Some items do not have enough stock.';
$response['data'] = [
    'order_id'    => $oid,
    'user_id'     => $order['user_id'],
    'salesman_id' => (int)$order['salesman_id'],
    'status'      => $order['status'],
    'items'       => $items
];

echo json_encode($response);
exit;

