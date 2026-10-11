<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

use Illuminate\Support\Carbon;
use zfhassaan\Payfast\DTOs\PaymentRequestDTO;
use zfhassaan\Payfast\DTOs\WalletPaymentRequestDTO;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;
use zfhassaan\Payfast\Services\Contracts\PaymentServiceInterface;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ConfigService $configService
    ) {
    }

    /**
     * Validate customer and get OTP screen.
     *
     * @return array<string, mixed>
     */
    public function validateCustomer(PaymentRequestDTO $dto): array
    {
        $postFields = array_merge($dto->toArray(), [
            'account_type_id' => '1',
            'order_date' => Carbon::today()->toDateString(),
            'data_3ds_callback_url' => $this->configService->getReturnUrl(),
            'checkout_url' => $this->configService->getCheckoutUrl(),
            'currency_code' => 'PKR',
        ]);

        $url = $this->configService->getApiUrl() . 'customer/validate';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $response = $this->httpClient->post($url, $postFields, $headers);

        return $response ?? [];
    }

    /**
     * Initiate a transaction.
     *
     * @return array<string, mixed>
     */
    public function initiateTransaction(PaymentRequestDTO $dto, string $authToken): array
    {
        $postFields = [
            'user_id' => $dto->userId ?? '',
            'basket_id' => $dto->orderNumber,
            'txnamt' => $dto->transactionAmount,
            'customer_mobile_no' => $dto->customerMobileNo,
            'customer_email_address' => $dto->customerEmail,
            'order_date' => Carbon::today()->toDateString(),
            'checkout_url' => $this->configService->getCheckoutUrl(),
            'transaction_id' => $dto->transactionId ?? '',
            'card_number' => $dto->cardNumber,
            'expiry_year' => $dto->expiryYear,
            'expiry_month' => $dto->expiryMonth,
            'cvv' => $dto->cvv,
            'data_3ds_pares' => $dto->data3dsPares ?? '',
            'data_3ds_secureid' => $dto->data3dsSecureId ?? '',
        ];

        $url = $this->configService->getApiUrl() . 'transaction';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        $response = $this->httpClient->post($url, $postFields, $headers);

        return $response ?? [];
    }

    /**
     * Validate wallet transaction.
     *
     * @return array<string, mixed>
     */
    public function validateWalletTransaction(
        WalletPaymentRequestDTO $dto,
        ?string $authToken = null
    ): array {
        $data = $dto->toArray();
        $data['account_type_id'] = (string) $dto->accountTypeId;
        $data['bank_code'] = (string) $dto->bankCode;
        $data['checkout_url'] = $this->configService->getCheckoutUrl();

        $url = $this->configService->getApiUrl() . 'customer/validate';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        if ($authToken !== null && $authToken !== '') {
            $headers['Authorization'] = 'Bearer ' . $authToken;
        }

        $response = $this->httpClient->post($url, $data, $headers);

        return $response ?? [];
    }

    /**
     * Initiate wallet transaction.
     *
     * @return array<string, mixed>
     */
    public function initiateWalletTransaction(WalletPaymentRequestDTO $dto, string $authToken): array
    {
        $data = $dto->toArray();
        $data['checkout_url'] = $this->configService->getCheckoutUrl();

        $url = $this->configService->getApiUrl() . 'transaction';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        $response = $this->httpClient->post($url, $data, $headers);

        return $response ?? [];
    }
}
