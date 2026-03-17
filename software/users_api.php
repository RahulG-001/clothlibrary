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

$response = [
    'success' => false,
    'message' => '',
    'data'    => [],
    'pagination' => [
        'page'        => 1,
        'per_page'    => 20,
        'total'       => 0,
        'total_pages' => 0
    ]
];

// Require salesman token
$authSalesman = salesman_require_auth($con);

// Pagination
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
if ($page < 1) { $page = 1; }
if ($perPage < 1) { $perPage = 50; }
if ($perPage > 200) { $perPage = 200; }
$offset = ($page - 1) * $perPage;

// Optional search by userid
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$whereSql = '';
if ($q !== '') {
    $qEsc = mysqli_real_escape_string($con, $q);
    $whereSql = " WHERE userid LIKE '%".$qEsc."%'";
}

// Count
$countRes = mysqli_query($con, "SELECT COUNT(*) AS total FROM users".$whereSql);
if (!$countRes || mysqli_num_rows($countRes) === 0) {
    $response['message'] = 'DB error: '.mysqli_error($con);
    echo json_encode($response);
    exit;
}
$total = (int)mysqli_fetch_assoc($countRes)['total'];

// List (never return password)
$sql = "SELECT userid FROM users".$whereSql." ORDER BY userid ASC LIMIT ".$perPage." OFFSET ".$offset;
$res = mysqli_query($con, $sql);
if (!$res) {
    $response['message'] = 'DB error: '.mysqli_error($con);
    echo json_encode($response);
    exit;
}

while ($row = mysqli_fetch_assoc($res)) {
    $response['data'][] = $row;
}

$response['success'] = true;
$response['message'] = 'Users fetched successfully.';
$response['pagination']['page'] = $page;
$response['pagination']['per_page'] = $perPage;
$response['pagination']['total'] = $total;
$response['pagination']['total_pages'] = $total > 0 ? ceil($total / $perPage) : 0;

echo json_encode($response);
exit;

