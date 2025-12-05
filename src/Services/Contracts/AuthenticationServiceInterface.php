<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services\Contracts;

interface AuthenticationServiceInterface
{
    /**
     * Get authentication token.
     *
     * @return array<string, mixed>
     */
    public function getToken(): array;

    /**
     * Refresh authentication token.
     *
     * @param string $token
     * @param string $refreshToken
     * @return array<string, mixed>
     */
    public function refreshToken(string $token, string $refreshToken): array;
}


