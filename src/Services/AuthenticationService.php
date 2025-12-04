<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

use zfhassaan\Payfast\Services\Contracts\AuthenticationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;

class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ConfigService $configService
    ) {
    }

    /**
     * Get authentication token.
     *
     * @return array<string, mixed>
     */
    public function getToken(): array
    {
        $options = [
            'grant_type' => $this->configService->getGrantType(),
            'merchant_id' => $this->configService->getMerchantId(),
            'secured_key' => $this->configService->getSecuredKey(),
            'customer_ip' => request()->ip(),
        ];

        $url = $this->configService->getApiUrl() . 'token';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $response = $this->httpClient->post($url, $options, $headers);

        // Handle both array and object responses
        if (is_array($response)) {
            return $response;
        }

        // If response is a string (JSON), decode it
        if (is_string($response)) {
            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Refresh authentication token.
     *
     * @param string $token
     * @param string $refreshToken
     * @return array<string, mixed>
     */
    public function refreshToken(string $token, string $refreshToken): array
    {
        $postFields = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        $url = $this->configService->getApiUrl() . 'refreshtoken';
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $token,
        ];

        $response = $this->httpClient->post($url, $postFields, $headers);

        // Handle both array and object responses
        if (is_array($response)) {
            return $response;
        }

        // If response is a string (JSON), decode it
        if (is_string($response)) {
            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}

