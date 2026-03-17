<?php
/**
 * Salesman token auth helper.
 *
 * Accepts token from:
 * - Authorization: Bearer <token>
 * - ?token=<token>
 * - POST token=<token>
 *
 * On failure: outputs JSON and exits.
 */

function _salesman_get_headers() {
    if (function_exists('getallheaders')) {
        return getallheaders();
    }
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
    return $headers;
}

function salesman_require_auth($con) {
    $token = '';

    $headers = _salesman_get_headers();
    if (isset($headers['Authorization'])) {
        $auth = trim($headers['Authorization']);
        if (stripos($auth, 'Bearer ') === 0) {
            $token = trim(substr($auth, 7));
        }
    } elseif (isset($headers['authorization'])) {
        $auth = trim($headers['authorization']);
        if (stripos($auth, 'Bearer ') === 0) {
            $token = trim(substr($auth, 7));
        }
    }

    if ($token === '' && isset($_GET['token'])) {
        $token = trim((string)$_GET['token']);
    }
    if ($token === '' && isset($_POST['token'])) {
        $token = trim((string)$_POST['token']);
    }

    if ($token === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Salesman not found',
            'data'    => null
        ]);
        exit;
    }

    $tokenEsc = mysqli_real_escape_string($con, $token);
    $sql = "SELECT t.salesman_id
            FROM salesman_token t
            WHERE t.token = '".$tokenEsc."'
              AND t.expires_at > NOW()
            LIMIT 1";
    $res = mysqli_query($con, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Salesman not found',
            'data'    => null
        ]);
        exit;
    }

    $row = mysqli_fetch_assoc($res);
    $sid = (int)$row['salesman_id'];

    $sres = mysqli_query($con, "SELECT id, first_name, last_name, phone FROM salesman WHERE id = '".$sid."' LIMIT 1");
    if (!$sres || mysqli_num_rows($sres) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Salesman not found',
            'data'    => null
        ]);
        exit;
    }

    return mysqli_fetch_assoc($sres);
}

