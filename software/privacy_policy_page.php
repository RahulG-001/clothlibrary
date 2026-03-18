<?php
$policy = [
    'screen' => [
        'title' => 'Privacy Policy',
        'subtitle' => 'Your Privacy Is Protected And Respected',
        'cta_text' => 'I Understand'
    ],
    'content' => [
        [
            'order' => '01',
            'title' => 'Information We Collect',
            'description' => 'We collect basic information such as your name, email address and contact details when you create an account or place an order.'
        ],
        [
            'order' => '02',
            'title' => 'How Your Data Is Used',
            'description' => 'Your information is used only for app operations and service quality.',
            'subsections' => [
                [
                    'title' => 'Primary Usage Areas',
                    'bullets' => [
                        'Processing fabric orders and managing inventory.',
                        'Improving product recommendations.'
                    ]
                ]
            ]
        ],
        [
            'order' => '03',
            'title' => 'Data Protection & Security',
            'description' => 'All user information is protected using modern security standards and encrypted communication. We take necessary technical and organizational measures.'
        ],
        [
            'order' => '04',
            'title' => 'Your Privacy Controls',
            'items' => [
                [
                    'title' => 'Account Management',
                    'description' => 'All your information is protected using modern security.'
                ],
                [
                    'title' => 'Account Deletion',
                    'description' => 'You can request account deletion and removal of your stored data whenever needed.'
                ]
            ]
        ],
        [
            'order' => '05',
            'title' => 'Communication Safety',
            'cards' => [
                [
                    'title' => 'Order Notifications',
                    'description' => 'Users receive updates about order status and confirmations.'
                ],
                [
                    'title' => 'Support Assistance',
                    'description' => 'Our support team may contact you regarding order issues.'
                ]
            ]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($policy['screen']['title']); ?></title>
  <style>
    :root { --bg:#f5f6f8; --card:#fff; --text:#141414; --muted:#6f6f6f; --primary:#0f1b3f; --line:#d6d8df; --soft:#f2f4f8; }
    * { box-sizing: border-box; }
    body { margin:0; font-family:Arial,sans-serif; background:#4a4a4a; color:var(--text); padding:24px 8px; }
    .phone { width:100%; max-width:420px; margin:0 auto; background:var(--bg); border-radius:16px; padding:14px 14px 18px; min-height:86vh; }
    .topbar { display:flex; align-items:center; justify-content:center; position:relative; padding:6px 0 12px; }
    .back { position:absolute; left:0; width:30px; height:30px; border-radius:50%; border:1px solid #d7d9de; display:flex; align-items:center; justify-content:center; color:#333; text-decoration:none; background:#fff; font-size:14px; }
    .title { font-size:20px; margin:4px 0 0; text-align:center; }
    .hero { text-align:center; background:var(--card); border-radius:14px; padding:14px; margin-bottom:16px; border:1px solid #eceef3; }
    .hero-icon { width:84px; height:84px; border-radius:50%; margin:0 auto 8px; background:radial-gradient(circle at 30% 30%, #98c4ff, #0f1b3f); display:flex; align-items:center; justify-content:center; color:#fff; font-size:34px; }
    .hero h2 { margin:4px 0; font-size:22px; }
    .hero p { margin:0; color:var(--muted); font-size:13px; }
    .timeline { position:relative; padding-left:38px; }
    .timeline::before { content:""; position:absolute; left:14px; top:8px; bottom:6px; border-left:2px dashed var(--line); }
    .section { position:relative; margin:0 0 14px; }
    .order { position:absolute; left:-38px; top:2px; width:28px; height:28px; border-radius:50%; background:var(--primary); color:#fff; font-size:11px; display:flex; align-items:center; justify-content:center; font-weight:700; letter-spacing:.2px; }
    .section h3 { margin:2px 0 8px; font-size:16px; }
    .desc,.box,.small-card { background:var(--soft); border-radius:10px; padding:10px 12px; color:#525252; font-size:13px; line-height:1.45; border:1px solid #e8ebf2; }
    .box { margin-top:8px; }
    .box strong { color:#1f1f1f; display:block; margin-bottom:6px; }
    .bullets { margin:0; padding-left:16px; }
    .bullets li { margin-bottom:4px; }
    .item-list { display:grid; gap:8px; margin-top:8px; }
    .item-row { display:flex; gap:8px; align-items:flex-start; }
    .check { width:18px; height:18px; border:2px solid var(--primary); border-radius:50%; margin-top:2px; position:relative; flex:0 0 18px; }
    .check::after { content:""; position:absolute; width:7px; height:3px; border-left:2px solid var(--primary); border-bottom:2px solid var(--primary); transform:rotate(-45deg); left:3px; top:4px; }
    .cards { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:8px; }
    .small-card { min-height:88px; }
    .small-card strong { display:block; color:#1f1f1f; margin-bottom:4px; font-size:13px; }
    .cta { width:100%; border:0; margin-top:16px; border-radius:10px; padding:12px; color:#fff; background:var(--primary); font-size:16px; cursor:pointer; }
  </style>
</head>
<body>
  <main class="phone">
    <div class="topbar">
      <a class="back" href="javascript:history.back()">&#x2039;</a>
      <div class="title"><?php echo htmlspecialchars($policy['screen']['title']); ?></div>
    </div>

    <section class="hero">
      <div class="hero-icon">&#128274;</div>
      <h2><?php echo htmlspecialchars($policy['screen']['title']); ?></h2>
      <p><?php echo htmlspecialchars($policy['screen']['subtitle']); ?></p>
    </section>

    <section class="timeline">
      <?php foreach ($policy['content'] as $section) { ?>
        <article class="section">
          <div class="order"><?php echo htmlspecialchars($section['order']); ?></div>
          <h3><?php echo htmlspecialchars($section['title']); ?></h3>

          <?php if (!empty($section['description'])) { ?>
            <div class="desc"><?php echo htmlspecialchars($section['description']); ?></div>
          <?php } ?>

          <?php if (!empty($section['subsections']) && is_array($section['subsections'])) { ?>
            <?php foreach ($section['subsections'] as $sub) { ?>
              <div class="box">
                <strong><?php echo htmlspecialchars($sub['title']); ?></strong>
                <?php if (!empty($sub['bullets']) && is_array($sub['bullets'])) { ?>
                  <ul class="bullets">
                    <?php foreach ($sub['bullets'] as $bullet) { ?>
                      <li><?php echo htmlspecialchars($bullet); ?></li>
                    <?php } ?>
                  </ul>
                <?php } ?>
              </div>
            <?php } ?>
          <?php } ?>

          <?php if (!empty($section['items']) && is_array($section['items'])) { ?>
            <div class="item-list">
              <?php foreach ($section['items'] as $item) { ?>
                <div class="item-row">
                  <span class="check"></span>
                  <div class="box">
                    <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                    <?php echo htmlspecialchars($item['description']); ?>
                  </div>
                </div>
              <?php } ?>
            </div>
          <?php } ?>

          <?php if (!empty($section['cards']) && is_array($section['cards'])) { ?>
            <div class="cards">
              <?php foreach ($section['cards'] as $card) { ?>
                <div class="small-card">
                  <strong><?php echo htmlspecialchars($card['title']); ?></strong>
                  <?php echo htmlspecialchars($card['description']); ?>
                </div>
              <?php } ?>
            </div>
          <?php } ?>
        </article>
      <?php } ?>
    </section>

  </main>
</body>
</html>

