<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');
require_once('salesman_auth.php');

$salesman = salesman_require_auth($con);

$data = $salesman;
if (!empty($data['profile_image'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $data['profile_image_url'] = $scheme.'://'.$host.$base.'/salesman_profile_images/'.$data['profile_image'];
}

echo json_encode([
    'success' => true,
    'message' => 'Salesman fetch successfully',
    'data'    => $data
]);
exit;

