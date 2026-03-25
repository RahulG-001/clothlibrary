<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');
require_once('salesman_auth.php');
require_once('quantity_parser.php');
require_once('product_stock_helpers.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

$authSalesman = salesman_require_auth($con);
$salesman_id = (int)$authSalesman['id'];

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
if ($salesman_id <= 0 || $order_id <= 0) {
    $response['message'] = 'order_id is required.';
    echo json_encode($response);
    exit;
}

$orderRes = mysqli_query(
    $con,
    "SELECT id AS order_id, user_id, salesman_id, status, created_at, updated_at
     FROM sales_order
     WHERE id = '".$order_id."'
       AND salesman_id = '".$salesman_id."'
     LIMIT 1"
);

if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    $response['message'] = 'Order not found.';
    echo json_encode($response);
    exit;
}

$order = mysqli_fetch_assoc($orderRes);
$orderStatus = isset($order['status']) ? strtolower(trim((string)$order['status'])) : '';

mysqli_begin_transaction($con);
try {
    // If order was already placed, it had its stock deducted in `place_order_api.php`.
    // When we delete a placed order, we must restore that stock back.
    if ($orderStatus === 'placed') {
        $restoreNeedByItemcode = [];
        $itemsRes = mysqli_query(
            $con,
            "SELECT oi.itemcode,
                    COALESCE(oi.quantity,0) AS line_qty,
                    COALESCE(oi.meters,0)  AS line_meters,
                    p.type AS product_type
             FROM sales_order_item oi
             INNER JOIN indiadata p ON p.itemcode = oi.itemcode
             WHERE oi.order_id = '".$order_id."'"
        );

        if (!$itemsRes) {
            throw new Exception('Failed to read order items for stock restore.');
        }

        while ($r = mysqli_fetch_assoc($itemsRes)) {
            $ic = trim((string)$r['itemcode']);
            if ($ic === '') {
                continue;
            }
            if (!isset($restoreNeedByItemcode[$ic])) {
                $restoreNeedByItemcode[$ic] = ['pcs' => 0.0, 'm' => 0.0, 'product_type' => isset($r['product_type']) ? (string)$r['product_type'] : ''];
            }
            $ptype = isset($r['product_type']) ? $r['product_type'] : '';
            $isPcsMode = product_stock_type_is_pcs($ptype);
            if ($isPcsMode) {
                $restoreNeedByItemcode[$ic]['pcs'] += (float)$r['line_qty'];
            } else {
                $restoreNeedByItemcode[$ic]['m'] += (float)$r['line_meters'];
            }
        }

        foreach ($restoreNeedByItemcode as $itemcode => $need) {
            $icEsc = mysqli_real_escape_string($con, $itemcode);
            $availRes = mysqli_query($con, "SELECT quantity, type FROM indiadata WHERE itemcode = '".$icEsc."' LIMIT 1");
            if (!$availRes || mysqli_num_rows($availRes) === 0) {
                throw new Exception('Restore failed: product not found for itemcode '.$itemcode);
            }
            $availRow = mysqli_fetch_assoc($availRes);
            $curAvail = parse_quantity_to_number(isset($availRow['quantity']) ? $availRow['quantity'] : '');
            $ptypeCur = isset($availRow['type']) ? $availRow['type'] : $need['product_type'];
            $isPcsCur = product_stock_type_is_pcs($ptypeCur);
            $addBack = $isPcsCur ? (float)$need['pcs'] : (float)$need['m'];
            $newAvail = $curAvail + $addBack;
            $newAvailEsc = mysqli_real_escape_string($con, format_quantity_for_db($newAvail));

            $up = mysqli_query($con, "UPDATE indiadata SET quantity = '".$newAvailEsc."' WHERE itemcode = '".$icEsc."' LIMIT 1");
            if (!$up) {
                throw new Exception('Restore failed: update quantity failed for itemcode '.$itemcode);
            }
        }
    }

    $deleteItemsRes = mysqli_query(
        $con,
        "DELETE FROM sales_order_item
         WHERE order_id = '".$order_id."'"
    );
    if (!$deleteItemsRes) {
        throw new Exception('Failed to delete order items.');
    }
    $deletedItemLines = mysqli_affected_rows($con);

    $deleteOrderRes = mysqli_query(
        $con,
        "DELETE FROM sales_order
         WHERE id = '".$order_id."'
           AND salesman_id = '".$salesman_id."'
         LIMIT 1"
    );
    if (!$deleteOrderRes || mysqli_affected_rows($con) !== 1) {
        throw new Exception('Failed to delete order.');
    }

    mysqli_commit($con);

    $response['success'] = true;
    $response['message'] = 'Order deleted successfully.';
    $response['data'] = [
        'order_id'            => (int)$order['order_id'],
        'user_id'             => $order['user_id'],
        'salesman_id'         => (int)$order['salesman_id'],
        'deleted_order_items' => (int)$deletedItemLines,
        'deleted_order'       => true
    ];
    echo json_encode($response);
    exit;
} catch (Throwable $e) {
    mysqli_rollback($con);
    $response['message'] = $e->getMessage();
    $response['data'] = [
        'order_id' => (int)$order_id
    ];
    echo json_encode($response);
    exit;
}

