<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

use Illuminate\Support\Facades\Event;
use Throwable;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Http\Middleware\LogIncomingIpn;
use zfhassaan\Payfast\Models\IPNLog;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\IPNLogRepositoryInterface;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;
use zfhassaan\Payfast\Services\Contracts\IPNServiceInterface;
use zfhassaan\Payfast\Support\PayfastLogger;

class IPNService implements IPNServiceInterface
{
    public function __construct(
        private readonly IPNLogRepositoryInterface $ipnLogRepository,
        private readonly ProcessPaymentRepositoryInterface $paymentRepository
    ) {
    }

    /**
     * Process IPN notification from PayFast.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function processIPN(array $data): array
    {
        $log = $this->resolveInboundLog($data);

        try {
            if (!$this->validateIPN($data)) {
                $this->markLog($log, IPNLog::STATUS_REJECTED, [
                    'phase' => 'validation_failed',
                    'reason' => 'Missing transaction_id and order/basket id',
                ]);

                return [
                    'status' => false,
                    'message' => 'Invalid IPN data',
                    'code' => 'INVALID_IPN',
                    'data' => ['ipn_log_id' => $log?->id],
                ];
            }

            $transactionId = $this->extractTransactionId($data);
            $orderNo = $this->extractOrderNo($data);
            $status = $this->extractStatus($data);
            $amount = $this->extractAmount($data);
            $currency = $this->extractCurrency($data);
            $idempotencyKey = $this->buildIdempotencyKey($data);

            if ($log) {
                $this->ipnLogRepository->update($log, [
                    'transaction_id' => $transactionId ?: $log->transaction_id,
                    'order_no' => $orderNo,
                    'status' => $status,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payload_hash' => hash('sha256', json_encode($this->normalizePayload($data)) ?: ''),
                    'details' => array_merge($log->details ?? [], [
                        'phase' => 'processing',
                        'normalized' => $this->normalizePayload($data),
                    ]),
                ]);
                $log->refresh();
            }

            if (config('payfast.idempotency.enabled', true) && $idempotencyKey !== '') {
                $duplicate = $this->findDuplicate($idempotencyKey, $transactionId, $log?->id);
                if ($duplicate) {
                    if ($log) {
                        $this->ipnLogRepository->update($log, [
                            'processing_status' => IPNLog::STATUS_DUPLICATE,
                            'idempotency_key' => null,
                            'duplicate_of' => $duplicate->id,
                            'details' => array_merge($log->details ?? [], [
                                'phase' => 'duplicate',
                                'original_ipn_log_id' => $duplicate->id,
                            ]),
                        ]);
                    }

                    PayfastLogger::info('IPN duplicate ignored (idempotent)', [
                        'ipn_log_id' => $log?->id,
                        'duplicate_of' => $duplicate->id,
                        'idempotency_key' => $idempotencyKey,
                    ]);

                    return [
                        'status' => true,
                        'message' => 'IPN already processed',
                        'code' => 'ALREADY_PROCESSED',
                        'data' => [
                            'ipn_log_id' => $log?->id,
                            'original_ipn_log_id' => $duplicate->id,
                            'transaction_id' => $transactionId,
                            'payment_updated' => false,
                        ],
                    ];
                }
            }

            $updateResult = $this->updatePaymentStatus($data);

            if ($log) {
                try {
                    $this->ipnLogRepository->update($log, [
                        'processing_status' => IPNLog::STATUS_PROCESSED,
                        'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                        'details' => array_merge($log->fresh()?->details ?? [], [
                            'phase' => 'processed',
                            'payment_update' => $updateResult,
                        ]),
                    ]);
                } catch (Throwable $e) {
                    // Unique idempotency_key race — treat as duplicate success.
                    if ($this->isUniqueViolation($e) && $idempotencyKey !== '') {
                        $original = $this->ipnLogRepository->findProcessedByIdempotencyKey($idempotencyKey);
                        $this->ipnLogRepository->update($log, [
                            'processing_status' => IPNLog::STATUS_DUPLICATE,
                            'idempotency_key' => null,
                            'duplicate_of' => $original?->id,
                        ]);

                        return [
                            'status' => true,
                            'message' => 'IPN already processed',
                            'code' => 'ALREADY_PROCESSED',
                            'data' => [
                                'ipn_log_id' => $log->id,
                                'original_ipn_log_id' => $original?->id,
                                'transaction_id' => $transactionId,
                                'payment_updated' => false,
                            ],
                        ];
                    }

                    throw $e;
                }
            }

            PayfastLogger::info('IPN Processed', [
                'ipn_log_id' => $log?->id,
                'transaction_id' => $transactionId,
                'order_no' => $orderNo,
                'status' => $status,
                'payment_updated' => $updateResult['updated'] ?? false,
            ]);

            return [
                'status' => true,
                'message' => 'IPN processed successfully',
                'code' => '00',
                'data' => [
                    'ipn_log_id' => $log?->id,
                    'transaction_id' => $transactionId,
                    'order_no' => $orderNo,
                    'payment_updated' => $updateResult['updated'] ?? false,
                    'idempotency_key' => $idempotencyKey,
                ],
            ];
        } catch (Throwable $e) {
            $this->markLog($log, IPNLog::STATUS_FAILED, [
                'phase' => 'exception',
                'error' => $e->getMessage(),
            ]);

            PayfastLogger::error('IPN Processing Error', [
                'error' => $e->getMessage(),
                'ipn_log_id' => $log?->id,
                'data' => $data,
            ]);

            return [
                'status' => false,
                'message' => 'Error processing IPN: ' . $e->getMessage(),
                'code' => 'IPN_ERROR',
                'data' => ['ipn_log_id' => $log?->id],
            ];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validateIPN(array $data): bool
    {
        return $this->extractTransactionId($data) !== null || $this->extractOrderNo($data) !== null;
    }

    /**
     * @param array<string, mixed> $ipnData
     * @return array<string, mixed>
     */
    public function updatePaymentStatus(array $ipnData): array
    {
        try {
            $transactionId = $this->extractTransactionId($ipnData);
            $orderNo = $this->extractOrderNo($ipnData);
            $status = $this->extractStatus($ipnData);

            if (!$transactionId && !$orderNo) {
                return [
                    'updated' => false,
                    'message' => 'No transaction_id or order_no found',
                ];
            }

            $payment = null;
            if ($transactionId) {
                $payment = $this->paymentRepository->findByTransactionId($transactionId);
            }
            if (!$payment && $orderNo) {
                $payment = $this->paymentRepository->findByBasketId($orderNo);
            }

            if (!$payment) {
                return [
                    'updated' => false,
                    'message' => 'Payment record not found',
                ];
            }

            $paymentStatus = $this->mapIPNStatusToPaymentStatus($status);
            if (!$paymentStatus) {
                return [
                    'updated' => false,
                    'message' => 'Unknown IPN status',
                ];
            }

            $oldStatus = $payment->status;

            // Terminal-state guards (idempotent payment updates)
            if ($oldStatus === ProcessPayment::STATUS_COMPLETED) {
                if ($paymentStatus === ProcessPayment::STATUS_COMPLETED) {
                    return [
                        'updated' => false,
                        'message' => 'Payment already completed (idempotent no-op)',
                        'payment_id' => $payment->id,
                        'old_status' => $oldStatus,
                        'new_status' => $oldStatus,
                    ];
                }

                return [
                    'updated' => false,
                    'message' => 'Payment already completed, ignoring non-success status',
                    'payment_id' => $payment->id,
                    'old_status' => $oldStatus,
                    'new_status' => $oldStatus,
                ];
            }

            if ($oldStatus === $paymentStatus) {
                return [
                    'updated' => false,
                    'message' => 'Payment already in target status (idempotent no-op)',
                    'payment_id' => $payment->id,
                    'old_status' => $oldStatus,
                    'new_status' => $paymentStatus,
                ];
            }

            $updateData = ['status' => $paymentStatus];
            if ($paymentStatus === ProcessPayment::STATUS_COMPLETED && !$payment->completed_at) {
                $updateData['completed_at'] = now();
            }

            $this->paymentRepository->update((string) $payment->id, $updateData);
            $payment->refresh();

            $requestData = json_decode($payment->requestData ?? '{}', true) ?: [];

            if ($paymentStatus === ProcessPayment::STATUS_COMPLETED) {
                Event::dispatch(new PaymentCompleted($requestData, $ipnData));
            } elseif ($paymentStatus === ProcessPayment::STATUS_FAILED) {
                Event::dispatch(new PaymentFailed(
                    $requestData,
                    (string) $status,
                    $ipnData['message'] ?? $ipnData['MESSAGE'] ?? 'Payment failed'
                ));
            }

            return [
                'updated' => true,
                'message' => 'Payment status updated',
                'payment_id' => $payment->id,
                'old_status' => $oldStatus,
                'new_status' => $paymentStatus,
            ];
        } catch (Throwable $e) {
            PayfastLogger::error('Payment Status Update Error', [
                'error' => $e->getMessage(),
                'ipn_data' => $ipnData,
            ]);

            return [
                'updated' => false,
                'message' => 'Error updating payment status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Mark an inbound log rejected (middleware / FormRequest failures).
     *
     * @param array<string, mixed> $details
     */
    public function markRejected(?int $logId, string $reason, array $details = []): void
    {
        if (!$logId) {
            return;
        }

        $log = $this->ipnLogRepository->findById($logId);
        $this->markLog($log, IPNLog::STATUS_REJECTED, array_merge(['reason' => $reason], $details));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveInboundLog(array $data): ?IPNLog
    {
        $logId = request()?->attributes->get(LogIncomingIpn::REQUEST_ATTR_LOG_ID);
        if ($logId) {
            $log = $this->ipnLogRepository->findById($logId);
            if ($log) {
                return $log;
            }
        }

        // Programmatic / console calls — still persist a row so nothing is missed.
        try {
            return $this->ipnLogRepository->create([
                'order_no' => $this->extractOrderNo($data) ?? '',
                'transaction_id' => $this->extractTransactionId($data) ?? ('manual-' . uniqid('', true)),
                'status' => $this->extractStatus($data) ?: 'unknown',
                'processing_status' => IPNLog::STATUS_RECEIVED,
                'amount' => $this->extractAmount($data),
                'currency' => $this->extractCurrency($data),
                'details' => ['phase' => 'received', 'source' => 'processIPN'],
                'raw_payload' => json_encode($data) ?: '{}',
                'payload_hash' => hash('sha256', json_encode($this->normalizePayload($data)) ?: ''),
                'ip_address' => request()?->ip(),
                'received_at' => now(),
            ]);
        } catch (Throwable $e) {
            PayfastLogger::error('Unable to create IPN log in processIPN', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $details
     */
    private function markLog(?IPNLog $log, string $processingStatus, array $details = []): void
    {
        if (!$log) {
            return;
        }

        try {
            $this->ipnLogRepository->update($log, [
                'processing_status' => $processingStatus,
                'details' => array_merge($log->details ?? [], $details),
            ]);
        } catch (Throwable $e) {
            PayfastLogger::warning('Failed to update IPN log status', [
                'ipn_log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function findDuplicate(string $idempotencyKey, ?string $transactionId, ?int $currentLogId): ?IPNLog
    {
        $byKey = $this->ipnLogRepository->findProcessedByIdempotencyKey($idempotencyKey);
        if ($byKey && $byKey->id !== $currentLogId) {
            return $byKey;
        }

        if ($transactionId) {
            $byTxn = $this->ipnLogRepository->findProcessedByTransactionId($transactionId);
            if ($byTxn && $byTxn->id !== $currentLogId) {
                return $byTxn;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function buildIdempotencyKey(array $data): string
    {
        $normalized = $this->normalizePayload($data);
        $parts = [
            $normalized['transaction_id'] ?? '',
            $normalized['order_no'] ?? '',
            $normalized['status'] ?? '',
            (string) ($normalized['amount'] ?? ''),
        ];

        $material = implode('|', $parts);
        if (trim($material, '|') === '') {
            return hash('sha256', json_encode($normalized) ?: uniqid('ipn', true));
        }

        return hash('sha256', $material);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        return [
            'transaction_id' => $this->extractTransactionId($data),
            'order_no' => $this->extractOrderNo($data),
            'status' => strtolower((string) $this->extractStatus($data)),
            'amount' => $this->extractAmount($data),
            'currency' => strtoupper($this->extractCurrency($data)),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractTransactionId(array $data): ?string
    {
        $value = $data['transaction_id'] ?? $data['TRANSACTION_ID'] ?? null;

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractOrderNo(array $data): ?string
    {
        $value = $data['order_no'] ?? $data['ORDER_NO'] ?? $data['basket_id'] ?? $data['BASKET_ID'] ?? null;

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractStatus(array $data): string
    {
        return (string) ($data['status'] ?? $data['STATUS'] ?? $data['code'] ?? $data['CODE'] ?? 'unknown');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractAmount(array $data): float
    {
        return (float) ($data['amount'] ?? $data['AMOUNT'] ?? $data['txnamt'] ?? $data['TXNAMT'] ?? 0);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractCurrency(array $data): string
    {
        $currency = (string) ($data['currency'] ?? $data['CURRENCY'] ?? $data['currency_code'] ?? $data['CURRENCY_CODE'] ?? 'PKR');

        return $currency !== '' ? substr($currency, 0, 3) : 'PKR';
    }

    private function mapIPNStatusToPaymentStatus(?string $ipnStatus): ?string
    {
        if (!$ipnStatus) {
            return null;
        }

        if ($ipnStatus === '00') {
            return ProcessPayment::STATUS_COMPLETED;
        }

        $lowerStatus = strtolower($ipnStatus);

        $statusMap = [
            'completed' => ProcessPayment::STATUS_COMPLETED,
            'success' => ProcessPayment::STATUS_COMPLETED,
            'failed' => ProcessPayment::STATUS_FAILED,
            'failure' => ProcessPayment::STATUS_FAILED,
            'cancelled' => ProcessPayment::STATUS_CANCELLED,
            'cancel' => ProcessPayment::STATUS_CANCELLED,
        ];

        if (isset($statusMap[$lowerStatus])) {
            return $statusMap[$lowerStatus];
        }

        if (str_contains($lowerStatus, 'success') || str_contains($lowerStatus, 'completed')) {
            return ProcessPayment::STATUS_COMPLETED;
        }
        if (str_contains($lowerStatus, 'fail') || str_contains($lowerStatus, 'error')) {
            return ProcessPayment::STATUS_FAILED;
        }
        if (str_contains($lowerStatus, 'cancel')) {
            return ProcessPayment::STATUS_CANCELLED;
        }

        return null;
    }

    private function isUniqueViolation(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || (int) $e->getCode() === 23000;
    }
}
