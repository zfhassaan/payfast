<?php

use Illuminate\Support\Facades\Route;
use zfhassaan\Payfast\Http\Controllers\IPNController;

/*
|--------------------------------------------------------------------------
| PayFast Package API Routes
|--------------------------------------------------------------------------
|
| Middleware / prefix are driven by config/payfast.php so apps can customize
| after: php artisan vendor:publish --tag=payfast-config
|
*/

$middleware = config('payfast.routes.middleware', ['api', 'payfast.ipn']);
$prefix = (string) config('payfast.routes.prefix', '');

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function (): void {
        Route::post('payfast/ipn', [IPNController::class, 'handle'])
            ->name('payfast.ipn.handle');
    });
