<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services\Contracts;

interface OTPVerificationServiceInterface
{
    /**
     * Verify OTP and store pares in database.
     *
     * @param string $transactionId
     * @param string $otp
     * @param string $pares
     * @return array<string, mixed>
     */
    public function verifyOTPAndStorePares(string $transactionId, string $otp, string $pares): array;

    /**
     * Complete transaction using stored pares from callback.
     *
     * @param string $pares
     * @return array<string, mixed>
     */
    public function completeTransactionFromPares(string $pares): array;
}

