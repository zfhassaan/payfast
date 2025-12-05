<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TokenRefreshed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $oldToken
     * @param string $newToken
     */
    public function __construct(
        public readonly string $oldToken,
        public readonly string $newToken
    ) {
    }
}


