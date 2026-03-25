<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');
require_once(__DIR__.'/salesman_auth.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

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

$phone    = isset($input['phone']) ? trim((string)$input['phone']) : '';
$password = isset($input['password']) ? trim((string)$input['password']) : '';

if ($phone === '' || $password === '') {
    $response['message'] = 'Phone and password are required.';
    echo json_encode($response);
    exit;
}

$phoneEsc    = mysqli_real_escape_string($con, $phone);
$passwordEsc = mysqli_real_escape_string($con, $password);

$profileSel = salesman_table_has_profile_image($con) ? ', profile_image' : '';
$query  = "SELECT id, first_name, last_name, phone".$profileSel." FROM salesman ";
$query .= "WHERE phone = '".$phoneEsc."' AND password = '".$passwordEsc."' ";
$query .= "LIMIT 1";

$result = mysqli_query($con, $query);

if ($result && mysqli_num_rows($result) === 1) {
    $salesman = mysqli_fetch_assoc($result);
    if (!array_key_exists('profile_image', $salesman)) {
        $salesman['profile_image'] = null;
    }
    $salesmanId = (int)$salesman['id'];

    // Create token (30 days)
    $token = bin2hex(random_bytes(32));
    $tokenEsc = mysqli_real_escape_string($con, $token);
    $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
    $expiresEsc = mysqli_real_escape_string($con, $expiresAt);

    // Cleanup expired tokens for this salesman
    mysqli_query($con, "DELETE FROM salesman_token WHERE salesman_id = '".$salesmanId."' AND expires_at <= NOW()");
    mysqli_query($con, "INSERT INTO salesman_token (token, salesman_id, expires_at) VALUES ('".$tokenEsc."', '".$salesmanId."', '".$expiresEsc."')");

    $response['success'] = true;
    $response['message'] = 'Login successful.';
    $response['data'] = [
        'status' => 'success',
        'salesman_id' => $salesmanId,
        'token' => $token,
        'expires_at' => $expiresAt,
        'salesman' => $salesman
    ];
} else {
    $response['message'] = 'Invalid phone or password.';
}

echo json_encode($response);
exit;

