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

// Accept JSON body
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

$user_id     = isset($input['user_id']) ? trim((string)$input['user_id']) : '';
$salesman_id = (int)$authSalesman['id'];
$rawItems    = isset($input['items']) ? $input['items'] : null;

// Normalize items: accept single object {"itemcode":"X","quantity":1} or array of same
if (!is_array($rawItems) || empty($rawItems)) {
    $response['message'] = 'items required (object or array, e.g. {"itemcode":"CL001","quantity":2}).';
    echo json_encode($response);
    exit;
}
if (isset($rawItems['itemcode']) && !isset($rawItems[0])) {
    $items = [$rawItems];
} else {
    $items = array_values($rawItems);
}

if ($user_id === '' || $salesman_id <= 0) {
    $response['message'] = 'user_id and salesman_id are required.';
    echo json_encode($response);
    exit;
}

$user_id_esc = mysqli_real_escape_string($con, $user_id);

// Check user exists (support both users.id and users.userid)
$userCheck = mysqli_query($con, "SELECT id, userid FROM users WHERE id = '".$user_id_esc."' OR userid = '".$user_id_esc."' LIMIT 1");
if (!$userCheck || mysqli_num_rows($userCheck) === 0) {
    $response['message'] = 'User not found.';
    echo json_encode($response);
    exit;
}
$userRow = mysqli_fetch_assoc($userCheck);
$user_id_stored = isset($userRow['userid']) ? $userRow['userid'] : $user_id;

// Check salesman exists
$smCheck = mysqli_query($con, "SELECT id FROM salesman WHERE id = '".$salesman_id."' LIMIT 1");
if (!$smCheck || mysqli_num_rows($smCheck) === 0) {
    $response['message'] = 'Salesman not found.';
    echo json_encode($response);
    exit;
}

// Validate items: add every item that exists in indiaData (case-insensitive match, trim spaces)
$validItems = [];
$rejectedItems = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        $rejectedItems[] = ['item' => $item, 'reason' => 'invalid_item_format'];
        continue;
    }
    $itemcode = isset($item['itemcode']) ? trim((string)$item['itemcode']) : '';
    $qty      = isset($item['quantity']) ? (float)$item['quantity'] : 0;
    if ($itemcode === '' || $qty <= 0) {
        $rejectedItems[] = ['itemcode' => $itemcode, 'quantity' => $qty, 'reason' => 'missing_itemcode_or_quantity'];
        continue;
    }
    $itemcode_esc = mysqli_real_escape_string($con, $itemcode);
    // Match product by itemcode (case-insensitive, trim) so CL001 matches cl001 or " CL001 "
    $prod = mysqli_query($con, "SELECT itemcode, quantity FROM indiaData WHERE LOWER(TRIM(itemcode)) = LOWER(TRIM('".$itemcode_esc."')) LIMIT 1");
    if (!$prod || mysqli_num_rows($prod) === 0) {
        $rejectedItems[] = ['itemcode' => $itemcode, 'quantity' => $qty, 'reason' => 'product_not_found'];
        continue;
    }
    $prow = mysqli_fetch_assoc($prod);
    $db_itemcode = $prow['itemcode'];
    $available = parse_quantity_to_number(isset($prow['quantity']) ? $prow['quantity'] : '');
    $validItems[] = [
        'itemcode' => $db_itemcode,
        'itemcode_esc' => mysqli_real_escape_string($con, $db_itemcode),
        'quantity' => $qty,
        'available' => $available
    ];
}

// If no valid items: do not create any order, return success false
if (empty($validItems)) {
    $response['success'] = false;
    $response['message'] = 'No valid items (check itemcode exists and quantity >= 1).';
    $response['data']    = null;
    echo json_encode($response);
    exit;
}

// Get or create cart order (status = cart) only when we have valid items
$orderQuery = "SELECT id FROM sales_order WHERE user_id = '".mysqli_real_escape_string($con, $user_id_stored)."' AND salesman_id = '".$salesman_id."' AND status = 'cart' LIMIT 1";
$orderRes   = mysqli_query($con, $orderQuery);
$order_id   = null;

if ($orderRes && mysqli_num_rows($orderRes) === 1) {
    $order_id = (int)mysqli_fetch_assoc($orderRes)['id'];
} else {
    $insOrder = "INSERT INTO sales_order (user_id, salesman_id, status) VALUES ('".mysqli_real_escape_string($con, $user_id_stored)."', '".$salesman_id."', 'cart')";
    if (mysqli_query($con, $insOrder)) {
        $order_id = (int)mysqli_insert_id($con);
    }
}

if ($order_id === null) {
    $response['message'] = 'Could not create or find order.';
    echo json_encode($response);
    exit;
}

$added = [];
$failedItems = [];
foreach ($validItems as $v) {
    $itemcode_esc = $v['itemcode_esc'];
    $qty = $v['quantity'];

    $exist = mysqli_query($con, "SELECT id, quantity FROM sales_order_item WHERE order_id = '".$order_id."' AND itemcode = '".$itemcode_esc."' LIMIT 1");
    if ($exist && mysqli_num_rows($exist) === 1) {
        $ex = mysqli_fetch_assoc($exist);
        $newQty = (float)$ex['quantity'] + $qty;
        $ok = mysqli_query($con, "UPDATE sales_order_item SET quantity = '".mysqli_real_escape_string($con, $newQty)."' WHERE id = '".(int)$ex['id']."'");
        if ($ok) {
            $added[] = ['itemcode' => $v['itemcode'], 'quantity' => $newQty];
        } else {
            $failedItems[] = ['itemcode' => $v['itemcode'], 'quantity' => $newQty, 'reason' => 'update_failed'];
        }
    } else {
        $insItem = "INSERT INTO sales_order_item (order_id, itemcode, quantity, price) VALUES ('".$order_id."', '".$itemcode_esc."', '".mysqli_real_escape_string($con, $qty)."', NULL)";
        if (mysqli_query($con, $insItem)) {
            $added[] = ['itemcode' => $v['itemcode'], 'quantity' => $qty];
        } else {
            $failedItems[] = ['itemcode' => $v['itemcode'], 'quantity' => $qty, 'reason' => 'insert_failed'];
        }
    }
}

// Build response: products with available qty from indiaData (price not stored in order)
$orderItemsQuery = "SELECT oi.itemcode, oi.quantity AS order_quantity, p.description, p.image, p.quantity AS db_quantity
                    FROM sales_order_item oi
                    LEFT JOIN indiaData p ON p.itemcode = oi.itemcode
                    WHERE oi.order_id = '".$order_id."'";
$orderItemsRes = mysqli_query($con, $orderItemsQuery);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$products = [];
while ($row = mysqli_fetch_assoc($orderItemsRes)) {
    $img = isset($row['image']) ? $row['image'] : '';
    $row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';
    $row['quantity'] = (float)$row['order_quantity'];
    $row['available_quantity'] = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');
    unset($row['order_quantity'], $row['db_quantity']);
    $products[] = $row;
}

$response['success'] = count($added) > 0;
$response['message'] = count($added) > 0 ? 'Items added to cart.' : 'No items were added to cart.';
$response['data'] = [
    'order_id'     => $order_id,
    'user_id'      => $user_id_stored,
    'salesman_id'  => $salesman_id,
    'status'       => 'cart',
    'products'     => $products
];

echo json_encode($response);
exit;