<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;
use zfhassaan\Payfast\Services\Contracts\TransactionServiceInterface;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ConfigService $configService
    ) {
    }

    /**
     * Get transaction details by transaction ID.
     *
     * @param string $transactionId
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function getTransactionDetails(string $transactionId, string $authToken): array
    {
        $url = $this->configService->getApiUrl() . 'transaction/' . $transactionId;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        $response = $this->httpClient->get($url, $headers);

        return $response ?? [];
    }

    /**
     * Get transaction details by basket ID.
     *
     * @param string $basketId
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function getTransactionDetailsByBasketId(string $basketId, string $authToken): array
    {
        $url = $this->configService->getApiUrl() . 'transaction/basket_id' . $basketId;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        $response = $this->httpClient->get($url, $headers);

        return $response ?? [];
    }

    /**
     * Request a refund for a transaction.
     *
     * @param array<string, mixed> $data
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function refundTransaction(array $data, string $authToken): array
    {
        $url = $this->configService->getApiUrl() . 'transaction/refund/' . ($data['transactionId'] ?? '');
        $postFields = [
            'transaction_id' => $data['transactionId'] ?? '',
            'txnamt' => $data['transactionAmount'] ?? '',
            'refund_reason' => $data['refund_reason'] ?? '',
            'customer_ip' => request()->ip(),
        ];

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        $response = $this->httpClient->post($url, $postFields, $headers);

        return $response ?? [];
    }

    /**
     * List available banks.
     *
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function listBanks(string $authToken): array
    {
        $url = $this->configService->getApiUrl() . 'list/banks';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        $response = $this->httpClient->get($url, $headers);

        return $response ?? [];
    }

    /**
     * List instruments with bank code.
     *
     * @param string|int $bankCode
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function listInstrumentsWithBank(string|int $bankCode, string $authToken): array
    {
        $url = $this->configService->getApiUrl() . 'list/instruments?bank_code=' . $bankCode;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        $response = $this->httpClient->get($url, $headers);

        return $response ?? [];
    }
}

