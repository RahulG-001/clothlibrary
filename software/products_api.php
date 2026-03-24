<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');
require_once('salesman_auth.php');
require_once('cart_price.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => []
];

// Require salesman token for this API
$authSalesman = salesman_require_auth($con);

// Optional filters
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = [];
if ($location !== '') {
    $locationEsc = mysqli_real_escape_string($con, $location);
    $where[] = "location = '".$locationEsc."'";
}
if ($search !== '') {
    $searchEsc = mysqli_real_escape_string($con, $search);
    $where[] = "(itemcode LIKE '%".$searchEsc."%' OR description LIKE '%".$searchEsc."%')";
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE '.implode(' AND ', $where);
}

$catalogPriceSel = indiadata_has_catalog_price_column($con) ? ', price' : '';
$query  = "SELECT id, itemcode, image, description, width, quantity, type, trn_date".$catalogPriceSel." ";
$query .= "FROM indiadata".$whereSql." ORDER BY itemcode ASC";

$result = mysqli_query($con, $query);

if ($result) {
    // Build base URL for images
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    while ($row = mysqli_fetch_assoc($result)) {
        $imagePath = $base.'/item_images/'.$row['image'];
        $row['image_url'] = $scheme.'://'.$host.$imagePath;
        $response['data'][] = $row;
    }
    $response['success'] = true;
    $response['message'] = 'Products fetched successfully.';
} else {
    $response['message'] = 'DB error: '.mysqli_error($con);
}

echo json_encode($response);
exit;

