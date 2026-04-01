<?php
require_once __DIR__ . '/bootstrap_timezone.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$response = [
    'success' => true,
    'message' => 'Privacy policy fetched successfully.',
    'data' => [
        'screen' => [
            'title' => 'Privacy Policy',
            'subtitle' => 'Your privacy is protected and respected',
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
        ],
        'meta' => [
            'version' => '1.0',
            'updated_at' => date('Y-m-d')
        ]
    ]
];

echo json_encode($response);
exit;

