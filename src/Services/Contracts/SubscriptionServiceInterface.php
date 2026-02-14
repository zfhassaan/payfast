<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services\Contracts;

use zfhassaan\Payfast\DTOs\SubscriptionRequestDTO;

interface SubscriptionServiceInterface
{
    /**
     * Create a new subscription.
     *
     * @param SubscriptionRequestDTO $dto
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function createSubscription(SubscriptionRequestDTO $dto, string $authToken): array;

    /**
     * Update an existing subscription.
     *
     * @param string $subscriptionId
     * @param array<string, mixed> $data
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function updateSubscription(string $subscriptionId, array $data, string $authToken): array;

    /**
     * Cancel a subscription.
     *
     * @param string $subscriptionId
     * @param string $authToken
     * @return array<string, mixed>
     */
    public function cancelSubscription(string $subscriptionId, string $authToken): array;
}
