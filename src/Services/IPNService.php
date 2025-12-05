<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\IPNLogRepositoryInterface;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;
use zfhassaan\Payfast\Services\Contracts\IPNServiceInterface;

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
        try {
            // Validate IPN data
            if (!$this->validateIPN($data)) {
                return [
                    'status' => false,
                    'message' => 'Invalid IPN data',
                    'code' => 'INVALID_IPN',
                ];
            }

            // Check if IPN already processed (idempotency check)
            $transactionId = $data['transaction_id'] ?? $data['TRANSACTION_ID'] ?? null;
            if ($transactionId && $this->ipnLogRepository->findByTransactionId($transactionId)) {
                return [
                    'status' => true,
                    'message' => 'IPN already processed',
                    'code' => 'ALREADY_PROCESSED',
                ];
            }

            // Extract IPN data
            $orderNo = $data['order_no'] ?? $data['ORDER_NO'] ?? $data['basket_id'] ?? $data['BASKET_ID'] ?? null;
            $status = $data['status'] ?? $data['STATUS'] ?? $data['code'] ?? $data['CODE'] ?? 'unknown';
            $amount = (float) ($data['amount'] ?? $data['AMOUNT'] ?? $data['txnamt'] ?? $data['TXNAMT'] ?? 0);
            $currency = $data['currency'] ?? $data['CURRENCY'] ?? $data['currency_code'] ?? $data['CURRENCY_CODE'] ?? 'PKR';

            // Store IPN log
            $ipnLog = $this->ipnLogRepository->create([
                'order_no' => $orderNo,
                'transaction_id' => $transactionId,
                'status' => $status,
                'amount' => $amount,
                'currency' => $currency,
                'details' => $data,
                'received_at' => now(),
            ]);

            // Update payment status if transaction_id matches
            $updateResult = $this->updatePaymentStatus($data);

            Log::channel('payfast')->info('IPN Processed', [
                'ipn_id' => $ipnLog->id,
                'transaction_id' => $transactionId,
                'order_no' => $orderNo,
                'status' => $status,
            ]);

            return [
                'status' => true,
                'message' => 'IPN processed successfully',
                'code' => '00',
                'data' => [
                    'ipn_log_id' => $ipnLog->id,
                    'transaction_id' => $transactionId,
                    'order_no' => $orderNo,
                    'payment_updated' => $updateResult['updated'] ?? false,
                ],
            ];
        } catch (\Exception $e) {
            Log::channel('payfast')->error('IPN Processing Error', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'status' => false,
                'message' => 'Error processing IPN: ' . $e->getMessage(),
                'code' => 'IPN_ERROR',
            ];
        }
    }

    /**
     * Validate IPN data.
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    public function validateIPN(array $data): bool
    {
        // Check for required fields - at least transaction_id or order_no should be present
        $hasTransactionId = isset($data['transaction_id']) || isset($data['TRANSACTION_ID']);
        $hasOrderNo = isset($data['order_no']) || isset($data['ORDER_NO']) || isset($data['basket_id']) || isset($data['BASKET_ID']);

        if (!$hasTransactionId && !$hasOrderNo) {
            return false;
        }

        // Additional validation can be added here (e.g., signature verification)
        // For PayFast, you might want to verify the signature if they provide one

        return true;
    }

    /**
     * Update payment status from IPN notification.
     *
     * @param array<string, mixed> $ipnData
     * @return array<string, mixed>
     */
    public function updatePaymentStatus(array $ipnData): array
    {
        try {
            $transactionId = $ipnData['transaction_id'] ?? $ipnData['TRANSACTION_ID'] ?? null;
            $orderNo = $ipnData['order_no'] ?? $ipnData['ORDER_NO'] ?? $ipnData['basket_id'] ?? $ipnData['BASKET_ID'] ?? null;
            $status = $ipnData['status'] ?? $ipnData['STATUS'] ?? $ipnData['code'] ?? $ipnData['CODE'] ?? null;

            if (!$transactionId && !$orderNo) {
                return [
                    'updated' => false,
                    'message' => 'No transaction_id or order_no found',
                ];
            }

            // Find payment by transaction_id or order_no
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

            // Map IPN status to payment status
            $paymentStatus = $this->mapIPNStatusToPaymentStatus($status);

            if (!$paymentStatus) {
                return [
                    'updated' => false,
                    'message' => 'Unknown IPN status',
                ];
            }

            // Update payment status
            $updateData = ['status' => $paymentStatus];

            if ($paymentStatus === ProcessPayment::STATUS_COMPLETED && !$payment->completed_at) {
                $updateData['completed_at'] = now();
            }

            if ($paymentStatus === ProcessPayment::STATUS_FAILED) {
                // Don't update if already completed
                if ($payment->status === ProcessPayment::STATUS_COMPLETED) {
                    return [
                        'updated' => false,
                        'message' => 'Payment already completed, ignoring failed status',
                    ];
                }
            }

            $this->paymentRepository->update((string) $payment->id, $updateData);
            $payment->refresh();

            // Dispatch events
            $requestData = json_decode($payment->requestData ?? '{}', true);

            if ($paymentStatus === ProcessPayment::STATUS_COMPLETED) {
                Event::dispatch(new PaymentCompleted($requestData, $ipnData));
            } elseif ($paymentStatus === ProcessPayment::STATUS_FAILED) {
                Event::dispatch(new PaymentFailed(
                    $requestData,
                    $status,
                    $ipnData['message'] ?? $ipnData['MESSAGE'] ?? 'Payment failed'
                ));
            }

            return [
                'updated' => true,
                'message' => 'Payment status updated',
                'payment_id' => $payment->id,
                'old_status' => $payment->getOriginal('status'),
                'new_status' => $paymentStatus,
            ];
        } catch (\Exception $e) {
            Log::channel('payfast')->error('Payment Status Update Error', [
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
     * Map IPN status to payment status.
     *
     * @param string|null $ipnStatus
     * @return string|null
     */
    private function mapIPNStatusToPaymentStatus(?string $ipnStatus): ?string
    {
        if (!$ipnStatus) {
            return null;
        }

        $statusMap = [
            '00' => ProcessPayment::STATUS_COMPLETED,
            '00' => ProcessPayment::STATUS_COMPLETED, // Success
            'completed' => ProcessPayment::STATUS_COMPLETED,
            'success' => ProcessPayment::STATUS_COMPLETED,
            'SUCCESS' => ProcessPayment::STATUS_COMPLETED,
            'failed' => ProcessPayment::STATUS_FAILED,
            'FAILED' => ProcessPayment::STATUS_FAILED,
            'failure' => ProcessPayment::STATUS_FAILED,
            'cancelled' => ProcessPayment::STATUS_CANCELLED,
            'CANCELLED' => ProcessPayment::STATUS_CANCELLED,
            'cancel' => ProcessPayment::STATUS_CANCELLED,
        ];

        // Check exact match first
        if (isset($statusMap[strtolower($ipnStatus)])) {
            return $statusMap[strtolower($ipnStatus)];
        }

        // Check if status contains success/completed
        $lowerStatus = strtolower($ipnStatus);
        if (str_contains($lowerStatus, 'success') || str_contains($lowerStatus, 'completed') || $ipnStatus === '00') {
            return ProcessPayment::STATUS_COMPLETED;
        }

        // Check if status contains failed/failure
        if (str_contains($lowerStatus, 'fail') || str_contains($lowerStatus, 'error')) {
            return ProcessPayment::STATUS_FAILED;
        }

        // Check if status contains cancelled/cancel
        if (str_contains($lowerStatus, 'cancel')) {
            return ProcessPayment::STATUS_CANCELLED;
        }

        return null;
    }
}

