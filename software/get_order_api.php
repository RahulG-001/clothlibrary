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

$order_id   = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$salesman_id = (int)$authSalesman['id'];

if ($salesman_id <= 0) {
    $response['message'] = 'Authenticate as a salesman.';
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
if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($con, $search);
    $where[] = "(CAST(o.id AS CHAR) LIKE '%".$searchEsc."%' OR EXISTS (
        SELECT 1
        FROM sales_order_item soi
        LEFT JOIN indiadata ip ON ip.itemcode = soi.itemcode
        WHERE soi.order_id = o.id
          AND (
              soi.itemcode LIKE '%".$searchEsc."%'
              OR ip.description LIKE '%".$searchEsc."%'
          )
    ))";
}
$where[] = "o.status = 'placed'";
$whereSql = ' WHERE '.implode(' AND ', $where).' ORDER BY o.id DESC';

$commentSel = sales_order_has_salesman_comment_column($con) ? ', o.salesman_comment' : '';
$orderQuery = "SELECT o.id AS order_id, o.user_id, o.salesman_id, o.status, o.created_at, o.updated_at".$commentSel."
               FROM sales_order o".$whereSql;
$orderRes = mysqli_query($con, $orderQuery);

if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    $response['message'] = 'No placed orders found.';
    echo json_encode($response);
    exit;
}

$ordersById = [];
$orderIds = [];
while ($order = mysqli_fetch_assoc($orderRes)) {
    $oid = (int)$order['order_id'];
    $orderIds[] = $oid;
    $ordersById[$oid] = [
        'order_id'          => $oid,
        'user_id'           => $order['user_id'],
        'salesman_id'       => (int)$order['salesman_id'],
        'status'            => $order['status'],
        'created_at'        => $order['created_at'],
        'updated_at'        => $order['updated_at'],
        'salesman_comment'  => isset($order['salesman_comment']) ? (string)$order['salesman_comment'] : '',
        'order_total'       => 0.0,
        'products'          => []
    ];
}

$priceSel = $hasLinePrice ? ', oi.price AS line_unit_price' : '';
$itemsQuery = "SELECT oi.order_id, oi.id AS line_id, oi.itemcode, oi.quantity AS order_qty,
               COALESCE(oi.meters,0) AS order_total_meters,
               p.id AS product_id, p.description, p.image, p.width, p.type AS product_type, p.quantity AS db_quantity".$priceSel."
               FROM sales_order_item oi
               LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
               WHERE oi.order_id IN (".implode(',', $orderIds).")";
$itemsRes = mysqli_query($con, $itemsQuery);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

while ($row = mysqli_fetch_assoc($itemsRes)) {
    $img = isset($row['image']) ? $row['image'] : '';
    $row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';
    $ptype = isset($row['product_type']) ? $row['product_type'] : '';
    $qty = isset($row['order_qty']) ? (float)$row['order_qty'] : 0.0;
    $totalM = (float)$row['order_total_meters'];
    $row['type'] = $ptype;
    if (product_stock_type_is_pcs($ptype)) {
        $row['stock_mode'] = 'PCS';
        $row['pieces'] = $qty;
        $row['total_pieces'] = $qty;
        $row['total_meters'] = $totalM;
        $row['meters'] = $totalM;
    } else {
        $row['stock_mode'] = 'M';
        $row['total_meters'] = $totalM;
        $row['meters'] = $totalM;
        $row['pieces'] = null;
        $row['total_pieces'] = null;
    }
    $row['available_quantity'] = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');
    $oid = (int)$row['order_id'];
    unset($row['order_qty'], $row['order_total_meters'], $row['db_quantity'], $row['product_type']);
    $priceRow = [
        'total_meters'     => $totalM,
        'line_unit_price'  => isset($row['line_unit_price']) ? $row['line_unit_price'] : null,
    ];
    cart_attach_line_amounts($priceRow, $hasLinePrice);
    $row['price'] = $priceRow['price'];
    $row['line_total'] = $priceRow['line_total'];
    unset($row['line_unit_price']);
    if (isset($ordersById[$oid])) {
        $ordersById[$oid]['products'][] = $row;
        if ($row['line_total'] !== null) {
            $ordersById[$oid]['order_total'] = round($ordersById[$oid]['order_total'] + (float)$row['line_total'], 2);
        }
    }
}

$response['success'] = true;
$response['message'] = 'Placed orders fetched successfully.';
$response['data'] = array_values($ordersById);

echo json_encode($response);
exit;
