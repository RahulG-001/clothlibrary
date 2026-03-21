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
require_once('cart_price.php');

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

$salesman_id   = (int)$authSalesman['id'];
$hasLinePrice  = sales_order_item_has_price_column($con);
$newLinePrice  = cart_parse_line_price(isset($input['price']) ? $input['price'] : (isset($input['unit_price']) ? $input['unit_price'] : null));
$order_id      = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$user_id       = isset($input['user_id']) ? trim((string)$input['user_id']) : '';
$itemcode      = isset($input['itemcode']) ? trim((string)$input['itemcode']) : '';
$line_id       = isset($input['line_id']) ? (int)$input['line_id'] : 0;
$action        = isset($input['action']) ? strtolower(trim((string)$input['action'])) : '';

$step          = isset($input['step']) ? (float)$input['step'] : 1.0;
$setMeters     = isset($input['meters']) ? (float)$input['meters'] : 0.0;

if ($salesman_id <= 0 || $itemcode === '' || ($order_id <= 0 && $user_id === '') || $action === '') {
    $response['message'] = 'Required: salesman auth, itemcode, action, and (order_id OR user_id).';
    echo json_encode($response);
    exit;
}

if (!in_array($action, ['inc', 'dec', 'set', 'set_meters'], true)) {
    $response['message'] = 'action must be inc, dec, set, or set_meters.';
    echo json_encode($response);
    exit;
}

if ($order_id <= 0) {
    $user_id_esc = mysqli_real_escape_string($con, $user_id);
    $ordRes = mysqli_query(
        $con,
        "SELECT id FROM sales_order WHERE user_id = '".$user_id_esc."' AND salesman_id = '".$salesman_id."' AND status = 'cart' ORDER BY id DESC LIMIT 1"
    );
    if (!$ordRes || mysqli_num_rows($ordRes) === 0) {
        $response['message'] = 'Cart order not found for this user/salesman.';
        echo json_encode($response);
        exit;
    }
    $order_id = (int)mysqli_fetch_assoc($ordRes)['id'];
}

$itemcode_esc = mysqli_real_escape_string($con, $itemcode);
$prodRes = mysqli_query(
    $con,
    "SELECT itemcode, quantity, description, image FROM indiadata WHERE LOWER(TRIM(itemcode)) = LOWER(TRIM('".$itemcode_esc."')) LIMIT 1"
);
if (!$prodRes || mysqli_num_rows($prodRes) === 0) {
    $response['message'] = 'Product not found.';
    echo json_encode($response);
    exit;
}
$prod = mysqli_fetch_assoc($prodRes);
$dbItemcode = $prod['itemcode'];
$available = parse_quantity_to_number(isset($prod['quantity']) ? $prod['quantity'] : '');

$dbItemcodeEsc = mysqli_real_escape_string($con, $dbItemcode);
$priceSel = $hasLinePrice ? ', price' : '';
if ($line_id > 0) {
    $itemRes = mysqli_query(
        $con,
        "SELECT id, COALESCE(meters,0) AS total_meters".$priceSel." FROM sales_order_item WHERE id = '".$line_id."' AND order_id = '".$order_id."' LIMIT 1"
    );
} else {
    $itemRes = mysqli_query(
        $con,
        "SELECT id, COALESCE(meters,0) AS total_meters".$priceSel." FROM sales_order_item WHERE order_id = '".$order_id."' AND itemcode = '".$dbItemcodeEsc."' LIMIT 1"
    );
}
$itemRow = ($itemRes && mysqli_num_rows($itemRes) === 1) ? mysqli_fetch_assoc($itemRes) : null;
$currentTotalMeters = $itemRow ? (float)$itemRow['total_meters'] : 0.0;

$newTotalMeters = $currentTotalMeters;

if ($action === 'inc') {
    if ($step <= 0) {
        $step = 1.0;
    }
    $newTotalMeters = round($currentTotalMeters + $step, 2);
} elseif ($action === 'dec') {
    if ($step <= 0) {
        $step = 1.0;
    }
    $newTotalMeters = round($currentTotalMeters - $step, 2);
} elseif ($action === 'set' || $action === 'set_meters') {
    $newTotalMeters = $setMeters >= 0 ? round($setMeters, 2) : 0.0;
}

if ($newTotalMeters < 0) {
    $newTotalMeters = 0.0;
}

$oldTotalMeters = $currentTotalMeters;

if ($newTotalMeters > $oldTotalMeters && $newTotalMeters > $available) {
    $response['success'] = false;
    $response['message'] = 'NOT_ENOUGH_STOCK';
    $response['data'] = [
        'order_id'                 => $order_id,
        'line_id'                  => $itemRow ? (int)$itemRow['id'] : null,
        'itemcode'                 => $dbItemcode,
        'total_meters'             => $oldTotalMeters,
        'requested_total_meters'   => $newTotalMeters,
        'available_meters_stock'   => $available,
    ];
    echo json_encode($response);
    exit;
}

$qtyOne = '1';

if ($newTotalMeters <= 0) {
    if ($itemRow) {
        mysqli_query($con, "DELETE FROM sales_order_item WHERE id = '".(int)$itemRow['id']."' LIMIT 1");
    }
    $outLineId = $itemRow ? (int)$itemRow['id'] : 0;
    $newTotalMeters = 0.0;
} elseif ($itemRow) {
    $newTotalStr = mysqli_real_escape_string($con, number_format($newTotalMeters, 2, '.', ''));
    $priceFragment = '';
    if ($hasLinePrice && $newLinePrice !== null) {
        $priceFragment = ", price = '".mysqli_real_escape_string($con, number_format($newLinePrice, 4, '.', ''))."'";
    }
    mysqli_query(
        $con,
        "UPDATE sales_order_item SET quantity = '".$qtyOne."', meters = '".$newTotalStr."'".$priceFragment." WHERE id = '".(int)$itemRow['id']."' LIMIT 1"
    );
    $outLineId = (int)$itemRow['id'];
} else {
    $newTotalStr = mysqli_real_escape_string($con, number_format($newTotalMeters, 2, '.', ''));
    mysqli_query(
        $con,
        "INSERT INTO sales_order_item (order_id, itemcode, quantity, meters, price) VALUES ('".$order_id."', '".$dbItemcodeEsc."', '".$qtyOne."', '".$newTotalStr."', NULL)"
    );
    $outLineId = (int)mysqli_insert_id($con);
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$img    = isset($prod['image']) ? $prod['image'] : '';
$imageUrl = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';

$unitOut = null;
if ($newTotalMeters > 0 && $hasLinePrice) {
    if ($newLinePrice !== null) {
        $unitOut = $newLinePrice;
    } elseif ($itemRow) {
        $unitOut = cart_normalize_stored_price(isset($itemRow['price']) ? $itemRow['price'] : null);
    }
}
$lineTotalOut = ($newTotalMeters > 0) ? cart_line_total($unitOut) : null;

$cartTotalOut = null;
if ($hasLinePrice) {
    $sumRes = mysqli_query($con, "SELECT SUM(COALESCE(price,0)) AS t FROM sales_order_item WHERE order_id = '".$order_id."'");
    if ($sumRes && ($sr = mysqli_fetch_assoc($sumRes)) && isset($sr['t'])) {
        $cartTotalOut = round((float)$sr['t'], 2);
    } else {
        $cartTotalOut = 0.0;
    }
}

$response['success'] = true;
$response['message'] = '';
$response['data'] = [
    'order_id'               => $order_id,
    'line_id'                => isset($outLineId) ? $outLineId : 0,
    'itemcode'               => $dbItemcode,
    'meters'                 => $newTotalMeters,
    'total_meters'           => $newTotalMeters,
    'price'                  => $unitOut,
    'line_total'             => $lineTotalOut,
    'cart_total'             => $cartTotalOut,
    'available_meters_stock' => $available,
    'description'            => isset($prod['description']) ? $prod['description'] : null,
    'image'                  => $img,
    'image_url'              => $imageUrl,
];

echo json_encode($response);
exit;
