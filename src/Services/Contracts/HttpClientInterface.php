<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services\Contracts;

interface HttpClientInterface
{
    /**
     * Perform a GET request.
     *
     * @param string $url
     * @param array<string, string> $headers
     * @return mixed
     */
    public function get(string $url, array $headers = []): mixed;

    /**
     * Perform a POST request.
     *
     * @param string $url
     * @param string|array<string, mixed> $data
     * @param array<string, string> $headers
     * @return mixed
     */
    public function post(string $url, string|array $data, array $headers = []): mixed;
}

