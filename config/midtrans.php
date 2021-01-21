<?php

return [
    'env' => env('MIDTRANS_ENV', ''),
    'serverKey' => env('MIDTRANS_SERVER_KEY', ''),
    'clientKey' => env('MIDTRANS_CLIENT_KEY', ''),
    'isSanitized' => env('MIDTRANS_SANITIZE', true),
    'is3ds' => env('MIDTRANS_3DS', false),
    'curlOptions' => [
    ],
];
