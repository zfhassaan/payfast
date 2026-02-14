<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\DTOs;

class SubscriptionRequestDTO
{
    /**
     * @param string $orderNumber
     * @param float $amount
     * @param string $customerEmail
     * @param string $customerMobile
     * @param string $planId
     * @param string|null $frequency (daily, weekly, monthly, yearly)
     * @param int|null $iterations
     */
    public function __construct(
        public readonly string $orderNumber,
        public readonly float $amount,
        public readonly string $customerEmail,
        public readonly string $customerMobile,
        public readonly string $planId,
        public readonly ?string $frequency = 'monthly',
        public readonly ?int $iterations = null
    ) {
    }

    /**
     * Convert DTO to array for API request.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_no' => $this->orderNumber,
            'amount' => $this->amount,
            'customer_email' => $this->customerEmail,
            'customer_mobile' => $this->customerMobile,
            'plan_id' => $this->planId,
            'frequency' => $this->frequency,
            'iterations' => $this->iterations,
        ];
    }
}
