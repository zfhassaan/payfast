<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services\Contracts;

use zfhassaan\Payfast\DTOs\PaymentRequestDTO;

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
     * @param array<string, mixed> $data
     * @param int $bankCode
     * @param int $accountTypeId
     * @return array<string, mixed>
     */
    public function validateWalletTransaction(array $data, int $bankCode, int $accountTypeId): array;

    /**
     * Initiate wallet transaction.
     *
     * @param array<string, mixed> $data
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function initiateWalletTransaction(array $data, string $authToken): array;
}

