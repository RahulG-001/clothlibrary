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

$authSalesman = salesman_require_auth($con);

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
if ($order_id <= 0) {
    $response['message'] = 'order_id is required.';
    echo json_encode($response);
    exit;
}

// Get order (must be cart status and belong to this salesman)
$orderRes = mysqli_query($con, "SELECT id, user_id, salesman_id, status FROM sales_order WHERE id = '".$order_id."' AND salesman_id = '".(int)$authSalesman['id']."' LIMIT 1");
if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    $response['message'] = 'Order not found.';
    echo json_encode($response);
    exit;
}
$order = mysqli_fetch_assoc($orderRes);
if ($order['status'] !== 'cart') {
    $response['message'] = 'Order already placed or invalid status.';
    echo json_encode($response);
    exit;
}

// Get order items
$itemsRes = mysqli_query($con, "SELECT oi.id, oi.itemcode, oi.quantity FROM sales_order_item oi WHERE oi.order_id = '".$order_id."'");
if (!$itemsRes || mysqli_num_rows($itemsRes) === 0) {
    $response['message'] = 'Order has no items.';
    echo json_encode($response);
    exit;
}

$deductErrors = [];
mysqli_begin_transaction($con);

while ($row = mysqli_fetch_assoc($itemsRes)) {
    $itemcode_esc = mysqli_real_escape_string($con, $row['itemcode']);
    $orderQty = (float)$row['quantity'];

    $prodRes = mysqli_query($con, "SELECT quantity FROM indiaData WHERE itemcode = '".$itemcode_esc."' LIMIT 1");
    if (!$prodRes || mysqli_num_rows($prodRes) === 0) {
        $deductErrors[] = $row['itemcode'].': product not found';
        continue;
    }
    $prod = mysqli_fetch_assoc($prodRes);
    $currentStr = isset($prod['quantity']) ? $prod['quantity'] : '';
    $available = parse_quantity_to_number($currentStr);

    if ($orderQty > $available) {
        $deductErrors[] = $row['itemcode'].": ordered ".$orderQty." exceeds available ".$available;
        continue;
    }

    $newQty = $available - $orderQty;
    $newStr = format_quantity_for_db($newQty);
    $newStrEsc = mysqli_real_escape_string($con, $newStr);
    $up = mysqli_query($con, "UPDATE indiaData SET quantity = '".$newStrEsc."' WHERE itemcode = '".$itemcode_esc."'");
    if (!$up) {
        $deductErrors[] = $row['itemcode'].': update failed';
    }
}

if (!empty($deductErrors)) {
    mysqli_rollback($con);
    $response['success'] = false;
    $response['message'] = 'Could not deduct stock: '.implode('; ', $deductErrors);
    $response['data'] = null;
    echo json_encode($response);
    exit;
}

$upOrder = mysqli_query($con, "UPDATE sales_order SET status = 'placed' WHERE id = '".$order_id."'");
if (!$upOrder) {
    mysqli_rollback($con);
    $response['message'] = 'Failed to update order status.';
    echo json_encode($response);
    exit;
}

mysqli_commit($con);

$response['success'] = true;
$response['message'] = 'Order placed successfully. Stock deducted.';
$response['data'] = [
    'order_id'     => $order_id,
    'user_id'      => $order['user_id'],
    'salesman_id'  => (int)$order['salesman_id'],
    'status'       => 'placed'
];

echo json_encode($response);
exit;
