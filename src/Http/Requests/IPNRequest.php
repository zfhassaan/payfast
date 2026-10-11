<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use zfhassaan\Payfast\Http\Middleware\LogIncomingIpn;
use zfhassaan\Payfast\Services\Contracts\IPNServiceInterface;

/**
 * Validates incoming PayFast IPN / webhook payloads.
 */
class IPNRequest extends PayfastFormRequest
{
    protected function failedValidation(ValidatorContract $validator): void
    {
        $logId = $this->attributes->get(LogIncomingIpn::REQUEST_ATTR_LOG_ID);
        try {
            app(IPNServiceInterface::class)->markRejected(
                is_numeric($logId) ? (int) $logId : null,
                'Form request validation failed',
                ['errors' => $validator->errors()->toArray()]
            );
        } catch (\Throwable) {
            // ignore
        }

        parent::failedValidation($validator);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transaction_id' => ['nullable', 'string'],
            'TRANSACTION_ID' => ['nullable', 'string'],
            'order_no' => ['nullable', 'string'],
            'ORDER_NO' => ['nullable', 'string'],
            'basket_id' => ['nullable', 'string'],
            'BASKET_ID' => ['nullable', 'string'],
            'status' => ['nullable'],
            'STATUS' => ['nullable'],
            'code' => ['nullable'],
            'CODE' => ['nullable'],
            'amount' => ['nullable', 'numeric'],
            'AMOUNT' => ['nullable', 'numeric'],
            'txnamt' => ['nullable', 'numeric'],
            'TXNAMT' => ['nullable', 'numeric'],
            'currency' => ['nullable', 'string'],
            'CURRENCY' => ['nullable', 'string'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function validateArray(array $data): ValidatorContract
    {
        $instance = new static();
        $validator = Validator::make(
            static::normalize($data),
            $instance->rules(),
            $instance->messages(),
            $instance->attributes()
        );

        $validator->after(function (ValidatorContract $validator): void {
            static::assertHasIdentifier($validator);
        });

        return $validator;
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            static::assertHasIdentifier($validator);
        });
    }

    private static function assertHasIdentifier(ValidatorContract $validator): void
    {
        $data = $validator->getData();

        $hasTransactionId = !empty($data['transaction_id']) || !empty($data['TRANSACTION_ID']);
        $hasOrderNo = !empty($data['order_no'])
            || !empty($data['ORDER_NO'])
            || !empty($data['basket_id'])
            || !empty($data['BASKET_ID']);

        if (!$hasTransactionId && !$hasOrderNo) {
            $validator->errors()->add(
                'transaction_id',
                'IPN payload requires a transaction_id or order/basket identifier.'
            );
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_id.required' => 'IPN payload requires a transaction_id or order/basket identifier.',
        ];
    }
}
