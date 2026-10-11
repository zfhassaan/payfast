<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Listeners;

use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;

/**
 * Fallback persistence for PaymentValidated events.
 *
 * PayFast already creates the process payment row in getOTPScreen /
 * validateWalletTransaction. This listener only creates a record when
 * one does not already exist for the transaction id (avoids duplicates).
 */
class StorePaymentRecord
{
    public function __construct(
        private readonly ProcessPaymentRepositoryInterface $paymentRepository
    ) {
    }

    /**
     * Handle payment validated event.
     */
    public function handle(PaymentValidated $event): void
    {
        $transactionId = (string) ($event->validationResponse['transaction_id'] ?? '');

        if ($transactionId !== '' && $this->paymentRepository->findByTransactionId($transactionId)) {
            return;
        }

        $orderNo = (string) (
            $event->paymentData['orderNumber']
            ?? $event->paymentData['basket_id']
            ?? $event->paymentData['orderNo']
            ?? ''
        );

        if ($orderNo !== '' && $this->paymentRepository->findByBasketId($orderNo)) {
            return;
        }

        $this->paymentRepository->create([
            'token' => $event->paymentData['token'] ?? '',
            'orderNo' => $orderNo,
            'data_3ds_secureid' => $event->validationResponse['data_3ds_secureid'] ?? '',
            'transaction_id' => $transactionId,
            'payload' => json_encode([
                'customer_validate' => $event->validationResponse,
                'user_request' => $event->paymentData,
            ]),
            'requestData' => json_encode($event->paymentData),
        ]);
    }
}
