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

mysqli_begin_transaction($con);
try {
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

