<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Listeners;

use Illuminate\Support\Facades\Log;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentInitiated;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ActivityLogRepositoryInterface;

class LogPaymentActivity
{
    public function __construct(
        private readonly ActivityLogRepositoryInterface $activityLogRepository
    ) {
    }

    public function handlePaymentInitiated(PaymentInitiated $event): void
    {
        Log::channel('payfast')->info('Payment Initiated', [
            'payment_data' => $event->paymentData,
            'response' => $event->response,
        ]);

        $this->persist(
            ProcessPayment::STATUS_PENDING,
            $event->paymentData,
            $event->response
        );
    }

    public function handlePaymentValidated(PaymentValidated $event): void
    {
        Log::channel('payfast')->info('Payment Validated', [
            'payment_data' => $event->paymentData,
            'validation_response' => $event->validationResponse,
        ]);

        $this->persist(
            ProcessPayment::STATUS_VALIDATED,
            $event->paymentData,
            $event->validationResponse
        );
    }

    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        Log::channel('payfast')->info('Payment Completed', [
            'transaction_data' => $event->transactionData,
            'response' => $event->response,
        ]);

        $this->persist(
            ProcessPayment::STATUS_COMPLETED,
            $event->transactionData,
            $event->response
        );
    }

    public function handlePaymentFailed(PaymentFailed $event): void
    {
        Log::channel('payfast')->error('Payment Failed', [
            'payment_data' => $event->paymentData,
            'error_code' => $event->errorCode,
            'error_message' => $event->errorMessage,
        ]);

        $this->persist(
            ProcessPayment::STATUS_FAILED,
            $event->paymentData,
            [
                'error_code' => $event->errorCode,
                'error_message' => $event->errorMessage,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $paymentData
     * @param  array<string, mixed>  $details
     */
    private function persist(string $status, array $paymentData, array $details): void
    {
        try {
            $this->activityLogRepository->create([
                'user_id' => (int) ($paymentData['user_id'] ?? 0),
                'transaction_id' => (string) (
                    $details['transaction_id']
                    ?? $paymentData['transaction_id']
                    ?? ''
                ),
                'order_no' => (string) (
                    $paymentData['orderNumber']
                    ?? $paymentData['basket_id']
                    ?? $paymentData['orderNo']
                    ?? $paymentData['order_no']
                    ?? ''
                ),
                'status' => $status,
                'amount' => (float) (
                    $paymentData['transactionAmount']
                    ?? $paymentData['txnamt']
                    ?? $details['txnamt']
                    ?? 0
                ),
                'details' => json_encode($details),
                'metadata' => [
                    'source' => 'event_listener',
                    'payment_method' => $paymentData['payment_method'] ?? null,
                ],
                'transaction_date' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('payfast')->warning('Failed to persist activity log', [
                'error' => $e->getMessage(),
                'status' => $status,
            ]);
        }
    }
}
