<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Http\Requests;

/**
 * Validates direct-checkout / card OTP screen payloads.
 */
class CardPaymentRequest extends PayfastFormRequest
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        return array_merge($data, [
            'orderNumber' => $data['orderNumber'] ?? $data['basket_id'] ?? $data['orderNo'] ?? null,
            'transactionAmount' => $data['transactionAmount'] ?? $data['txnamt'] ?? null,
            'customerMobileNo' => $data['customerMobileNo'] ?? $data['customer_mobile_no'] ?? null,
            'customer_email' => $data['customer_email'] ?? $data['customer_email_address'] ?? null,
            'cardNumber' => $data['cardNumber'] ?? $data['card_number'] ?? null,
            'expiry_month' => $data['expiry_month'] ?? null,
            'expiry_year' => $data['expiry_year'] ?? null,
            'cvv' => $data['cvv'] ?? null,
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
            'orderNumber' => ['required', 'string'],
            'transactionAmount' => ['required', 'numeric', 'gt:0'],
            'customerMobileNo' => ['required', 'string'],
            'customer_email' => ['required', 'email'],
            'cardNumber' => ['required', 'string'],
            'expiry_month' => ['required', 'string'],
            'expiry_year' => ['required', 'string'],
            'cvv' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'orderNumber.required' => 'Order Number is Required',
            'transactionAmount.required' => 'Transaction Amount is required',
            'transactionAmount.gt' => 'Transaction Amount must be greater than zero',
            'customerMobileNo.required' => 'Customer Mobile Number is required',
            'customer_email.required' => 'Customer Email address is required',
            'customer_email.email' => 'Customer Email address must be valid',
            'cardNumber.required' => 'Card Number is required',
            'expiry_month.required' => 'Expiry Month is required',
            'expiry_year.required' => 'Expiry Year is required',
            'cvv.required' => 'CVV is a required Field.',
        ];
    }
}
