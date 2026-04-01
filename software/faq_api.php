<?php
require_once __DIR__ . '/bootstrap_timezone.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$response = [
    'success' => true,
    'message' => 'FAQ fetched successfully.',
    'data' => [
        'screen' => [
            'title' => 'Frequently Asked Questions:'
        ],
        'faqs' => [
            [
                'id' => 1,
                'question' => 'How is fabric measured?',
                'answer' => 'Fabric is measured in meters. Product cards show available quantity and width so you can choose the correct material for your requirement.',
                'is_open' => false
            ],
            [
                'id' => 2,
                'question' => 'How do I know if fabric is available?',
                'answer' => 'You can check fabric availability directly within the app while browsing materials. Each fabric listed in the application displays its current stock status, allowing you to see whether the material is available before placing an order. When you open a fabric item, the app provides information about its availability along with other details such as specifications, colors, and material composition. This helps users make informed decisions before selecting the required quantity.',
                'is_open' => false
            ],
            [
                'id' => 3,
                'question' => 'How do I place an order?',
                'answer' => 'Open products, add items to cart, set quantity/meters, and then place your order from the cart checkout screen.',
                'is_open' => false
            ],
            [
                'id' => 4,
                'question' => 'What do fabric specifications mean?',
                'answer' => 'Specifications include details like width, material type, quality, and color. These help you compare products and select the right fabric.',
                'is_open' => false
            ],
            [
                'id' => 5,
                'question' => 'Can I order custom quantities?',
                'answer' => 'Yes. You can order based on available stock. For special requirements or large quantities, please contact support.',
                'is_open' => false
            ]
        ],
        'meta' => [
            'version' => '1.0',
            'updated_at' => date('Y-m-d')
        ]
    ]
];

$view = isset($_GET['view']) ? trim((string)$_GET['view']) : '';
$format = isset($_GET['format']) ? trim((string)$_GET['format']) : '';
$isMobileHtml = ($view === 'mobile' || $format === 'html');

if (!$isMobileHtml) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$data = $response['data'];
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($data['screen']['title']); ?></title>
  <style>
    :root { --bg:#f3f4f6; --card:#eef0f3; --text:#111827; --muted:#6b7280; --line:#e0e3e8; }
    * { box-sizing: border-box; }
    body { margin:0; font-family:Arial,sans-serif; background:#4a4a4a; color:var(--text); padding:24px 8px; }
    .phone { width:100%; max-width:420px; margin:0 auto; background:var(--bg); border-radius:24px; padding:18px 16px 24px; min-height:86vh; }
    .topbar { display:flex; align-items:center; justify-content:center; position:relative; padding:6px 0 10px; }
    .back { position:absolute; left:0; width:44px; height:44px; border-radius:50%; border:1px solid #d7d9de; display:flex; align-items:center; justify-content:center; color:#333; text-decoration:none; background:transparent; font-size:28px; line-height:1; }
    .title { font-size:40px; margin:6px 0 14px; text-align:center; font-weight:700; line-height:1.05; letter-spacing: -0.5px; }
    .faq-list { display:grid; gap:14px; }
    .faq-item { background:var(--card); border-radius:14px; border:1px solid #e4e7ec; overflow:hidden; }
    .faq-q {
      width:100%; border:0; background:transparent; text-align:left; padding:18px 16px; font-size:18px;
      color:#374151; display:flex; justify-content:space-between; align-items:center; font-weight:600; cursor:pointer;
    }
    .faq-q .chev { font-size:18px; color:#6b7280; }
    .faq-a { padding:0 16px 16px; color:#6b7280; font-size:15px; line-height:1.65; }
    .faq-item.closed .faq-a { display:none; }
    .faq-item.closed .faq-q .chev { transform:rotate(0deg); }
    .faq-item.open .faq-q .chev { transform:rotate(180deg); }
    .faq-item.open .faq-q { border-bottom:1px solid var(--line); }
  </style>
</head>
<body>
  <main class="phone">
    <div class="title"><?php echo nl2br(htmlspecialchars($data['screen']['title'])); ?></div>

    <section class="faq-list">
      <?php foreach ($data['faqs'] as $faq) { ?>
        <article class="faq-item <?php echo !empty($faq['is_open']) ? 'open' : 'closed'; ?>">
          <button class="faq-q" type="button">
            <span><?php echo htmlspecialchars($faq['question']); ?></span>
            <span class="chev">&#8964;</span>
          </button>
          <div class="faq-a"><?php echo htmlspecialchars($faq['answer']); ?></div>
        </article>
      <?php } ?>
    </section>
  </main>
  <script>
    (function () {
      var items = document.querySelectorAll('.faq-item');
      for (var i = 0; i < items.length; i++) {
        (function (item) {
          var btn = item.querySelector('.faq-q');
          if (!btn) return;
          btn.addEventListener('click', function () {
            if (item.classList.contains('open')) {
              item.classList.remove('open');
              item.classList.add('closed');
            } else {
              item.classList.remove('closed');
              item.classList.add('open');
            }
          });
        })(items[i]);
      }
    })();
  </script>
</body>
</html>

