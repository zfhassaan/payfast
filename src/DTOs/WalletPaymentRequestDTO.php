<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\DTOs;

use Illuminate\Support\Carbon;

class WalletPaymentRequestDTO
{
    public const BANK_CODE_JAZZCASH = 1;
    public const BANK_CODE_EASYPAISA = 13;
    public const BANK_CODE_UPAISA = 14;
    public const ACCOUNT_TYPE_WALLET = 4;

    public function __construct(
        public readonly string $orderNumber,
        public readonly float $transactionAmount,
        public readonly string $customerMobileNo,
        public readonly string $customerEmail,
        public readonly int $bankCode,
        public readonly int $accountTypeId = self::ACCOUNT_TYPE_WALLET,
        public readonly ?string $orderDate = null,
        public readonly ?string $transactionId = null,
    ) {
    }

    /**
     * Create from array, applying wallet bank code defaults.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(
        array $data,
        ?int $bankCode = null,
        int $accountTypeId = self::ACCOUNT_TYPE_WALLET
    ): self {
        $resolvedBankCode = (int) ($bankCode ?? $data['bank_code'] ?? 0);

        return new self(
            orderNumber: (string) ($data['orderNumber'] ?? $data['basket_id'] ?? $data['orderNo'] ?? ''),
            transactionAmount: (float) ($data['transactionAmount'] ?? $data['txnamt'] ?? 0),
            customerMobileNo: (string) ($data['customerMobileNo'] ?? $data['customer_mobile_no'] ?? ''),
            customerEmail: (string) ($data['customer_email'] ?? $data['customer_email_address'] ?? ''),
            bankCode: $resolvedBankCode,
            accountTypeId: (int) ($data['account_type_id'] ?? $accountTypeId),
            orderDate: isset($data['order_date'])
                ? (string) $data['order_date']
                : Carbon::today()->toDateString(),
            transactionId: isset($data['transaction_id']) ? (string) $data['transaction_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'basket_id' => $this->orderNumber,
            'txnamt' => $this->transactionAmount,
            'customer_mobile_no' => $this->customerMobileNo,
            'customer_email_address' => $this->customerEmail,
            'bank_code' => $this->bankCode,
            'account_type_id' => $this->accountTypeId,
            'order_date' => $this->orderDate ?? Carbon::today()->toDateString(),
        ];

        if ($this->transactionId !== null && $this->transactionId !== '') {
            $payload['transaction_id'] = $this->transactionId;
        }

        return $payload;
    }

    public function isValid(): bool
    {
        return $this->orderNumber !== ''
            && $this->transactionAmount > 0
            && $this->customerMobileNo !== ''
            && $this->bankCode > 0;
    }
}
