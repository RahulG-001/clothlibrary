<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');
require_once('salesman_auth.php');

$salesman = salesman_require_auth($con);

// Accept JSON body or form-data
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

$new_password = isset($input['new_password']) ? trim((string)$input['new_password']) : '';
if ($new_password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'new_password is required.',
    ]);
    exit;
}

$pwEsc = mysqli_real_escape_string($con, $new_password);
$sid = (int)$salesman['id'];

$ok = mysqli_query($con, "UPDATE salesman SET password = '".$pwEsc."' WHERE id = '".$sid."' LIMIT 1");
if (!$ok) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update password.',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Password updated.',
]);
exit;

