<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require(__DIR__.'/admin/db.php');
require_once(__DIR__.'/salesman_auth.php');
require_once(__DIR__.'/db_tables.php');
require_once(__DIR__.'/quantity_parser.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

// Require salesman token
$authSalesman = salesman_require_auth($con);

$indiaTable = get_india_data_table($con);
if ($indiaTable === null) {
    $response['message'] = "DB error: table indiaData/indiadata not found";
    echo json_encode($response);
    exit;
}

// QR value can be passed as `qr` or `code` or `itemcode`
$qr = '';
if (isset($_GET['qr'])) {
    $qr = trim((string)$_GET['qr']);
} elseif (isset($_GET['code'])) {
    $qr = trim((string)$_GET['code']);
} elseif (isset($_GET['itemcode'])) {
    $qr = trim((string)$_GET['itemcode']);
}

if ($qr === '') {
    $response['message'] = 'qr (or code/itemcode) is required.';
    echo json_encode($response);
    exit;
}

// Build full image URL base
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$qrEsc = mysqli_real_escape_string($con, $qr);

// Treat QR as itemcode (case-insensitive, trimmed)
$query = "SELECT *
          FROM ".$indiaTable."
          WHERE LOWER(TRIM(itemcode)) = LOWER(TRIM('".$qrEsc."'))
          LIMIT 1";

$res = mysqli_query($con, $query);
if (!$res || mysqli_num_rows($res) === 0) {
    $response['message'] = 'Product not found.';
    echo json_encode($response);
    exit;
}

$row = mysqli_fetch_assoc($res);

// Add computed fields
$img = isset($row['image']) ? $row['image'] : '';
$row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';
// Convert DB quantity string to numeric for app usage
$row['available_quantity'] = parse_quantity_to_number(isset($row['quantity']) ? $row['quantity'] : '');

$response['success'] = true;
$response['message'] = 'Product fetched successfully.';
$response['data'] = [
    'itemcode' => isset($row['itemcode']) ? $row['itemcode'] : $qr,
    'product'  => $row
];

echo json_encode($response);
exit;

