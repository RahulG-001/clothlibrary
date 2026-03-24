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

$user_id     = isset($input['user_id']) ? trim((string)$input['user_id']) : '';
$salesman_id = (int)$authSalesman['id'];
$rawItems    = isset($input['items']) ? $input['items'] : null;

if (!is_array($rawItems) || empty($rawItems)) {
    $response['message'] = 'items required. For type M: itemcode + meters. For type PCS: itemcode + pieces, quantity, or PCS (count). Optional price (line total). Example M: {"itemcode":"CL001","meters":12.5}  Example PCS: {"itemcode":"CL002","pieces":3} or {"itemcode":"CL002","PCS":3}';
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

$userCheck = mysqli_query(
    $con,
    "SELECT id, userid
     FROM users
     WHERE id = '".$user_id_esc."' 
        OR userid = '".$user_id_esc."'
     ORDER BY (id = '".$user_id_esc."') DESC
     LIMIT 1"
);
if (!$userCheck || mysqli_num_rows($userCheck) === 0) {
    $response['message'] = 'User not found.';
    echo json_encode($response);
    exit;
}
$userRow = mysqli_fetch_assoc($userCheck);
$user_id_stored = isset($userRow['userid']) ? $userRow['userid'] : $user_id;

$smCheck = mysqli_query($con, "SELECT id FROM salesman WHERE id = '".$salesman_id."' LIMIT 1");
if (!$smCheck || mysqli_num_rows($smCheck) === 0) {
    $response['message'] = 'Salesman not found.';
    echo json_encode($response);
    exit;
}

$parsedItems = [];
$rejectedItems = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        $rejectedItems[] = ['item' => $item, 'reason' => 'invalid_item_format'];
        continue;
    }
    $itemcode = isset($item['itemcode']) ? trim((string)$item['itemcode']) : '';
    $metersIn = isset($item['meters']) ? (float)$item['meters'] : 0;
    $piecesIn = 0.0;
    if (isset($item['pieces'])) {
        $piecesIn = (float)$item['pieces'];
    } elseif (isset($item['quantity'])) {
        $piecesIn = (float)$item['quantity'];
    } elseif (isset($item['PCS'])) {
        $piecesIn = (float)$item['PCS'];
    } elseif (isset($item['pcs'])) {
        $piecesIn = (float)$item['pcs'];
    }
    $linePrice = cart_parse_line_price(isset($item['price']) ? $item['price'] : (isset($item['unit_price']) ? $item['unit_price'] : null));

    if ($itemcode === '') {
        $rejectedItems[] = ['itemcode' => '', 'reason' => 'missing_itemcode'];
        continue;
    }

    $itemcode_esc = mysqli_real_escape_string($con, $itemcode);
    $prod = mysqli_query($con, "SELECT itemcode, quantity, type FROM indiadata WHERE LOWER(TRIM(itemcode)) = LOWER(TRIM('".$itemcode_esc."')) LIMIT 1");
    if (!$prod || mysqli_num_rows($prod) === 0) {
        $rejectedItems[] = ['itemcode' => $itemcode, 'reason' => 'product_not_found'];
        continue;
    }
    $prow = mysqli_fetch_assoc($prod);
    $db_itemcode = $prow['itemcode'];
    $ptype = isset($prow['type']) ? $prow['type'] : '';
    $available = parse_quantity_to_number(isset($prow['quantity']) ? $prow['quantity'] : '');

    if (product_stock_type_is_pcs($ptype)) {
        if ($piecesIn <= 0) {
            if ($metersIn > 0) {
                $rejectedItems[] = ['itemcode' => $itemcode, 'reason' => 'pcs_product_use_pieces_not_meters', 'product_type' => 'PCS'];
            } else {
                $rejectedItems[] = ['itemcode' => $itemcode, 'reason' => 'missing_pieces', 'product_type' => 'PCS'];
            }
            continue;
        }
        $parsedItems[] = [
            'itemcode'     => $db_itemcode,
            'itemcode_esc' => mysqli_real_escape_string($con, $db_itemcode),
            'stock_mode'   => 'PCS',
            'amount'       => round($piecesIn, 2),
            'available'    => $available,
            'line_price'   => $linePrice,
        ];
    } else {
        if ($metersIn <= 0) {
            if ($piecesIn > 0) {
                $rejectedItems[] = ['itemcode' => $itemcode, 'reason' => 'meter_product_use_meters_not_pieces', 'product_type' => 'M'];
            } else {
                $rejectedItems[] = ['itemcode' => $itemcode, 'reason' => 'missing_meters', 'product_mode' => 'M'];
            }
            continue;
        }
        $parsedItems[] = [
            'itemcode'     => $db_itemcode,
            'itemcode_esc' => mysqli_real_escape_string($con, $db_itemcode),
            'stock_mode'   => 'M',
            'amount'       => round($metersIn, 2),
            'available'    => $available,
            'line_price'   => $linePrice,
        ];
    }
}

if (empty($parsedItems)) {
    $response['success'] = false;
    $response['message'] = 'No valid items (check product type: M needs meters, PCS needs pieces).';
    $response['data']    = null;
    echo json_encode($response);
    exit;
}

$orderQuery = "SELECT id
                FROM sales_order
                WHERE user_id = '".mysqli_real_escape_string($con, $user_id_stored)."'
                  AND salesman_id = '".$salesman_id."'
                  AND status = 'cart'
                ORDER BY id DESC
                LIMIT 1";
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

$qtyOne = '1';
$added = [];
$failedItems = [];

foreach ($parsedItems as $v) {
    $itemcode_esc = $v['itemcode_esc'];
    $amount = (float)$v['amount'];
    $addLinePrice = $v['line_price'];
    $available = (float)$v['available'];
    $mode = $v['stock_mode'];

    $existSql = "SELECT id, COALESCE(quantity,0) AS line_q, COALESCE(meters,0) AS line_m FROM sales_order_item WHERE order_id = '".$order_id."' AND itemcode = '".$itemcode_esc."' LIMIT 1";
    if ($hasPriceColumn) {
        $existSql = "SELECT id, COALESCE(quantity,0) AS line_q, COALESCE(meters,0) AS line_m, price FROM sales_order_item WHERE order_id = '".$order_id."' AND itemcode = '".$itemcode_esc."' LIMIT 1";
    }
    $exist = mysqli_query($con, $existSql);

    $already = 0.0;
    $ex = null;
    if ($exist && mysqli_num_rows($exist) === 1) {
        $ex = mysqli_fetch_assoc($exist);
        $already = $mode === 'PCS' ? (float)$ex['line_q'] : (float)$ex['line_m'];
    }

    $newTotal = round($already + $amount, 2);
    if ($newTotal > $available + 0.0001) {
        $maxAddable = max(0.0, round($available - $already, 2));
        $unit = $mode === 'PCS' ? 'pieces' : 'meters';
        $failedItems[] = [
            'itemcode'       => $v['itemcode'],
            'reason'         => 'insufficient_stock',
            'stock_mode'     => $mode,
            'requested'      => $newTotal,
            'available'      => $available,
            'already_in_cart'=> $already,
            'add_this_call'  => $amount,
            'unit'           => $unit,
            'max_addable'   => $maxAddable,
        ];
        continue;
    }

    $priceFragment = '';
    if ($hasPriceColumn) {
        $oldP = $ex ? cart_normalize_stored_price(isset($ex['price']) ? $ex['price'] : null) : null;
        $blended = cart_blend_line_price($oldP, $addLinePrice);
        if ($blended === null) {
            $priceFragment = ', price = NULL';
        } else {
            $priceFragment = ", price = '".mysqli_real_escape_string($con, number_format($blended, 4, '.', ''))."'";
        }
    }

    if ($ex) {
        if ($mode === 'M') {
            $newStr = mysqli_real_escape_string($con, number_format($newTotal, 2, '.', ''));
            if ($hasMetersColumn) {
                $ok = mysqli_query($con, "UPDATE sales_order_item SET quantity = '".$qtyOne."', meters = '".$newStr."'".$priceFragment." WHERE id = '".(int)$ex['id']."'");
            } else {
                $ok = mysqli_query($con, "UPDATE sales_order_item SET quantity = '".$qtyOne."'".$priceFragment." WHERE id = '".(int)$ex['id']."'");
            }
        } else {
            $newQ = mysqli_real_escape_string($con, number_format($newTotal, 2, '.', ''));
            $zeroM = mysqli_real_escape_string($con, '0.00');
            if ($hasMetersColumn) {
                $ok = mysqli_query($con, "UPDATE sales_order_item SET quantity = '".$newQ."', meters = '".$zeroM."'".$priceFragment." WHERE id = '".(int)$ex['id']."'");
            } else {
                $ok = mysqli_query($con, "UPDATE sales_order_item SET quantity = '".$newQ."'".$priceFragment." WHERE id = '".(int)$ex['id']."'");
            }
        }
        if ($ok) {
            if ($mode === 'M') {
                $added[] = ['itemcode' => $v['itemcode'], 'stock_mode' => 'M', 'meters_added' => $amount, 'total_meters' => $newTotal];
            } else {
                $added[] = ['itemcode' => $v['itemcode'], 'stock_mode' => 'PCS', 'pieces_added' => $amount, 'total_pieces' => $newTotal];
            }
        } else {
            $failedItems[] = ['itemcode' => $v['itemcode'], 'reason' => 'update_failed', 'db_error' => mysqli_error($con)];
        }
    } else {
        $priceValSql = 'NULL';
        if ($hasPriceColumn && $addLinePrice !== null) {
            $priceValSql = "'".mysqli_real_escape_string($con, number_format($addLinePrice, 4, '.', ''))."'";
        }
        if ($mode === 'M') {
            $mStr = mysqli_real_escape_string($con, number_format($amount, 2, '.', ''));
            if ($hasMetersColumn) {
                $insItem = "INSERT INTO sales_order_item (order_id, itemcode, quantity, meters, price) VALUES ('".$order_id."', '".$itemcode_esc."', '".$qtyOne."', '".$mStr."', ".$priceValSql.")";
            } else {
                $insItem = "INSERT INTO sales_order_item (order_id, itemcode, quantity, price) VALUES ('".$order_id."', '".$itemcode_esc."', '".$qtyOne."', ".$priceValSql.")";
            }
        } else {
            $qStr = mysqli_real_escape_string($con, number_format($amount, 2, '.', ''));
            $zeroM = mysqli_real_escape_string($con, '0.00');
            if ($hasMetersColumn) {
                $insItem = "INSERT INTO sales_order_item (order_id, itemcode, quantity, meters, price) VALUES ('".$order_id."', '".$itemcode_esc."', '".$qStr."', '".$zeroM."', ".$priceValSql.")";
            } else {
                $insItem = "INSERT INTO sales_order_item (order_id, itemcode, quantity, price) VALUES ('".$order_id."', '".$itemcode_esc."', '".$qStr."', ".$priceValSql.")";
            }
        }
        if (mysqli_query($con, $insItem)) {
            if ($mode === 'M') {
                $added[] = ['itemcode' => $v['itemcode'], 'stock_mode' => 'M', 'meters_added' => $amount, 'total_meters' => $amount];
            } else {
                $added[] = ['itemcode' => $v['itemcode'], 'stock_mode' => 'PCS', 'pieces_added' => $amount, 'total_pieces' => $amount];
            }
        } else {
            $failedItems[] = ['itemcode' => $v['itemcode'], 'reason' => 'insert_failed', 'db_error' => mysqli_error($con)];
        }
    }
}

$priceSel = $hasPriceColumn ? ', oi.price AS line_unit_price' : '';
$orderItemsQuery = "SELECT oi.id AS line_id, oi.itemcode, oi.quantity AS order_qty, COALESCE(oi.meters,0) AS order_total_meters, p.description, p.image, p.quantity AS db_quantity, p.type AS product_type".$priceSel."
                    FROM sales_order_item oi
                    LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
                    WHERE oi.order_id = '".$order_id."'";
$orderItemsRes = mysqli_query($con, $orderItemsQuery);
if (!$orderItemsRes) {
    $orderItemsQuery = "SELECT oi.id AS line_id, oi.itemcode, oi.quantity AS order_qty, 1 AS order_total_meters, p.description, p.image, p.quantity AS db_quantity, p.type AS product_type".$priceSel."
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
        $ptype = isset($row['product_type']) ? $row['product_type'] : '';
        $qty = isset($row['order_qty']) ? (float)$row['order_qty'] : 0.0;
        $totalM = isset($row['order_total_meters']) ? (float)$row['order_total_meters'] : 0.0;

        if (product_stock_type_is_pcs($ptype)) {
            $row['stock_mode'] = 'PCS';
            $row['pieces'] = $qty;
            $row['total_pieces'] = $qty;
            $row['meters'] = $totalM;
            $row['total_meters'] = $totalM;
        } else {
            $row['stock_mode'] = 'M';
            $row['meters'] = $totalM;
            $row['total_meters'] = $totalM;
            $row['pieces'] = null;
            $row['total_pieces'] = null;
        }

        $row['available_quantity'] = parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : '');
        unset($row['order_qty'], $row['order_total_meters'], $row['db_quantity'], $row['product_type']);
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
    $hasProblems = count($rejectedItems) > 0 || count($failedItems) > 0;
    $hasOutOfStock = false;
    if (!$hasOutOfStock && is_array($failedItems)) {
        foreach ($failedItems as $fi) {
            if (isset($fi['reason']) && $fi['reason'] === 'insufficient_stock') {
                $hasOutOfStock = true;
                break;
            }
        }
    }
    $response['message'] = $hasProblems
        ? ($hasOutOfStock
            ? 'Cart loaded. Some items are not in stock (see failed_items / rejected_items).'
            : 'Cart loaded. This request did not add new lines (see rejected_items / failed_items).')
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
