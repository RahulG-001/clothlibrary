<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

$rawComment = '';
if (isset($input['salesman_comment'])) {
    $rawComment = (string)$input['salesman_comment'];
} elseif (isset($input['comment'])) {
    $rawComment = (string)$input['comment'];
} elseif (isset($input['order_comment'])) {
    $rawComment = (string)$input['order_comment'];
}
$rawComment = trim($rawComment);
if (strlen($rawComment) > 8000) {
    $rawComment = substr($rawComment, 0, 8000);
}

$hasCommentCol = sales_order_has_salesman_comment_column($con);
if ($rawComment !== '' && !$hasCommentCol) {
    $response['message'] = 'salesman_comment column missing. Run software/admin/alter_sales_order_salesman_comment.sql.';
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

$itemsRes = mysqli_query(
    $con,
    "SELECT oi.itemcode, COALESCE(oi.quantity,0) AS line_qty, COALESCE(oi.meters,0) AS line_meters, p.type AS product_type
     FROM sales_order_item oi
     INNER JOIN indiadata p ON p.itemcode = oi.itemcode
     WHERE oi.order_id = '".$order_id."'"
);
if (!$itemsRes || mysqli_num_rows($itemsRes) === 0) {
    $response['message'] = 'Order has no items.';
    echo json_encode($response);
    exit;
}

$deductByItemcode = [];
while ($row = mysqli_fetch_assoc($itemsRes)) {
    $ic = trim((string)$row['itemcode']);
    if (!isset($deductByItemcode[$ic])) {
        $deductByItemcode[$ic] = ['m' => 0.0, 'pcs' => 0.0, 'type' => isset($row['product_type']) ? $row['product_type'] : ''];
    }
    if (product_stock_type_is_pcs($row['product_type'])) {
        $deductByItemcode[$ic]['pcs'] += (float)$row['line_qty'];
    } else {
        $deductByItemcode[$ic]['m'] += (float)$row['line_meters'];
    }
}

$deductErrors = [];
mysqli_begin_transaction($con);

foreach ($deductByItemcode as $itemcode => $bucket) {
    $itemcode_esc = mysqli_real_escape_string($con, $itemcode);
    $prodRes = mysqli_query($con, "SELECT quantity, type FROM indiadata WHERE itemcode = '".$itemcode_esc."' LIMIT 1");
    if (!$prodRes || mysqli_num_rows($prodRes) === 0) {
        $deductErrors[] = $itemcode.': product not found';
        continue;
    }
    $prod = mysqli_fetch_assoc($prodRes);
    $available = parse_quantity_to_number(isset($prod['quantity']) ? $prod['quantity'] : '');
    $ptype = isset($prod['type']) ? $prod['type'] : '';

    if (product_stock_type_is_pcs($ptype)) {
        $need = (float)$bucket['pcs'];
        if ($need > $available) {
            $deductErrors[] = $itemcode.': need '.$need.' pcs, available '.$available;
            continue;
        }
    } else {
        $need = (float)$bucket['m'];
        if ($need > $available) {
            $deductErrors[] = $itemcode.': need '.$need.' m, available '.$available;
            continue;
        }
    }

    $newQty = $available - $need;
    $newStr = format_quantity_for_db($newQty);
    $newStrEsc = mysqli_real_escape_string($con, $newStr);
    $up = mysqli_query($con, "UPDATE indiadata SET quantity = '".$newStrEsc."' WHERE itemcode = '".$itemcode_esc."'");
    if (!$up) {
        $deductErrors[] = $itemcode.': update failed';
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

$commentEsc = mysqli_real_escape_string($con, $rawComment);
if ($hasCommentCol) {
    $upOrder = mysqli_query($con, "UPDATE sales_order SET status = 'placed', salesman_comment = '".$commentEsc."' WHERE id = '".$order_id."'");
} else {
    $upOrder = mysqli_query($con, "UPDATE sales_order SET status = 'placed' WHERE id = '".$order_id."'");
}
if (!$upOrder) {
    mysqli_rollback($con);
    $response['message'] = 'Failed to update order status.';
    echo json_encode($response);
    exit;
}

mysqli_commit($con);

$orderTotal = null;
if (sales_order_item_has_price_column($con)) {
    $totRes = mysqli_query($con, "SELECT SUM(COALESCE(price,0)) AS t FROM sales_order_item WHERE order_id = '".$order_id."'");
    if ($totRes && ($tr = mysqli_fetch_assoc($totRes)) && isset($tr['t'])) {
        $orderTotal = round((float)$tr['t'], 2);
    } else {
        $orderTotal = 0.0;
    }
}

$response['success'] = true;
$response['message'] = 'Order placed successfully. Stock deducted.';
$response['data'] = [
    'order_id'          => $order_id,
    'user_id'           => $order['user_id'],
    'salesman_id'       => (int)$order['salesman_id'],
    'status'            => 'placed',
    'salesman_comment'  => $hasCommentCol ? $rawComment : '',
    'order_total'       => $orderTotal,
];

echo json_encode($response);
exit;
