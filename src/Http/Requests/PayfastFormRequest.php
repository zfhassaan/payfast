<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Http\Requests;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use zfhassaan\Payfast\Helpers\Utility;

/**
 * Base Form Request for package HTTP endpoints and programmatic array validation.
 */
abstract class PayfastFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        throw new HttpResponseException(
            Utility::returnError(
                $validator->errors(),
                $validator->errors()->first() ?? 'Validation failed',
                'VALIDATION_ERROR',
                Response::HTTP_BAD_REQUEST
            )
        );
    }

    /**
     * Normalize alternate PayFast field names before validation.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        return $data;
    }

    /**
     * Validate a raw array using this Form Request's rules (facade / service usage).
     *
     * @param array<string, mixed> $data
     */
    public static function validateArray(array $data): ValidatorContract
    {
        $instance = new static();

        return Validator::make(
            static::normalize($data),
            $instance->rules(),
            $instance->messages(),
            $instance->attributes()
        );
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }
}
