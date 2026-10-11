<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use zfhassaan\Payfast\Http\Middleware\LogIncomingIpn;
use zfhassaan\Payfast\Http\Requests\IPNRequest;
use zfhassaan\Payfast\PayFast;
use zfhassaan\Payfast\Support\PayfastLogger;

/**
 * Built-in IPN controller. Inbound persistence happens in LogIncomingIpn
 * before this action runs, so rejected/invalid payloads are never lost.
 */
class IPNController extends Controller
{
    public function handle(IPNRequest $request): JsonResponse
    {
        PayfastLogger::info('IPN controller handling validated request', [
            'ipn_log_id' => $request->attributes->get(LogIncomingIpn::REQUEST_ATTR_LOG_ID),
            'ip' => $request->ip(),
        ]);

        /** @var PayFast $payfast */
        $payfast = app('payfast');

        return $payfast->handleIPN($request->all());
    }
}
