<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $transactionData
     * @param array<string, mixed> $response
     */
    public function __construct(
        public readonly array $transactionData,
        public readonly array $response
    ) {
    }
}

