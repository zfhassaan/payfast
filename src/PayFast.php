<?php

declare(strict_types=1);

namespace zfhassaan\Payfast;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use zfhassaan\Payfast\DTOs\PaymentRequestDTO;
use zfhassaan\Payfast\DTOs\WalletPaymentRequestDTO;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentInitiated;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Events\TokenRefreshed;
use zfhassaan\Payfast\Helpers\Utility;
use zfhassaan\Payfast\Http\Requests\CardPaymentRequest;
use zfhassaan\Payfast\Http\Requests\WalletPaymentRequest;
use zfhassaan\Payfast\Interfaces\PaymentInterface;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;
use zfhassaan\Payfast\Services\Contracts\AuthenticationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\IPNServiceInterface;
use zfhassaan\Payfast\Services\Contracts\OTPVerificationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\PaymentServiceInterface;
use zfhassaan\Payfast\Services\Contracts\SubscriptionServiceInterface;
use zfhassaan\Payfast\Services\Contracts\TransactionServiceInterface;

/**
 * PayFast Payment Gateway Service
 *
 * This class provides a clean interface to interact with PayFast payment gateway APIs.
 * It uses service-based architecture with event-driven components.
 *
 * @package zfhassaan\Payfast
 */
class PayFast implements PaymentInterface
{
    private ?string $authToken = null;

    public function __construct(
        private readonly AuthenticationServiceInterface $authenticationService,
        private readonly PaymentServiceInterface $paymentService,
        private readonly SubscriptionServiceInterface $subscriptionService,
        private readonly TransactionServiceInterface $transactionService,
        private readonly OTPVerificationServiceInterface $otpVerificationService,
        private readonly ProcessPaymentRepositoryInterface $paymentRepository,
        private readonly IPNServiceInterface $ipnService
    ) {
    }

    /**
     * Get authentication token.
     *
     * @return JsonResponse
     */
    public function getToken(): JsonResponse
    {
        try {
            $response = $this->authenticationService->getToken();

            if (isset($response['code']) && $response['code'] === '00') {
                $this->authToken = $response['data']['token'] ?? null;

                return Utility::returnSuccess($response['data'] ?? $response, 'Token retrieved successfully', $response['code']);
            }

            return Utility::returnError($response, $response['message'] ?? 'Failed to get token', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Refresh authentication token.
     *
     * @param string $token
     * @param string $refreshToken
     * @return JsonResponse|null
     */
    public function refreshToken(string $token, string $refreshToken): ?JsonResponse
    {
        try {
            $this->authToken = $token;
            $response = $this->authenticationService->refreshToken($token, $refreshToken);

            if (isset($response['code']) && $response['code'] === '00') {
                $this->authToken = $response['data']['token'] ?? $token;

                Event::dispatch(new TokenRefreshed($token, $this->authToken));

                return Utility::returnSuccess($response['data'] ?? $response, 'Token refreshed successfully', $response['code'] ?? '00');
            }

            return Utility::returnError(
                $response,
                $response['message'] ?? 'Failed to refresh token',
                $response['code'] ?? '',
                ResponseAlias::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Get OTP screen for customer validation.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function getOTPScreen(array $data): JsonResponse
    {
        $validator = CardPaymentRequest::validateArray($data);

        if ($validator->fails()) {
            return Utility::returnError(
                $validator->errors(),
                $validator->errors()->first(),
                'VALIDATION_ERROR',
                Response::HTTP_BAD_REQUEST
            );
        }

        $data = $validator->validated();

        try {
            // Get token first
            $tokenResponse = $this->getToken();
            $tokenData = json_decode($tokenResponse->getContent(), true);

            if (!isset($tokenData['data']['token'])) {
                return Utility::returnError($tokenData, 'Failed to get authentication token', 'AUTH_ERROR');
            }

            $this->authToken = $tokenData['data']['token'];

            // Create DTO from request data
            $dto = PaymentRequestDTO::fromArray($data);

            // Validate customer
            $validationResponse = $this->paymentService->validateCustomer($dto);

            if (!isset($validationResponse['code']) || $validationResponse['code'] !== '00') {
                $errorCode = $validationResponse['code'] ?? 'UNKNOWN';
                $errorResponse = Utility::payfastErrorCodes($errorCode);
                $errorData = json_decode($errorResponse->getContent(), true);

                Event::dispatch(new PaymentFailed($data, $errorCode, $errorData['error_description'] ?? ''));

                return Utility::returnError(
                    $errorData,
                    $errorData['error_description'] ?? 'Validation failed',
                    $errorCode,
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Store payment in database with validated status
            $paymentData = [
                'token' => $this->authToken,
                'orderNo' => $dto->orderNumber,
                'data_3ds_secureid' => $validationResponse['data_3ds_secureid'] ?? '',
                'transaction_id' => $validationResponse['transaction_id'] ?? '',
                'status' => ProcessPayment::STATUS_VALIDATED,
                'payment_method' => ProcessPayment::METHOD_CARD,
                'payload' => json_encode([
                    'customer_validate' => $validationResponse,
                    'user_request' => $data,
                ]),
                'requestData' => json_encode($data),
            ];

            $payment = $this->paymentRepository->create($paymentData);

            // Dispatch payment validated event
            Event::dispatch(new PaymentValidated($data, $validationResponse));

            return Utility::returnSuccess([
                'token' => $this->authToken,
                'customer_validate' => $validationResponse,
                'transaction_id' => $validationResponse['transaction_id'] ?? '',
                'payment_id' => $payment->id,
                'redirect_url' => $validationResponse['redirect_url'] ?? null, // OTP screen URL if provided by PayFast
            ], 'OTP screen retrieved successfully', $validationResponse['code'] ?? '00');
        } catch (\Exception $e) {
            Event::dispatch(new PaymentFailed($data, 'EXCEPTION', $e->getMessage()));

            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * List available banks.
     *
     * @return JsonResponse
     */
    public function listBanks(): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $response = $this->transactionService->listBanks($this->authToken);

            if (!empty($response['banks'])) {
                return Utility::returnSuccess($response['banks'], 'Banks listed successfully', '00');
            }

            return Utility::returnError($response, 'Error Generating Banks List');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage(), 'Internal Server Error');
        }
    }

    /**
     * List instruments with bank code.
     *
     * @param string|array $code
     * @return JsonResponse
     */
    public function listInstrumentsWithBank(string|array $code): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $bankCode = is_array($code) ? ($code['bank_code'] ?? '') : $code;
            $response = $this->transactionService->listInstrumentsWithBank($bankCode, $this->authToken);

            if (isset($response['bankInstruments']) || (isset($response['code']) && $response['code'] === '00')) {
                return Utility::returnSuccess($response['bankInstruments'] ?? $response, 'Bank instruments retrieved successfully', $response['code'] ?? '00');
            }

            return Utility::returnError($response, 'Failed to retrieve bank instruments');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Get transaction details by transaction ID.
     *
     * @param string $transactionId
     * @return JsonResponse
     */
    public function getTransactionDetails(string $transactionId): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $response = $this->transactionService->getTransactionDetails($transactionId, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                return Utility::returnSuccess($response, 'Transaction details retrieved successfully', $response['code']);
            }

            return Utility::returnError($response, $response['message'] ?? 'Failed to retrieve transaction details', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Get transaction details by basket ID.
     *
     * @param string $basketId
     * @return JsonResponse
     */
    public function getTransactionDetailsByBasketId(string $basketId): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $response = $this->transactionService->getTransactionDetailsByBasketId($basketId, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                return Utility::returnSuccess($response, 'Transaction details retrieved successfully', $response['code']);
            }

            return Utility::returnError($response, $response['message'] ?? 'Failed to retrieve transaction details', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Request a refund for a transaction.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function refundTransactionRequest(array $data): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $response = $this->transactionService->refundTransaction($data, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                return Utility::returnSuccess($response, 'Refund processed successfully', $response['code']);
            }

            return Utility::returnError($response, $response['message'] ?? 'Failed to process refund', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Void a non-settled transaction.
     *
     * @param string $transactionId
     * @return JsonResponse
     */
    public function voidTransaction(string $transactionId): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $response = $this->transactionService->voidTransaction($transactionId, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                return Utility::returnSuccess($response, 'Transaction voided successfully', $response['code']);
            }

            return Utility::returnError($response, $response['message'] ?? 'Failed to void transaction', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Get settlement status of a transaction.
     *
     * @param string $transactionId
     * @return JsonResponse
     */
    public function getSettlementStatus(string $transactionId): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $response = $this->transactionService->getSettlementStatus($transactionId, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                return Utility::returnSuccess($response, 'Settlement status retrieved successfully', $response['code']);
            }

            return Utility::returnError($response, $response['message'] ?? 'Failed to retrieve settlement status', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Pay with EasyPaisa wallet.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function payWithEasyPaisa(array $data): JsonResponse
    {
        return $this->payWithWallet($data, 'easypaisa', 'EasyPaisa');
    }

    /**
     * Pay with UPaisa wallet.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function payWithUPaisa(array $data): JsonResponse
    {
        return $this->payWithWallet($data, 'upaisa', 'UPaisa');
    }

    /**
     * Pay with JazzCash wallet.
     *
     * Uses PayFast customer/validate with configurable bank_code / account_type_id,
     * then persists the payment for the subsequent wallet transaction step.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function payWithJazzCash(array $data): JsonResponse
    {
        return $this->payWithWallet($data, 'jazzcash', 'JazzCash');
    }

    /**
     * Shared wallet entrypoint with Form Request + DTO validation.
     *
     * Bank codes come from config/payfast.php → wallets.* (publishable).
     *
     * @param array<string, mixed> $data
     */
    private function payWithWallet(array $data, string $walletKey, string $walletLabel): JsonResponse
    {
        $validator = WalletPaymentRequest::validateArray($data);

        if ($validator->fails()) {
            return Utility::returnError(
                $validator->errors(),
                $validator->errors()->first() ?? "Invalid {$walletLabel} wallet payment data",
                'VALIDATION_ERROR',
                Response::HTTP_BAD_REQUEST
            );
        }

        $defaults = match ($walletKey) {
            'jazzcash' => WalletPaymentRequestDTO::BANK_CODE_JAZZCASH,
            'upaisa' => WalletPaymentRequestDTO::BANK_CODE_UPAISA,
            default => WalletPaymentRequestDTO::BANK_CODE_EASYPAISA,
        };

        $bankCode = (int) config("payfast.wallets.{$walletKey}.bank_code", $defaults);
        $accountTypeId = (int) config(
            "payfast.wallets.{$walletKey}.account_type_id",
            WalletPaymentRequestDTO::ACCOUNT_TYPE_WALLET
        );

        $validated = $validator->validated();
        $validated['account_type_id'] = $validated['account_type_id'] ?? $accountTypeId;

        $dto = WalletPaymentRequestDTO::fromArray($validated, $bankCode, $accountTypeId);

        return $this->validateWalletTransaction($dto->toArray());
    }

    /**
     * Validate wallet transaction.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function validateWalletTransaction(array $data): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $dto = WalletPaymentRequestDTO::fromArray($data);
            if ($dto->bankCode <= 0 || !$dto->isValid()) {
                return Utility::returnError([], 'A valid bank_code and wallet payload are required', 'VALIDATION_ERROR');
            }

            $response = $this->paymentService->validateWalletTransaction($dto, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                $payload = $dto->toArray();
                $payload['token'] = $this->authToken;
                $payload['transaction_id'] = $response['transaction_id'] ?? '';

                $paymentMethod = $this->resolveWalletPaymentMethod($dto->bankCode);

                $paymentData = [
                    'token' => $this->authToken,
                    'orderNo' => $dto->orderNumber,
                    'data_3ds_secureid' => $response['data_3ds_secureid'] ?? '',
                    'transaction_id' => $response['transaction_id'] ?? '',
                    'status' => ProcessPayment::STATUS_VALIDATED,
                    'payment_method' => $paymentMethod,
                    'payload' => json_encode([
                        'customer_validate' => $response,
                        'user_request' => $payload,
                    ]),
                    'requestData' => json_encode($payload),
                ];

                $payment = $this->paymentRepository->create($paymentData);

                Event::dispatch(new PaymentValidated($payload, $response));

                // For wallet payments, return payment info for OTP / wallet initiate step
                return Utility::returnSuccess([
                    'transaction_id' => $response['transaction_id'] ?? '',
                    'payment_id' => $payment->id,
                    'payment_method' => $paymentMethod,
                    'redirect_url' => $response['redirect_url'] ?? null,
                ], 'Wallet transaction validated successfully', '00');
            }

            return Utility::returnError($response, $response['message'] ?? 'Wallet validation failed', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Initiate wallet transaction.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function walletTransactionInitiate(array $data): JsonResponse
    {
        try {
            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $dto = WalletPaymentRequestDTO::fromArray($data);
            $response = $this->paymentService->initiateWalletTransaction($dto, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                Event::dispatch(new PaymentCompleted($data, $response));
                return Utility::returnSuccess($response, 'Wallet transaction initiated successfully', '00');
            }

            Event::dispatch(new PaymentFailed($data, $response['code'] ?? 'UNKNOWN', $response['message'] ?? ''));
            return Utility::returnError($response, $response['message'] ?? 'Failed to initiate wallet transaction', $response['code'] ?? '');
        } catch (\Exception $e) {
            Event::dispatch(new PaymentFailed($data, 'EXCEPTION', $e->getMessage()));
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Initiate a transaction.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function initiateTransaction(array $data): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            Event::dispatch(new PaymentInitiated($data, []));

            $dto = PaymentRequestDTO::fromArray($data);
            $response = $this->paymentService->initiateTransaction($dto, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                Event::dispatch(new PaymentCompleted($data, $response));

                return Utility::returnSuccess($response, 'Transaction initiated successfully', '00');
            }

            Event::dispatch(new PaymentFailed($data, $response['code'] ?? 'UNKNOWN', $response['message'] ?? ''));

            return Utility::returnError($response, $response['message'] ?? 'Failed to initiate transaction', $response['code'] ?? '');
        } catch (\Exception $e) {
            Event::dispatch(new PaymentFailed($data, 'EXCEPTION', $e->getMessage()));

            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Create a new subscription.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function createSubscription(array $data): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $dto = new \zfhassaan\Payfast\DTOs\SubscriptionRequestDTO(
                $data['orderNumber'] ?? '',
                (float) ($data['transactionAmount'] ?? 0),
                $data['customer_email'] ?? '',
                $data['customerMobileNo'] ?? '',
                $data['planId'] ?? '',
                $data['frequency'] ?? 'monthly',
                $data['iterations'] ?? null
            );

            $response = $this->subscriptionService->createSubscription($dto, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                return Utility::returnSuccess($response, 'Subscription created successfully', '00');
            }

            return Utility::returnError($response, $response['message'] ?? 'Failed to create subscription', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Update an existing subscription.
     *
     * @param string $subscriptionId
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function updateSubscription(string $subscriptionId, array $data): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $response = $this->subscriptionService->updateSubscription($subscriptionId, $data, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                return Utility::returnSuccess($response, 'Subscription updated successfully', '00');
            }

            return Utility::returnError($response, $response['message'] ?? 'Failed to update subscription', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Cancel a subscription.
     *
     * @param string $subscriptionId
     * @return JsonResponse
     */
    public function cancelSubscription(string $subscriptionId): JsonResponse
    {
        try {
            if (!$this->authToken) {
                $tokenResponse = $this->getToken();
                $tokenData = json_decode($tokenResponse->getContent(), true);
                $this->authToken = $tokenData['data']['token'] ?? null;
            }

            if (!$this->authToken) {
                return Utility::returnError([], 'Authentication token is required', 'AUTH_ERROR');
            }

            $response = $this->subscriptionService->cancelSubscription($subscriptionId, $this->authToken);

            if (isset($response['code']) && $response['code'] === '00') {
                return Utility::returnSuccess($response, 'Subscription cancelled successfully', '00');
            }

            return Utility::returnError($response, $response['message'] ?? 'Failed to cancel subscription', $response['code'] ?? '');
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Map wallet bank_code (from config) to ProcessPayment payment_method.
     */
    private function resolveWalletPaymentMethod(int $bankCode): string
    {
        $jazz = (int) config('payfast.wallets.jazzcash.bank_code', WalletPaymentRequestDTO::BANK_CODE_JAZZCASH);
        $easy = (int) config('payfast.wallets.easypaisa.bank_code', WalletPaymentRequestDTO::BANK_CODE_EASYPAISA);
        $up = (int) config('payfast.wallets.upaisa.bank_code', WalletPaymentRequestDTO::BANK_CODE_UPAISA);

        return match ($bankCode) {
            $jazz => ProcessPayment::METHOD_JAZZCASH,
            $up => ProcessPayment::METHOD_UPAISA,
            $easy => ProcessPayment::METHOD_EASYPAISA,
            default => ProcessPayment::METHOD_EASYPAISA,
        };
    }

    /**
     * Get current authentication token.
     *
     * @return string|null
     */
    public function getAuthToken(): ?string
    {
        return $this->authToken;
    }

    /**
     * Set authentication token.
     *
     * @param string $token
     * @return void
     */
    public function setAuthToken(string $token): void
    {
        $this->authToken = $token;
    }

    /**
     * Verify OTP and store pares in database.
     *
     * @param string $transactionId
     * @param string $otp
     * @param string $pares
     * @return JsonResponse
     */
    public function verifyOTPAndStorePares(string $transactionId, string $otp, string $pares): JsonResponse
    {
        try {
            $result = $this->otpVerificationService->verifyOTPAndStorePares($transactionId, $otp, $pares);

            if ($result['status']) {
                return Utility::returnSuccess($result['data'], 'OTP verified and pares stored successfully', $result['code']);
            }

            return Utility::returnError($result['data'] ?? [], $result['message'], $result['code']);
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Complete transaction from callback using pares.
     *
     * @param string $pares
     * @return JsonResponse
     */
    public function completeTransactionFromPares(string $pares): JsonResponse
    {
        try {
            $result = $this->otpVerificationService->completeTransactionFromPares($pares);

            if ($result['status']) {
                return Utility::returnSuccess($result['data'], 'Transaction completed successfully', $result['code']);
            }

            return Utility::returnError($result['data'] ?? [], $result['message'], $result['code']);
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage());
        }
    }

    /**
     * Handle IPN (Instant Payment Notification) webhook from PayFast.
     *
     * @param array<string, mixed> $data
     * @return JsonResponse
     */
    public function handleIPN(array $data): JsonResponse
    {
        try {
            $result = $this->ipnService->processIPN($data);

            if ($result['status']) {
                return Utility::returnSuccess(
                    $result['data'] ?? [],
                    $result['message'] ?? 'IPN processed successfully',
                    $result['code'] ?? '00'
                );
            }

            return Utility::returnError(
                $result['data'] ?? [],
                $result['message'] ?? 'Failed to process IPN',
                $result['code'] ?? 'IPN_ERROR'
            );
        } catch (\Exception $e) {
            return Utility::returnError([], $e->getMessage(), 'IPN_EXCEPTION');
        }
    }
}
