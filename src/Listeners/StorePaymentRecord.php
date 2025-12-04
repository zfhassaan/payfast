<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Listeners;

use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;

class StorePaymentRecord
{
    public function __construct(
        private readonly ProcessPaymentRepositoryInterface $paymentRepository
    ) {
    }

    /**
     * Handle payment validated event.
     *
     * @param PaymentValidated $event
     * @return void
     */
    public function handle(PaymentValidated $event): void
    {
        $options = [
            'token' => $event->paymentData['token'] ?? '',
            'data_3ds_secureid' => $event->validationResponse['data_3ds_secureid'] ?? '',
            'transaction_id' => $event->validationResponse['transaction_id'] ?? '',
            'payload' => json_encode([
                'customer_validate' => $event->validationResponse,
                'user_request' => $event->paymentData,
            ]),
            'requestData' => json_encode($event->paymentData),
        ];

        $this->paymentRepository->create($options);
    }
}

