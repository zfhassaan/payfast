<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use zfhassaan\Payfast\Models\ProcessPayment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\zfhassaan\Payfast\Models\ProcessPayment>
 */
class ProcessPaymentFactory extends Factory
{
    protected $model = ProcessPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => \Illuminate\Support\Str::uuid(),
            'token' => 'test_token_' . $this->faker->uuid(),
            'orderNo' => 'ORD-' . $this->faker->unique()->numerify('#####'),
            'data_3ds_secureid' => '3DS-' . $this->faker->uuid(),
            'data_3ds_pares' => null,
            'transaction_id' => 'TXN-' . $this->faker->unique()->numerify('#####'),
            'status' => ProcessPayment::STATUS_PENDING,
            'payment_method' => ProcessPayment::METHOD_CARD,
            'payload' => json_encode([
                'customer_validate' => [],
                'user_request' => [],
            ]),
            'requestData' => json_encode([
                'orderNumber' => 'ORD-123',
                'transactionAmount' => 1000.00,
            ]),
        ];
    }

    /**
     * Indicate that the payment is validated.
     *
     * @return static
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProcessPayment::STATUS_VALIDATED,
        ]);
    }

    /**
     * Indicate that the payment OTP is verified.
     *
     * @return static
     */
    public function otpVerified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProcessPayment::STATUS_OTP_VERIFIED,
            'data_3ds_pares' => 'pares_' . $this->faker->uuid(),
            'otp_verified_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment is completed.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProcessPayment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}


