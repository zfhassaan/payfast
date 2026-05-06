<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use zfhassaan\Payfast\PayFast;

/**
 * Built-in IPN (Instant Payment Notification) Controller.
 *
 * This controller is auto-registered by the package and handles
 * incoming IPN webhook requests from PayFast. The route is registered
 * at POST /api/payfast/ipn by default.
 *
 * For custom IPN handling logic, you may override this by binding
 * your own controller to the payfast.ipn.handle route.
 *
 * @package zfhassaan\Payfast\Http\Controllers
 */
class IPNController extends Controller
{
    /**
     * Handle incoming IPN webhook from PayFast.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        Log::channel('payfast')->info('IPN Received', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'data' => $request->all(),
        ]);

        /** @var PayFast $payfast */
        $payfast = app('payfast');

        return $payfast->handleIPN($request->all());
    }
}
