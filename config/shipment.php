<?php

return [
    'fedex' => [
        'url' => env('FEDEX_URL', 'https://apis-sandbox.fedex.com'),
        'client_id' => env('FEDEX_CLIENT_ID'),
        'client_secret' => env('FEDEX_CLIENT_SECRET'),
        'account_number' => env('FEDEX_ACCOUNT_NUMBER'),
    ],
];
