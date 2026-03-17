<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

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

// Require salesman token
$authSalesman = salesman_require_auth($con);

// Identify product by id or itemcode
$id       = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$itemcode = isset($_GET['itemcode']) ? trim((string)$_GET['itemcode']) : '';

if ($id <= 0 && $itemcode === '') {
    $response['message'] = 'id or itemcode is required.';
    echo json_encode($response);
    exit;
}

$where = [];
if ($id > 0) {
    $where[] = "id = '".intval($id)."'";
}
if ($itemcode !== '') {
    $itemcodeEsc = mysqli_real_escape_string($con, $itemcode);
    $where[] = "itemcode = '".$itemcodeEsc."'";
}

$whereSql = ' WHERE '.implode(' AND ', $where).' LIMIT 1';

$query  = "SELECT id, itemcode, image, description, width, quantity, type, trn_date ";
$query .= "FROM indiaData".$whereSql;

$result = mysqli_query($con, $query);

if ($result && mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);

    // Build full image URL
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // images are under ../item_images relative to this script
    $imagePath = $base.'/item_images/'.$row['image'];
    $fullImageUrl = $scheme.'://'.$host.$imagePath;

    // Optional human readable location
    $locationLabel = '';
    if (isset($row['location'])) {
        if ($row['location'] === 'LN') {
            $locationLabel = 'Lajpat Nagar';
        } elseif ($row['location'] === 'SJ') {
            $locationLabel = 'Shahpur Jat';
        }
    }

    $row['image_url'] = $fullImageUrl;
    $row['location_label'] = $locationLabel;

    $response['success'] = true;
    $response['message'] = 'Product fetched successfully.';
    $response['data']    = $row;
} else {
    $response['message'] = 'Product not found.';
}

echo json_encode($response);
exit;

