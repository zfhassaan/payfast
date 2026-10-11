<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Throwable;
use zfhassaan\Payfast\Models\IPNLog;
use zfhassaan\Payfast\Repositories\Contracts\IPNLogRepositoryInterface;
use zfhassaan\Payfast\Support\PayfastLogger;

/**
 * FIRST middleware on the IPN route — persists + file-logs every request
 * before signature checks / FormRequest validation can reject it.
 */
class LogIncomingIpn
{
    public const REQUEST_ATTR_LOG_ID = 'payfast_ipn_log_id';

    public function __construct(
        private readonly IPNLogRepositoryInterface $ipnLogRepository
    ) {
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $payload = $request->all();
        $rawBody = $request->getContent();
        $headers = $this->safeHeaders($request);

        PayfastLogger::info('IPN inbound request', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'content_type' => $request->header('Content-Type'),
            'payload' => $payload,
            'raw_body_length' => strlen($rawBody),
        ]);

        $transactionId = $payload['transaction_id'] ?? $payload['TRANSACTION_ID'] ?? null;
        $orderNo = $payload['order_no'] ?? $payload['ORDER_NO'] ?? $payload['basket_id'] ?? $payload['BASKET_ID'] ?? null;
        $status = (string) ($payload['status'] ?? $payload['STATUS'] ?? $payload['code'] ?? $payload['CODE'] ?? 'received');
        $amount = (float) ($payload['amount'] ?? $payload['AMOUNT'] ?? $payload['txnamt'] ?? $payload['TXNAMT'] ?? 0);
        $currency = (string) ($payload['currency'] ?? $payload['CURRENCY'] ?? $payload['currency_code'] ?? $payload['CURRENCY_CODE'] ?? 'PKR');

        try {
            $log = $this->ipnLogRepository->create([
                'order_no' => $orderNo !== null ? (string) $orderNo : null,
                'transaction_id' => $transactionId !== null ? (string) $transactionId : ('pending-' . uniqid('', true)),
                'status' => $status !== '' ? $status : 'received',
                'processing_status' => IPNLog::STATUS_RECEIVED,
                'amount' => $amount,
                'currency' => $currency !== '' ? substr($currency, 0, 3) : 'PKR',
                'details' => [
                    'phase' => 'received',
                    'query' => $request->query(),
                ],
                'raw_payload' => $rawBody !== '' ? $rawBody : (json_encode($payload) ?: '{}'),
                'request_headers' => $headers,
                'payload_hash' => hash(
                    'sha256',
                    $rawBody !== '' ? $rawBody : (json_encode($payload) ?: '')
                ),
                'ip_address' => $request->ip(),
                'received_at' => now(),
            ]);

            $request->attributes->set(self::REQUEST_ATTR_LOG_ID, $log->id);
        } catch (Throwable $e) {
            // Never block the webhook because logging failed.
            PayfastLogger::error('Failed to persist inbound IPN log', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
        }

        return $next($request);
    }

    /**
     * @return array<string, mixed>
     */
    private function safeHeaders(Request $request): array
    {
        if (!config('payfast.request_logging.log_headers', true)) {
            return [];
        }

        $headers = $request->headers->all();
        $redact = array_map('strtolower', (array) config('payfast.request_logging.redact_headers', [
            'authorization',
            'cookie',
            'x-payfast-signature',
        ]));

        foreach ($headers as $name => $value) {
            if (in_array(strtolower((string) $name), $redact, true)) {
                $headers[$name] = ['[redacted]'];
            }
        }

        return $headers;
    }
}
