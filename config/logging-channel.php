<?php

/*
|--------------------------------------------------------------------------
| PayFast logging channel (optional)
|--------------------------------------------------------------------------
|
| Publish with:
|   php artisan vendor:publish --tag=payfast-logging
|
| Then merge the returned array into config/logging.php under 'channels':
|
|   'channels' => [
|       // ...
|       'payfast' => require __DIR__.'/payfast-logging-channel.php',
|   ],
|
| Or copy the array body into config/logging.php manually.
|
*/

return [
    'driver' => 'daily',
    'path' => storage_path('logs/payfast.log'),
    'level' => env('PAYFAST_LOG_LEVEL', env('LOG_LEVEL', 'debug')),
    'days' => (int) env('PAYFAST_LOG_DAYS', 14),
    'replace_placeholders' => true,
];
