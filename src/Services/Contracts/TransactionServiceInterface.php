<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services\Contracts;

interface TransactionServiceInterface
{
    /**
     * Get transaction details by transaction ID.
     *
     * @param string $transactionId
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function getTransactionDetails(string $transactionId, string $authToken): array;

    /**
     * Get transaction details by basket ID.
     *
     * @param string $basketId
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function getTransactionDetailsByBasketId(string $basketId, string $authToken): array;

    /**
     * Request a refund for a transaction.
     *
     * @param array<string, mixed> $data
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function refundTransaction(array $data, string $authToken): array;

    /**
     * List available banks.
     *
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function listBanks(string $authToken): array;

    /**
     * List instruments with bank code.
     *
     * @param string|int $bankCode
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function listInstrumentsWithBank(string|int $bankCode, string $authToken): array;
}

