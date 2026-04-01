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
require_once('sales_order_meta_helpers.php');
require_once('cart_price.php');
require_once('product_stock_helpers.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

$authSalesman = salesman_require_auth($con);

$tempUserPrefix = 'TEMP_';

$usersHasColumn = function ($column) use ($con) {
    $col = mysqli_real_escape_string($con, (string)$column);
    $r = mysqli_query($con, "SHOW COLUMNS FROM `users` LIKE '".$col."'");
    return $r && mysqli_num_rows($r) > 0;
};

$pickString = function ($src, $keys) {
    if (!is_array($src)) {
        return '';
    }
    foreach ($keys as $k) {
        if (isset($src[$k]) && trim((string)$src[$k]) !== '') {
            return trim((string)$src[$k]);
        }
    }
    return '';
};

$generateCustomerUserid = function () use ($con) {
    for ($i = 0; $i < 12; $i++) {
        $id = 'C'.date('Ymd').'_'.substr(bin2hex(random_bytes(4)), 0, 8);
        $idEsc = mysqli_real_escape_string($con, $id);
        $r = mysqli_query($con, "SELECT id FROM users WHERE userid = '".$idEsc."' LIMIT 1");
        if ($r && mysqli_num_rows($r) === 0) {
            return $id;
        }
    }
    return null;
};

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

// Optional: allow attaching/creating customer during place-order
$requestedUserId = '';
if (isset($input['user_id']) && trim((string)$input['user_id']) !== '') {
    $requestedUserId = trim((string)$input['user_id']);
} elseif (isset($input['userid']) && trim((string)$input['userid']) !== '') {
    $requestedUserId = trim((string)$input['userid']);
} elseif (isset($input['id']) && trim((string)$input['id']) !== '') {
    // Some app clients send numeric users.id as `id`
    $requestedUserId = trim((string)$input['id']);
} elseif (isset($input['customer_id']) && trim((string)$input['customer_id']) !== '') {
    $requestedUserId = trim((string)$input['customer_id']);
}

$customerName = $pickString($input, ['customer_name', 'name', 'full_name']);
$customerAddress = $pickString($input, ['customer_address', 'address']);

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
$orderMeta = sales_order_meta_from_input($input);
$orderMetaSaved = false;
$orderMetaWarning = '';

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

$orderUserId = isset($order['user_id']) ? (string)$order['user_id'] : '';

// Create customer now (place order time) and attach its `users.userid` to this order.
// This is used when cart has no customer attached (or has legacy/temp invalid value).
$createCustomerAndAttach = function (string $cName, string $cAddress) use (
    $con,
    $generateCustomerUserid,
    $usersHasColumn,
    $order_id,
    &$orderUserId
) {
    if ($cName === '') {
        return null;
    }

    // Ensure required columns exist (created by alter_users_customer_fields.sql)
    if (
        !$usersHasColumn('userid') ||
        !$usersHasColumn('password') ||
        !$usersHasColumn('name') ||
        !$usersHasColumn('address')
    ) {
        return null;
    }

    $newUserid = $generateCustomerUserid();
    if ($newUserid === null) {
        return null;
    }

    $defaultPassword = '123456';
    $useridEsc = mysqli_real_escape_string($con, $newUserid);
    $pwEsc     = mysqli_real_escape_string($con, $defaultPassword);
    $nameWithUserid = trim($cName).' ('.$newUserid.')';
    $nameEsc   = mysqli_real_escape_string($con, $nameWithUserid);
    $addrEsc   = mysqli_real_escape_string($con, $cAddress);

    // visiting_card handled via customer_create_api (multipart). For place-order we keep it NULL.
    $ins = mysqli_query(
        $con,
        "INSERT INTO users (userid, password, name, address, visiting_card)
         VALUES ('".$useridEsc."', '".$pwEsc."', '".$nameEsc."', '".$addrEsc."', NULL)"
    );
    if (!$ins) {
        return null;
    }

    $upU = mysqli_query($con, "UPDATE sales_order SET user_id = '".$useridEsc."' WHERE id = '".$order_id."' LIMIT 1");
    if (!$upU) {
        return null;
    }

    $orderUserId = $newUserid;
    return $newUserid;
};

// If cart was created without a customer (TEMP_*), save customer now and attach to order.
// Also allow overriding order user_id with a real existing user_id provided in request.
if ($requestedUserId !== '') {
    $reqEsc = mysqli_real_escape_string($con, $requestedUserId);
    $reqAsInt = ctype_digit($requestedUserId) ? (int)$requestedUserId : 0;

    // If the app sends numeric users.id, keep saving that id into sales_order.user_id.
    // Otherwise, save users.userid (login id).
    $uRes = mysqli_query(
        $con,
        "SELECT id, userid FROM users
         WHERE ".($reqAsInt > 0 ? "id = '".$reqAsInt."'" : "userid = '".$reqEsc."'")."
         LIMIT 1"
    );
    if (!$uRes || mysqli_num_rows($uRes) === 0) {
        $response['message'] = 'Provided user_id not found.';
        echo json_encode($response);
        exit;
    }
    $u = mysqli_fetch_assoc($uRes);
    $valueToStore = '';
    if ($reqAsInt > 0 && isset($u['id'])) {
        $valueToStore = (string)((int)$u['id']); // store numeric id as string
    } else {
        $valueToStore = isset($u['userid']) ? (string)$u['userid'] : $requestedUserId;
    }

    $valueToStoreEsc = mysqli_real_escape_string($con, $valueToStore);
    if ($valueToStore !== $orderUserId) {
        $upU = mysqli_query($con, "UPDATE sales_order SET user_id = '".$valueToStoreEsc."' WHERE id = '".$order_id."' LIMIT 1");
        if (!$upU) {
            $response['message'] = 'Failed to attach user to order.';
            echo json_encode($response);
            exit;
        }
        $orderUserId = $valueToStore;
    }
} elseif ($orderUserId === '' || strpos($orderUserId, $tempUserPrefix) === 0) {
    if ($customerName === '') {
        $response['message'] = 'customer_name (or name) is required to save customer at place order.';
        echo json_encode($response);
        exit;
    }
    $newUserid = $createCustomerAndAttach($customerName, $customerAddress);
    if ($newUserid === null) {
        $response['message'] = 'Could not create customer and attach to order.';
        echo json_encode($response);
        exit;
    }
} else {
    // Legacy/bug safety: if cart already has something in sales_order.user_id,
    // ensure it maps to a real users.userid. If it doesn't, try resolving by name,
    // otherwise create a new customer using provided customer_name.
    $orderUserIdEsc = mysqli_real_escape_string($con, $orderUserId);
    $orderUserIdAsInt = ctype_digit($orderUserId) ? (int)$orderUserId : 0;
    $uRes = mysqli_query(
        $con,
        "SELECT userid FROM users
         WHERE userid = '".$orderUserIdEsc."' ".($orderUserIdAsInt > 0 ? "OR id = '".$orderUserIdAsInt."'" : '')."
         LIMIT 1"
    );

    if ($uRes && mysqli_num_rows($uRes) === 1) {
        $u = mysqli_fetch_assoc($uRes);
        $realUserid = isset($u['userid']) ? (string)$u['userid'] : $orderUserId;
        if ($realUserid !== $orderUserId) {
            $realUseridEsc = mysqli_real_escape_string($con, $realUserid);
            mysqli_query($con, "UPDATE sales_order SET user_id = '".$realUseridEsc."' WHERE id = '".$order_id."' LIMIT 1");
            $orderUserId = $realUserid;
        }
    } else {
        if ($customerName === '') {
            $response['message'] = 'Invalid customer user_id in cart. Provide customer_name to fix.';
            echo json_encode($response);
            exit;
        }

        $cNameEsc = mysqli_real_escape_string($con, $customerName);
        $byNameRes = mysqli_query(
            $con,
            "SELECT userid FROM users
             WHERE LOWER(TRIM(name)) = LOWER(TRIM('".$cNameEsc."'))
             ORDER BY id DESC
             LIMIT 1"
        );

        if ($byNameRes && mysqli_num_rows($byNameRes) === 1) {
            $u = mysqli_fetch_assoc($byNameRes);
            $realUserid = isset($u['userid']) ? (string)$u['userid'] : '';
            if ($realUserid !== '') {
                $realUseridEsc = mysqli_real_escape_string($con, $realUserid);
                mysqli_query($con, "UPDATE sales_order SET user_id = '".$realUseridEsc."' WHERE id = '".$order_id."' LIMIT 1");
                $orderUserId = $realUserid;
            }
        } else {
            $newUserid = $createCustomerAndAttach($customerName, $customerAddress);
            if ($newUserid === null) {
                $response['message'] = 'Could not create customer and attach to order.';
                echo json_encode($response);
                exit;
            }
        }
    }
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

if (sales_order_meta_has_any($orderMeta)) {
    if (!sales_order_meta_table_exists($con)) {
        // Optional block: do not fail order placement if meta table is missing.
        $orderMetaWarning = 'Order placed, but extra bill fields were not saved (sales_order_meta table missing).';
    } else {
        $okMeta = sales_order_meta_upsert($con, $order_id, $orderMeta);
        if (!$okMeta) {
            // Optional block: do not fail order placement if meta save fails.
            $orderMetaWarning = 'Order placed, but extra bill fields were not saved.';
        } else {
            $orderMetaSaved = true;
        }
    }
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
    'user_id'           => $orderUserId !== '' ? $orderUserId : $order['user_id'],
    'salesman_id'       => (int)$order['salesman_id'],
    'status'            => 'placed',
    'salesman_comment'  => $hasCommentCol ? $rawComment : '',
    'order_total'       => $orderTotal,
    'order_meta'        => $orderMeta,
    'order_meta_saved'  => $orderMetaSaved,
];
if ($orderMetaWarning !== '') {
    $response['meta_warning'] = $orderMetaWarning;
}

echo json_encode($response);
exit;
