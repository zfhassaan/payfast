<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;

class HttpClientService implements HttpClientInterface
{
    /**
     * Perform a GET request.
     *
     * @param string $url
     * @param array<string, string> $headers
     * @return mixed
     */
    public function get(string $url, array $headers = []): mixed
    {
        $curl = curl_init();
        $formattedHeaders = $this->formatHeaders($headers);
        $userPwd = null;

        // Extract basic auth if present
        foreach ($headers as $key => $value) {
            if (str_starts_with((string) $value, 'Basic ')) {
                $userPwd = base64_decode(substr((string) $value, 6));
                // Remove from headers array
                unset($formattedHeaders[array_search($value, $formattedHeaders, true)]);
            }
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $formattedHeaders,
        ];

        if ($userPwd) {
            $options[CURLOPT_USERPWD] = $userPwd;
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Perform a POST request.
     *
     * @param string $url
     * @param string|array<string, mixed> $data
     * @param array<string, string> $headers
     * @return mixed
     */
    public function post(string $url, string|array $data, array $headers = []): mixed
    {
        $postFields = is_array($data) ? http_build_query($data) : $data;

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($response === false) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Format headers array for cURL.
     *
     * @param array<string, string> $headers
     * @return array<string>
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            if (str_contains((string) $value, ':')) {
                $formatted[] = (string) $value;
            } else {
                $formatted[] = "$key: $value";
            }
        }

        return $formatted;
    }
}

