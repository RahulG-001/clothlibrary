<?php
/**
 * Whether `salesman.profile_image` exists (cached per request).
 */
function salesman_table_has_profile_image($con) {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $r = mysqli_query($con, "SHOW COLUMNS FROM `salesman` LIKE 'profile_image'");
    $cache = ($r && mysqli_num_rows($r) > 0);
    return $cache;
}

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
    // Prevent mysqli from throwing exceptions (which causes HTTP 500 on some servers)
    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

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

    // If token table is missing on live, return JSON instead of crashing
    $tblRes = mysqli_query($con, "SHOW TABLES LIKE 'salesman_token'");
    if (!$tblRes || mysqli_num_rows($tblRes) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Auth table missing: salesman_token',
            'data'    => null
        ]);
        exit;
    }

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
        ]);
        exit;
    }

    $row = mysqli_fetch_assoc($res);
    $sid = (int)$row['salesman_id'];

    $profileSel = salesman_table_has_profile_image($con) ? ', profile_image' : '';
    $sres = mysqli_query($con, "SELECT id, first_name, last_name, phone, password".$profileSel." FROM salesman WHERE id = '".$sid."' LIMIT 1");
    if (!$sres || mysqli_num_rows($sres) === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Salesman not found',
        ]);
        exit;
    }

    $row = mysqli_fetch_assoc($sres);
    if (!array_key_exists('profile_image', $row)) {
        $row['profile_image'] = null;
    }
    return $row;
}

