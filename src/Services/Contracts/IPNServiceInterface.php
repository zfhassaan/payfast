<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services\Contracts;

interface IPNServiceInterface
{
    /**
     * Process IPN notification from PayFast.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function processIPN(array $data): array;

    /**
     * Validate IPN data.
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    public function validateIPN(array $data): bool;

    /**
     * Update payment status from IPN notification.
     *
     * @param array<string, mixed> $ipnData
     * @return array<string, mixed>
     */
    public function updatePaymentStatus(array $ipnData): array;
}

