<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

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

// Require salesman token
$authSalesman = salesman_require_auth($con);
$salesman_id = (int)$authSalesman['id'];

// Accept JSON body or form-data
$input = [];
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = [];
    }
} else {
    $input = $_POST;
}

$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$line_id  = isset($input['line_id']) ? (int)$input['line_id'] : 0;
$itemcode = isset($input['itemcode']) ? trim((string)$input['itemcode']) : '';

if ($salesman_id <= 0 || $order_id <= 0 || ($line_id <= 0 && $itemcode === '')) {
    $response['message'] = 'Required: order_id and (line_id OR itemcode).';
    echo json_encode($response);
    exit;
}

// Verify order belongs to salesman and is cart
$orderRes = mysqli_query(
    $con,
    "SELECT id AS order_id, user_id, salesman_id, status, created_at, updated_at
     FROM sales_order
     WHERE id = '".$order_id."'
       AND salesman_id = '".$salesman_id."'
     LIMIT 1"
);
if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    $response['message'] = 'Cart order not found.';
    echo json_encode($response);
    exit;
}
$order = mysqli_fetch_assoc($orderRes);
// Normalize status because DB values sometimes contain different casing/whitespace.
$orderStatus = isset($order['status']) ? strtolower(trim((string)$order['status'])) : '';
if ($orderStatus !== 'cart') {
    $response['message'] = 'Order already placed or invalid status.';
    $response['data'] = [
        'order_id' => (int)$order_id,
        'actual_status' => isset($order['status']) ? (string)$order['status'] : null,
    ];
    echo json_encode($response);
    exit;
}

// Delete item line
if ($line_id > 0) {
    $del = mysqli_query(
        $con,
        "DELETE FROM sales_order_item
         WHERE id = '".$line_id."'
           AND order_id = '".$order_id."'
         LIMIT 1"
    );
} else {
    $itemcode_esc = mysqli_real_escape_string($con, $itemcode);
    $del = mysqli_query(
        $con,
        "DELETE FROM sales_order_item
         WHERE order_id = '".$order_id."'
           AND LOWER(TRIM(itemcode)) = LOWER(TRIM('".$itemcode_esc."'))"
    );
}

if (!$del) {
    $response['message'] = 'Failed to delete item.';
    echo json_encode($response);
    exit;
}

// Return updated cart products list (same style as get_cart_api)
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
    WHERE oi.order_id = '".$order_id."'
";
$itemsRes = mysqli_query($con, $itemsQuery);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$products = [];
if ($itemsRes) {
    while ($row = mysqli_fetch_assoc($itemsRes)) {
        $img = isset($row['image']) ? $row['image'] : '';
        $row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';

        $q       = (float)$row['order_quantity'];
        $totalM  = (float)$row['order_total_meters'];

        $row['quantity']      = $q;
        $row['total_meters']  = $totalM;
        $row['meters']        = $q > 0 ? round($totalM / $q, 2) : 0;
        $row['available_quantity'] = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');

        unset($row['order_quantity'], $row['order_total_meters'], $row['db_quantity']);
        $products[] = $row;
    }
}

$response['success'] = true;
$response['message'] = 'Item removed from cart.';
$response['data'] = [
    'order_id'     => (int)$order['order_id'],
    'user_id'      => $order['user_id'],
    'salesman_id'  => (int)$order['salesman_id'],
    'status'       => $order['status'],
    'created_at'   => $order['created_at'],
    'updated_at'   => $order['updated_at'],
    'products'     => $products
];

echo json_encode($response);
exit;

?>

