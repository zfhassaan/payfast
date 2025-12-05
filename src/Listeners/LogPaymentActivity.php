<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Listeners;

use Illuminate\Support\Facades\Log;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentInitiated;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Repositories\Contracts\ActivityLogRepositoryInterface;

class LogPaymentActivity
{
    public function __construct(
        private readonly ActivityLogRepositoryInterface $activityLogRepository
    ) {
    }

    /**
     * Handle payment initiated event.
     *
     * @param PaymentInitiated $event
     * @return void
     */
    public function handlePaymentInitiated(PaymentInitiated $event): void
    {
        Log::channel('payfast')->info('Payment Initiated', [
            'payment_data' => $event->paymentData,
            'response' => $event->response,
        ]);
    }

    /**
     * Handle payment validated event.
     *
     * @param PaymentValidated $event
     * @return void
     */
    public function handlePaymentValidated(PaymentValidated $event): void
    {
        Log::channel('payfast')->info('Payment Validated', [
            'payment_data' => $event->paymentData,
            'validation_response' => $event->validationResponse,
        ]);
    }

    /**
     * Handle payment completed event.
     *
     * @param PaymentCompleted $event
     * @return void
     */
    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        Log::channel('payfast')->info('Payment Completed', [
            'transaction_data' => $event->transactionData,
            'response' => $event->response,
        ]);
    }

    /**
     * Handle payment failed event.
     *
     * @param PaymentFailed $event
     * @return void
     */
    public function handlePaymentFailed(PaymentFailed $event): void
    {
        Log::channel('payfast')->error('Payment Failed', [
            'payment_data' => $event->paymentData,
            'error_code' => $event->errorCode,
            'error_message' => $event->errorMessage,
        ]);
    }
}
