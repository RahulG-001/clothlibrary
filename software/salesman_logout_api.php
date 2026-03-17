<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');

$token = '';
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
}

if (isset($headers['Authorization'])) {
    $auth = trim($headers['Authorization']);
    if (stripos($auth, 'Bearer ') === 0) {
        $token = trim(substr($auth, 7));
    }
}
if ($token === '' && isset($_POST['token'])) {
    $token = trim((string)$_POST['token']);
}
if ($token === '' && isset($_GET['token'])) {
    $token = trim((string)$_GET['token']);
}

if ($token === '') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Token required.',
        'data'    => null
    ]);
    exit;
}

$tokenEsc = mysqli_real_escape_string($con, $token);
mysqli_query($con, "DELETE FROM salesman_token WHERE token = '".$tokenEsc."' LIMIT 1");

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Logged out.',
    'data'    => null
]);
exit;