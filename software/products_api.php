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
    'data'    => [],
    'pagination' => [
        'page'       => 1,
        'per_page'   => 20,
        'total'      => 0,
        'total_pages'=> 0
    ]
];

// Require salesman token for this API
$authSalesman = salesman_require_auth($con);

// Optional filters
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$search   = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination params
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage  = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
if ($page < 1) {
    $page = 1;
}
if ($perPage < 1) {
    $perPage = 50;
}
if ($perPage > 200) {
    $perPage = 200;
}

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

$countQuery  = "SELECT COUNT(*) as total FROM indiadata".$whereSql;
$countResult = mysqli_query($con, $countQuery);
$total       = 0;
if ($countResult && mysqli_num_rows($countResult) === 1) {
    $countRow = mysqli_fetch_assoc($countResult);
    $total    = (int)$countRow['total'];
} elseif (!$countResult) {
    $response['message'] = 'DB error: '.mysqli_error($con);
    echo json_encode($response);
    exit;
}

$offset = ($page - 1) * $perPage;

$query  = "SELECT id, itemcode, image, description, width, quantity, type, trn_date ";
$query .= "FROM indiadata".$whereSql." ORDER BY itemcode ASC LIMIT ".$perPage." OFFSET ".$offset;

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
    $response['pagination']['page']        = $page;
    $response['pagination']['per_page']    = $perPage;
    $response['pagination']['total']       = $total;
    $response['pagination']['total_pages'] = $total > 0 ? ceil($total / $perPage) : 0;
} else {
    $response['message'] = 'DB error: '.mysqli_error($con);
}

echo json_encode($response);
exit;

