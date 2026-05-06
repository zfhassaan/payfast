<?php

use Illuminate\Support\Facades\Route;
use zfhassaan\Payfast\Http\Controllers\IPNController;

/*
|--------------------------------------------------------------------------
| PayFast Package API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the PayFastServiceProvider with the 'api'
| middleware group. CSRF verification is automatically excluded.
|
*/

Route::post('payfast/ipn', [IPNController::class, 'handle'])
    ->name('payfast.ipn.handle');
