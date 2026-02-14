<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

use zfhassaan\Payfast\DTOs\SubscriptionRequestDTO;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;
use zfhassaan\Payfast\Services\Contracts\SubscriptionServiceInterface;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private readonly ConfigService $configService,
        private readonly HttpClientInterface $httpClient
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createSubscription(SubscriptionRequestDTO $dto, string $authToken): array
    {
        $url = $this->configService->getApiUrl() . 'subscription/create';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        return $this->httpClient->post($url, $dto->toArray(), $headers) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function updateSubscription(string $subscriptionId, array $data, string $authToken): array
    {
        $url = $this->configService->getApiUrl() . 'subscription/update/' . $subscriptionId;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        return $this->httpClient->post($url, $data, $headers) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function cancelSubscription(string $subscriptionId, string $authToken): array
    {
        $url = $this->configService->getApiUrl() . 'subscription/cancel/' . $subscriptionId;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $authToken,
        ];

        return $this->httpClient->post($url, [], $headers) ?? [];
    }
}
