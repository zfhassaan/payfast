<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentValidated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $paymentData
     * @param array<string, mixed> $validationResponse
     */
    public function __construct(
        public readonly array $paymentData,
        public readonly array $validationResponse
    ) {
    }
}

