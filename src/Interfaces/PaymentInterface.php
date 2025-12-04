<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Interfaces;

use Illuminate\Http\JsonResponse;

interface PaymentInterface
{
    /**
     * Get authentication token.
     *
     * @return JsonResponse
     */
    public function getToken(): JsonResponse;

    /**
     * Refresh authentication token.
     *
     * @param string $token
     * @param string $refreshToken
     * @return JsonResponse|null
     */
    public function refreshToken(string $token, string $refreshToken): ?JsonResponse;

    /**
     * Get OTP screen for customer validation.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function getOTPScreen(array $data): JsonResponse;

    /**
     * List available banks.
     *
     * @return JsonResponse
     */
    public function listBanks(): JsonResponse;

    /**
     * List instruments with bank code.
     *
     * @param string|array $code
     * @return JsonResponse|bool
     */
    public function listInstrumentsWithBank(string|array $code): JsonResponse|bool;

    /**
     * Get transaction details by transaction ID.
     *
     * @param string $transactionId
     * @return JsonResponse
     */
    public function getTransactionDetails(string $transactionId): JsonResponse;

    /**
     * Request a refund for a transaction.
     *
     * @param array<string, mixed> $data
     * @return bool|string
     */
    public function refundTransactionRequest(array $data): bool|string;

    /**
     * Pay with EasyPaisa wallet.
     *
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function payWithEasyPaisa(array $data): mixed;

    /**
     * Pay with UPaisa wallet.
     *
     * @param array<string, mixed> $data
     * @return mixed
     */
    public function payWithUPaisa(array $data): mixed;

    /**
     * Validate wallet transaction.
     *
     * @param array<string, mixed> $data
     * @return string|bool
     */
    public function validateWalletTransaction(array $data): string|bool;

    /**
     * Initiate wallet transaction.
     *
     * @param array<string, mixed> $data
     * @return bool|string
     */
    public function walletTransactionInitiate(array $data): bool|string;

    /**
     * Initiate a transaction.
     *
     * @param array<string, mixed> $data
     * @return bool|string
     */
    public function initiateTransaction(array $data): bool|string;

    /**
     * Verify OTP and store pares in database.
     *
     * @param string $transactionId
     * @param string $otp
     * @param string $pares
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOTPAndStorePares(string $transactionId, string $otp, string $pares): \Illuminate\Http\JsonResponse;

    /**
     * Complete transaction from callback using pares.
     *
     * @param string $pares
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeTransactionFromPares(string $pares): \Illuminate\Http\JsonResponse;
}
