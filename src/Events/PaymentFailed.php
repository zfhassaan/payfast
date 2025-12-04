<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $paymentData
     * @param string $errorCode
     * @param string $errorMessage
     */
    public function __construct(
        public readonly array $paymentData,
        public readonly string $errorCode,
        public readonly string $errorMessage
    ) {
    }
}

