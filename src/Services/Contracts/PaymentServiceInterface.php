<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services\Contracts;

use zfhassaan\Payfast\DTOs\PaymentRequestDTO;
use zfhassaan\Payfast\DTOs\WalletPaymentRequestDTO;

interface PaymentServiceInterface
{
    /**
     * Validate customer and get OTP screen.
     *
     * @param PaymentRequestDTO $dto
     * @return array<string, mixed>
     */
    public function validateCustomer(PaymentRequestDTO $dto): array;

    /**
     * Initiate a transaction.
     *
     * @param PaymentRequestDTO $dto
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function initiateTransaction(PaymentRequestDTO $dto, string $authToken): array;

    /**
     * Validate wallet transaction.
     *
     * @param string|null $authToken Bearer token required by PayFast for wallet validate
     * @return array<string, mixed>
     */
    public function validateWalletTransaction(
        WalletPaymentRequestDTO $dto,
        ?string $authToken = null
    ): array;

    /**
     * Initiate wallet transaction.
     *
     * @return array<string, mixed>
     */
    public function initiateWalletTransaction(WalletPaymentRequestDTO $dto, string $authToken): array;
}


