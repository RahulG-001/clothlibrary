<?php
// Simple viewer for the last QR/API failures.
// This helps you understand what was scanned and why the lookup failed.

$logFile = __DIR__ . '/admin/qr_api_errors.log';
$max = isset($_GET['max']) ? (int)$_GET['max'] : 100;
if ($max < 1) { $max = 1; }
if ($max > 500) { $max = 500; }

$buffer = [];
if (is_file($logFile) && is_readable($logFile)) {
    $fh = @fopen($logFile, 'r');
    if ($fh) {
        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $buffer[] = $line;
            if (count($buffer) > $max) {
                array_shift($buffer);
            }
        }
        @fclose($fh);
    }
}

function esc($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QR/API Error Viewer</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 16px; background: #f6f7fb; color: #111827; }
    .wrap { max-width: 980px; margin: 0 auto; }
    h1 { font-size: 18px; margin: 0 0 12px; }
    .meta { color: #6b7280; font-size: 12px; margin-bottom: 14px; }
    pre { background: #111827; color: #e5e7eb; padding: 12px; border-radius: 10px; overflow: auto; }
    .item { background: #ffffff; border: 1px solid #e5e7eb; padding: 12px; border-radius: 14px; margin-bottom: 12px; }
    .k { font-size: 12px; color: #6b7280; margin-top: 8px; }
    .v { font-size: 13px; margin-top: 2px; word-break: break-word; }
  </style>
</head>
<body>
<div class="wrap">
  <h1>QR/API Error Viewer</h1>
  <div class="meta">
    Log file: <code><?php echo esc($logFile); ?></code><br>
    Showing last <?php echo esc($max); ?> entries.
  </div>

  <?php if (empty($buffer)) { ?>
    <div class="item">
      <div class="v">No QR/API errors logged yet.</div>
    </div>
  <?php } else { ?>
    <?php foreach (array_reverse($buffer) as $line) {
        $obj = json_decode($line, true);
        $time = isset($obj['time']) ? $obj['time'] : '';
        $message = isset($obj['message']) ? $obj['message'] : (isset($obj['response']['message']) ? $obj['response']['message'] : '');
        $debug = $obj['response']['debug'] ?? null;
    ?>
      <div class="item">
        <div class="k">Time</div>
        <div class="v"><?php echo esc($time); ?></div>
        <div class="k">Message</div>
        <div class="v"><?php echo esc($message); ?></div>
        <?php if ($debug !== null) { ?>
          <div class="k">Debug</div>
          <pre><?php echo esc(json_encode($debug, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)); ?></pre>
        <?php } ?>
      </div>
    <?php } ?>
  <?php } ?>
</div>
</body>
</html>

