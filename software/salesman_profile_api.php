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

echo json_encode([
    'success' => true,
    'message' => 'Salesman fetch successfully',
    'data'    => $salesman
]);
exit;

