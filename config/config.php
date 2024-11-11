<?php

return [
    'max_execution_time' => env("MALL_EXPORT_MAX_EXEC_TIME", 3600),
    'import' => [],
    'export' => [
        'fileName' => 'Products_export',
        'appendDate' => true,
        'dateFormat' => '_Y-m-d',
    ],
];
