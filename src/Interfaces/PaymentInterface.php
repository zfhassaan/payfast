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
     * Get transaction details by basket/order ID.
     *
     * @param string $basketId
     * @return JsonResponse
     */
    public function getTransactionDetailsByBasketId(string $basketId): JsonResponse;

    /**
     * Request a refund for a transaction.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function refundTransactionRequest(array $data): JsonResponse;

    /**
     * Void a non-settled transaction.
     *
     * @param string $transactionId
     * @return JsonResponse
     */
    public function voidTransaction(string $transactionId): JsonResponse;

    /**
     * Get settlement status of a transaction.
     *
     * @param string $transactionId
     * @return JsonResponse
     */
    public function getSettlementStatus(string $transactionId): JsonResponse;

    /**
     * Pay with EasyPaisa wallet.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function payWithEasyPaisa(array $data): JsonResponse;

    /**
     * Pay with UPaisa wallet.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function payWithUPaisa(array $data): JsonResponse;

    /**
     * Validate wallet transaction.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function validateWalletTransaction(array $data): JsonResponse;

    /**
     * Initiate wallet transaction.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function walletTransactionInitiate(array $data): JsonResponse;

    /**
     * Initiate a transaction.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function initiateTransaction(array $data): JsonResponse;

    /**
     * Create a new subscription.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function createSubscription(array $data): JsonResponse;

    /**
     * Update an existing subscription.
     *
     * @param string $subscriptionId
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function updateSubscription(string $subscriptionId, array $data): JsonResponse;

    /**
     * Cancel a subscription.
     *
     * @param string $subscriptionId
     * @return JsonResponse
     */
    public function cancelSubscription(string $subscriptionId): JsonResponse;

    /**
     * Verify OTP and store pares in database.
     *
     * @param string $transactionId
     * @param string $otp
     * @param string $pares
     * @return JsonResponse
     */
    public function verifyOTPAndStorePares(string $transactionId, string $otp, string $pares): JsonResponse;

    /**
     * Complete transaction from callback using pares.
     *
     * @param string $pares
     * @return JsonResponse
     */
    public function completeTransactionFromPares(string $pares): JsonResponse;

    /**
     * Handle IPN (Instant Payment Notification) webhook from PayFast.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function handleIPN(array $data): JsonResponse;
}
