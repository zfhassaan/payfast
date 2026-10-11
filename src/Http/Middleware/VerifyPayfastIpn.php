<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use zfhassaan\Payfast\Helpers\Utility;
use zfhassaan\Payfast\Services\Contracts\IPNServiceInterface;
use zfhassaan\Payfast\Support\PayfastLogger;

/**
 * Optional IP allowlist + HMAC signature verification for PayFast IPN webhooks.
 *
 * Runs AFTER LogIncomingIpn so rejected requests are still persisted.
 */
class VerifyPayfastIpn
{
    public function __construct(
        private readonly IPNServiceInterface $ipnService
    ) {
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $allowedIps = config('payfast.ipn.allowed_ips', []);
        $secret = (string) config('payfast.ipn.secret', '');
        $logId = $request->attributes->get(LogIncomingIpn::REQUEST_ATTR_LOG_ID);

        if ($allowedIps !== [] && !$this->ipIsAllowed($request->ip(), $allowedIps)) {
            $this->ipnService->markRejected(
                is_numeric($logId) ? (int) $logId : null,
                'IP not allowlisted',
                ['ip' => $request->ip()]
            );
            PayfastLogger::warning('IPN rejected: IP not allowlisted', ['ip' => $request->ip(), 'ipn_log_id' => $logId]);

            return Utility::returnError(
                ['ip' => $request->ip(), 'ipn_log_id' => $logId],
                'IPN request IP is not allowed',
                'IPN_IP_FORBIDDEN',
                Response::HTTP_FORBIDDEN
            );
        }

        if ($secret !== '' && !$this->signatureIsValid($request, $secret)) {
            $this->ipnService->markRejected(
                is_numeric($logId) ? (int) $logId : null,
                'Invalid signature'
            );
            PayfastLogger::warning('IPN rejected: invalid signature', ['ip' => $request->ip(), 'ipn_log_id' => $logId]);

            return Utility::returnError(
                ['ipn_log_id' => $logId],
                'Invalid IPN signature',
                'IPN_SIGNATURE_INVALID',
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $allowedIps
     */
    private function ipIsAllowed(?string $ip, array $allowedIps): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }

        foreach ($allowedIps as $allowed) {
            if ($allowed === '*' || $allowed === $ip) {
                return true;
            }

            if (str_contains($allowed, '/') && $this->ipInCidr($ip, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr, 2) + [null, null];
        if ($subnet === null || $mask === null) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $mask = (int) $mask;

        if ($ipLong === false || $subnetLong === false || $mask < 0 || $mask > 32) {
            return false;
        }

        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    private function signatureIsValid(Request $request, string $secret): bool
    {
        $headerName = (string) config('payfast.ipn.signature_header', 'X-PayFast-Signature');
        $bodyKey = (string) config('payfast.ipn.signature_body_key', 'signature');

        $provided = $request->header($headerName)
            ?? $request->input($bodyKey)
            ?? '';

        $provided = is_string($provided) ? trim($provided) : '';
        if ($provided === '') {
            return false;
        }

        if (str_contains($provided, '=')) {
            $provided = substr($provided, (int) strpos($provided, '=') + 1);
        }

        $rawBody = $request->getContent();
        if ($rawBody === '') {
            $rawBody = json_encode($request->all()) ?: '';
        }
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals(strtolower($expected), strtolower($provided));
    }
}
