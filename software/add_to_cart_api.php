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

// Normalize items: meter-only lines (quantity is not used; DB column kept as 1)
if (!is_array($rawItems) || empty($rawItems)) {
    $response['message'] = 'items required. Each: itemcode, meters (> 0), price (optional, line total saved as-is). Example: {"itemcode":"CL001","meters":12.5,"price":500}';
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

// Validate items: add every item that exists in indiadata (case-insensitive match, trim spaces)
$validItems = [];
$rejectedItems = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        $rejectedItems[] = ['item' => $item, 'reason' => 'invalid_item_format'];
        continue;
    }
    $itemcode = isset($item['itemcode']) ? trim((string)$item['itemcode']) : '';
    $addMeters = isset($item['meters']) ? (float)$item['meters'] : 0;
    $linePrice = cart_parse_line_price(isset($item['price']) ? $item['price'] : (isset($item['unit_price']) ? $item['unit_price'] : null));
    if ($itemcode === '' || $addMeters <= 0) {
        $rejectedItems[] = ['itemcode' => $itemcode, 'meters' => $addMeters, 'reason' => 'missing_itemcode_or_meters'];
        continue;
    }
    $itemcode_esc = mysqli_real_escape_string($con, $itemcode);
    // Match product by itemcode (case-insensitive, trim) so CL001 matches cl001 or " CL001 "
    $prod = mysqli_query($con, "SELECT itemcode, quantity FROM indiadata WHERE LOWER(TRIM(itemcode)) = LOWER(TRIM('".$itemcode_esc."')) LIMIT 1");
    if (!$prod || mysqli_num_rows($prod) === 0) {
        $rejectedItems[] = ['itemcode' => $itemcode, 'meters' => $addMeters, 'reason' => 'product_not_found'];
        continue;
    }
    $prow = mysqli_fetch_assoc($prod);
    $db_itemcode = $prow['itemcode'];
    $available = parse_quantity_to_number(isset($prow['quantity']) ? $prow['quantity'] : '');
    $validItems[] = [
        'itemcode' => $db_itemcode,
        'itemcode_esc' => mysqli_real_escape_string($con, $db_itemcode),
        'add_meters' => round($addMeters, 2),
        'available' => $available,
        'line_price' => $linePrice,
    ];
}

// If no valid items: do not create any order, return success false
if (empty($validItems)) {
    $response['success'] = false;
    $response['message'] = 'No valid items (check itemcode exists and meters > 0).';
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

$metersColCheck = mysqli_query($con, "SHOW COLUMNS FROM sales_order_item LIKE 'meters'");
$hasMetersColumn = $metersColCheck && mysqli_num_rows($metersColCheck) > 0;
$hasPriceColumn = sales_order_item_has_price_column($con);

// DB: quantity column fixed at 1; meters = total meters on the line
$qtyOne = '1';
$added = [];
$failedItems = [];
foreach ($validItems as $v) {
    $itemcode_esc = $v['itemcode_esc'];
    $segmentMeters = (float)$v['add_meters'];
    $addLinePrice = $v['line_price'];
    $totalMetersStr = mysqli_real_escape_string($con, number_format($segmentMeters, 2, '.', ''));

    $existSql = "SELECT id, COALESCE(meters,0) AS total_m FROM sales_order_item WHERE order_id = '".$order_id."' AND itemcode = '".$itemcode_esc."' LIMIT 1";
    if ($hasPriceColumn) {
        $existSql = "SELECT id, COALESCE(meters,0) AS total_m, price FROM sales_order_item WHERE order_id = '".$order_id."' AND itemcode = '".$itemcode_esc."' LIMIT 1";
    }
    $exist = mysqli_query($con, $existSql);

    if ($exist && mysqli_num_rows($exist) === 1) {
        $ex = mysqli_fetch_assoc($exist);
        $newTotalM = (float)$ex['total_m'] + $segmentMeters;
        $newTotalMStr = mysqli_real_escape_string($con, number_format($newTotalM, 2, '.', ''));
        $priceFragment = '';
        if ($hasPriceColumn) {
            $oldP = cart_normalize_stored_price(isset($ex['price']) ? $ex['price'] : null);
            $blended = cart_blend_line_price($oldP, $addLinePrice);
            if ($blended === null) {
                $priceFragment = ', price = NULL';
            } else {
                $priceFragment = ", price = '".mysqli_real_escape_string($con, number_format($blended, 4, '.', ''))."'";
            }
        }
        if ($hasMetersColumn) {
            $ok = mysqli_query($con, "UPDATE sales_order_item SET quantity = '".$qtyOne."', meters = '".$newTotalMStr."'".$priceFragment." WHERE id = '".(int)$ex['id']."'");
        } else {
            $ok = mysqli_query($con, "UPDATE sales_order_item SET quantity = '".$qtyOne."'".$priceFragment." WHERE id = '".(int)$ex['id']."'");
        }
        if ($ok) {
            $added[] = ['itemcode' => $v['itemcode'], 'meters_added' => $segmentMeters, 'total_meters' => $newTotalM];
        } else {
            $failedItems[] = ['itemcode' => $v['itemcode'], 'reason' => 'update_failed', 'db_error' => mysqli_error($con)];
        }
    } else {
        $priceValSql = 'NULL';
        if ($hasPriceColumn && $addLinePrice !== null) {
            $priceValSql = "'".mysqli_real_escape_string($con, number_format($addLinePrice, 4, '.', ''))."'";
        }
        if ($hasMetersColumn) {
            if ($hasPriceColumn) {
                $insItem = "INSERT INTO sales_order_item (order_id, itemcode, quantity, meters, price) VALUES ('".$order_id."', '".$itemcode_esc."', '".$qtyOne."', '".$totalMetersStr."', ".$priceValSql.")";
            } else {
                $insItem = "INSERT INTO sales_order_item (order_id, itemcode, quantity, meters, price) VALUES ('".$order_id."', '".$itemcode_esc."', '".$qtyOne."', '".$totalMetersStr."', NULL)";
            }
        } else {
            if ($hasPriceColumn) {
                $insItem = "INSERT INTO sales_order_item (order_id, itemcode, quantity, price) VALUES ('".$order_id."', '".$itemcode_esc."', '".$qtyOne."', ".$priceValSql.")";
            } else {
                $insItem = "INSERT INTO sales_order_item (order_id, itemcode, quantity, price) VALUES ('".$order_id."', '".$itemcode_esc."', '".$qtyOne."', NULL)";
            }
        }
        if (mysqli_query($con, $insItem)) {
            $added[] = ['itemcode' => $v['itemcode'], 'meters_added' => $segmentMeters, 'total_meters' => $segmentMeters];
        } else {
            $failedItems[] = ['itemcode' => $v['itemcode'], 'reason' => 'insert_failed', 'db_error' => mysqli_error($con)];
        }
    }
}

// Build response: products with stock (fallback if `meters` column missing)
$priceSel = $hasPriceColumn ? ', oi.price AS line_unit_price' : '';
$orderItemsQuery = "SELECT oi.id AS line_id, oi.itemcode, COALESCE(oi.meters,0) AS order_total_meters, p.description, p.image, p.quantity AS db_quantity".$priceSel."
                    FROM sales_order_item oi
                    LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
                    WHERE oi.order_id = '".$order_id."'";
$orderItemsRes = mysqli_query($con, $orderItemsQuery);
if (!$orderItemsRes) {
    $orderItemsQuery = "SELECT oi.id AS line_id, oi.itemcode, 1 AS order_total_meters, p.description, p.image, p.quantity AS db_quantity".$priceSel."
                        FROM sales_order_item oi
                        LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
                        WHERE oi.order_id = '".$order_id."'";
    $orderItemsRes = mysqli_query($con, $orderItemsQuery);
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$products = [];
if (!$orderItemsRes) {
    $response['message'] = count($added) > 0 ? 'Items added but list failed: '.mysqli_error($con) : mysqli_error($con);
} else {
    while ($row = mysqli_fetch_assoc($orderItemsRes)) {
        $img = isset($row['image']) ? $row['image'] : '';
        $row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';
        $totalM = isset($row['order_total_meters']) ? (float)$row['order_total_meters'] : 0.0;
        $row['meters'] = $totalM;
        $row['total_meters'] = $totalM;
        $row['available_quantity'] = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');
        unset($row['order_total_meters'], $row['db_quantity']);
        cart_attach_line_amounts($row, $hasPriceColumn);
        $products[] = $row;
    }
}

$addedCount = count($added);
$productCount = count($products);
if (!$orderItemsRes && $addedCount > 0) {
    $response['success'] = true;
    $response['message'] = 'Items added but cart list failed: '.mysqli_error($con);
} elseif ($addedCount > 0) {
    $response['success'] = true;
    $response['message'] = 'Items added to cart.';
} elseif ($productCount > 0) {
    $response['success'] = true;
    $response['message'] = count($rejectedItems) > 0 || count($failedItems) > 0
        ? 'Cart loaded. This request did not add new lines (see rejected_items / failed_items).'
        : 'Cart loaded.';
} else {
    $response['success'] = false;
    $response['message'] = 'No items in cart and nothing was added.';
}

$response['data'] = [
    'order_id'            => $order_id,
    'user_id'             => $user_id_stored,
    'salesman_id'         => $salesman_id,
    'status'              => 'cart',
    'cart_total'          => cart_sum_line_totals($products),
    'added_this_request'  => $added,
    'rejected_items'      => $rejectedItems,
    'failed_items'        => $failedItems,
    'products'            => $products
];

echo json_encode($response);
exit;