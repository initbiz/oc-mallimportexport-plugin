<?php

return [
    'import' => [],
    'export' => [
        'fileName' => 'Products_export',
        'appendDate' => true,
        'dateFormat' => '_Y-m-d',
    ],
    'export_queue' => [
        'enabled' => env("MALL_EXPORT_QUEUE_ENABLED", false),
    ],
];
