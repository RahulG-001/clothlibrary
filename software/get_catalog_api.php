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

$authSalesman = salesman_require_auth($con);
$salesman_id = (int)$authSalesman['id'];
if ($salesman_id <= 0) {
    $response['message'] = 'Salesman not found';
    echo json_encode($response);
    exit;
}

$catalog_id = isset($_GET['catalog_id']) ? (int)$_GET['catalog_id'] : 0;

$where = "";
if ($catalog_id > 0) {
    $where = " WHERE c.id = '".$catalog_id."' ";
}

$catalogRes = mysqli_query(
    $con,
    "SELECT c.id, c.title, c.file_type, c.created_at, c.updated_at
     FROM catalog c
     ".$where."
     ORDER BY c.id DESC"
);

if (!$catalogRes || mysqli_num_rows($catalogRes) === 0) {
    $response['message'] = 'Catalog not found.';
    $response['data'] = [];
    echo json_encode($response);
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$uploadsUrlBase = $scheme.'://'.$host.$base.'/catalog_uploads/';

$catalogs = [];
while ($c = mysqli_fetch_assoc($catalogRes)) {
    $cid = (int)$c['id'];

    $filesRes = mysqli_query(
        $con,
        "SELECT id, file_name, original_name, mime_type, created_at
         FROM catalog_file
         WHERE catalog_id = '".$cid."'
         ORDER BY id DESC"
    );

    $files = [];
    if ($filesRes) {
        while ($f = mysqli_fetch_assoc($filesRes)) {
            $fn = $f['file_name'];
            $files[] = [
                'id'            => (int)$f['id'],
                'file_name'     => $fn,
                'original_name' => $f['original_name'],
                'mime_type'     => $f['mime_type'],
                'url'           => $fn !== '' ? $uploadsUrlBase.rawurlencode($fn) : '',
                'created_at'    => $f['created_at'],
            ];
        }
    }

    $catalogs[] = [
        'id'         => $cid,
        'title'      => $c['title'],
        'file_type'  => $c['file_type'],
        'created_at' => $c['created_at'],
        'updated_at' => $c['updated_at'],
        'files'      => $files
    ];
}

$response['success'] = true;
$response['message'] = 'Catalog fetched successfully.';
$response['data'] = [
    'catalogs' => $catalogs
];

echo json_encode($response);
exit;

?>

