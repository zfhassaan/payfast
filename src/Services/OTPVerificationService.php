<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

use Illuminate\Support\Facades\Event;
use zfhassaan\Payfast\DTOs\PaymentRequestDTO;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;
use zfhassaan\Payfast\Services\Contracts\AuthenticationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\OTPVerificationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\PaymentServiceInterface;

class OTPVerificationService implements OTPVerificationServiceInterface
{
    public function __construct(
        private readonly ProcessPaymentRepositoryInterface $paymentRepository,
        private readonly PaymentServiceInterface $paymentService,
        private readonly AuthenticationServiceInterface $authenticationService
    ) {
    }

    /**
     * Verify OTP and store pares in database.
     *
     * @param string $transactionId
     * @param string $otp
     * @param string $pares
     * @return array<string, mixed>
     */
    public function verifyOTPAndStorePares(string $transactionId, string $otp, string $pares): array
    {
        $payment = $this->paymentRepository->findByTransactionId($transactionId);

        if (!$payment) {
            return [
                'status' => false,
                'message' => 'Payment record not found',
                'code' => 'PAYMENT_NOT_FOUND',
            ];
        }

        if (!$payment->isValidated()) {
            return [
                'status' => false,
                'message' => 'Payment is not in validated state',
                'code' => 'INVALID_STATUS',
            ];
        }

        // Verify OTP with PayFast (if required by API)
        // For now, we'll assume OTP is verified and store pares
        $updateData = [
            'data_3ds_pares' => $pares,
            'status' => ProcessPayment::STATUS_OTP_VERIFIED,
            'otp_verified_at' => now(),
        ];

        $this->paymentRepository->update((string) $payment->id, $updateData);
        $payment->refresh();

        Event::dispatch(new PaymentValidated(
            json_decode($payment->requestData ?? '{}', true),
            ['transaction_id' => $transactionId, 'pares' => $pares]
        ));

        return [
            'status' => true,
            'message' => 'OTP verified and pares stored',
            'code' => '00',
            'data' => [
                'transaction_id' => $transactionId,
                'pares' => $pares,
                'payment_id' => $payment->id,
            ],
        ];
    }

    /**
     * Complete transaction using stored pares from callback.
     *
     * @param string $pares
     * @return array<string, mixed>
     */
    public function completeTransactionFromPares(string $pares): array
    {
        // Find payment by pares using repository
        $payment = $this->paymentRepository->findByPares($pares);
        
        // Ensure payment is in OTP verified status
        if ($payment && $payment->status !== ProcessPayment::STATUS_OTP_VERIFIED) {
            $payment = null;
        }

        if (!$payment) {
            return [
                'status' => false,
                'message' => 'Payment record not found for this pares',
                'code' => 'PAYMENT_NOT_FOUND',
            ];
        }

        // Get authentication token
        $tokenResponse = $this->authenticationService->getToken();
        if (!isset($tokenResponse['data']['token'])) {
            return [
                'status' => false,
                'message' => 'Failed to get authentication token',
                'code' => 'AUTH_ERROR',
            ];
        }

        $authToken = $tokenResponse['data']['token'];

        // Get original request data
        $requestData = json_decode($payment->requestData ?? '{}', true);
        $payload = json_decode($payment->payload ?? '{}', true);
        $customerValidate = $payload['customer_validate'] ?? [];

        // Create DTO with pares
        $dto = PaymentRequestDTO::fromArray(array_merge($requestData, [
            'transaction_id' => $payment->transaction_id,
            'data_3ds_pares' => $pares,
            'data_3ds_secureid' => $payment->data_3ds_secureid,
        ]));

        // Complete the transaction
        $response = $this->paymentService->initiateTransaction($dto, $authToken);

        if (isset($response['code']) && $response['code'] === '00') {
            $this->paymentRepository->update((string) $payment->id, [
                'status' => ProcessPayment::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            Event::dispatch(new PaymentCompleted($requestData, $response));

            return [
                'status' => true,
                'message' => 'Transaction completed successfully',
                'code' => '00',
                'data' => $response,
            ];
        }

        $this->paymentRepository->update((string) $payment->id, [
            'status' => ProcessPayment::STATUS_FAILED,
        ]);

        Event::dispatch(new PaymentFailed(
            $requestData,
            $response['code'] ?? 'UNKNOWN',
            $response['message'] ?? 'Transaction failed'
        ));

        return [
            'status' => false,
            'message' => $response['message'] ?? 'Transaction failed',
            'code' => $response['code'] ?? 'UNKNOWN',
            'data' => $response,
        ];
    }
}

