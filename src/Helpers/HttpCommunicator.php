<?php

namespace zfhassaan\Payfast\Helpers;

use http\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;


class HttpCommunicator {
    protected $client;
    protected String $baseurl;

    public function __construct(){

    }
    // Method to perform GET requests
    public static function get($url, $headers = []) {

    }

    /**
     * This passes the url param as string and also fields with authToken and Post Fields in
     * http_post_fields() format.
     *
     * @param String $token
     * @param String $url
     * @param array $data
     * @param array $headers
     * @return string
     */
    public static function post(String $token, String $url,mixed $data, Array $headers = []): string
    {

        $fieldString = http_build_query($data);

        $response = Http::withHeaders($headers)->post($url, $fieldString);

        return Utility::returnSuccess($response,Response::HTTP_OK);
    }
}
