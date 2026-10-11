<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Http\Requests;

/**
 * Validates EasyPaisa / UPaisa / JazzCash wallet payloads.
 */
class WalletPaymentRequest extends PayfastFormRequest
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        return array_merge($data, [
            'basket_id' => $data['basket_id'] ?? $data['orderNumber'] ?? $data['orderNo'] ?? null,
            'txnamt' => $data['txnamt'] ?? $data['transactionAmount'] ?? null,
            'customer_mobile_no' => $data['customer_mobile_no'] ?? $data['customerMobileNo'] ?? null,
            'customer_email_address' => $data['customer_email_address'] ?? $data['customer_email'] ?? null,
            'bank_code' => $data['bank_code'] ?? null,
            'account_type_id' => $data['account_type_id'] ?? null,
            'order_date' => $data['order_date'] ?? null,
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(static::normalize($this->all()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'basket_id' => ['required', 'string'],
            'txnamt' => ['required', 'numeric', 'gt:0'],
            'customer_mobile_no' => ['required', 'string'],
            'customer_email_address' => ['nullable', 'email'],
            'bank_code' => ['nullable', 'integer'],
            'account_type_id' => ['nullable', 'integer'],
            'order_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'basket_id.required' => 'Basket / order id is required for wallet payments',
            'txnamt.required' => 'Transaction amount is required for wallet payments',
            'txnamt.gt' => 'Transaction amount must be greater than zero',
            'customer_mobile_no.required' => 'Customer mobile number is required for wallet payments',
            'customer_email_address.email' => 'Customer email address must be valid',
        ];
    }
}
