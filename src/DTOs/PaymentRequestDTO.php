<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\DTOs;

class PaymentRequestDTO
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly float $transactionAmount,
        public readonly string $customerMobileNo,
        public readonly string $customerEmail,
        public readonly string $cardNumber,
        public readonly string $expiryMonth,
        public readonly string $expiryYear,
        public readonly string $cvv,
        public readonly ?string $userId = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $data3dsPares = null,
        public readonly ?string $data3dsSecureId = null,
    ) {
    }

    /**
     * Create from array.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderNumber: (string) ($data['orderNumber'] ?? $data['basket_id'] ?? $data['orderNo'] ?? ''),
            transactionAmount: (float) ($data['transactionAmount'] ?? $data['txnamt'] ?? 0),
            customerMobileNo: (string) ($data['customerMobileNo'] ?? $data['customer_mobile_no'] ?? ''),
            customerEmail: (string) ($data['customer_email'] ?? $data['customer_email_address'] ?? ''),
            cardNumber: (string) ($data['cardNumber'] ?? $data['card_number'] ?? ''),
            expiryMonth: (string) ($data['expiry_month'] ?? ''),
            expiryYear: (string) ($data['expiry_year'] ?? ''),
            cvv: (string) ($data['cvv'] ?? ''),
            userId: isset($data['user_id']) ? (string) $data['user_id'] : null,
            transactionId: isset($data['transaction_id']) ? (string) $data['transaction_id'] : null,
            data3dsPares: isset($data['data_3ds_pares']) ? (string) $data['data_3ds_pares'] : null,
            data3dsSecureId: isset($data['data_3ds_secureid']) ? (string) $data['data_3ds_secureid'] : null,
        );
    }

    /**
     * Convert to array for API request.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'basket_id' => $this->orderNumber,
            'txnamt' => $this->transactionAmount,
            'customer_mobile_no' => $this->customerMobileNo,
            'customer_email_address' => $this->customerEmail,
            'card_number' => $this->cardNumber,
            'expiry_month' => $this->expiryMonth,
            'expiry_year' => $this->expiryYear,
            'cvv' => $this->cvv,
        ];
    }
}

