<?php

namespace zfhassaan\Payfast\Helpers;

use Illuminate\Support\Facades\Http;

class ConfigLoader
{
    protected string $api_url;
    public string $merchant_id;
    public string $refresh_token;
    public string $secured_key;
    public string $grant_type;
    public string $handshake;
    public string $refreshToken;
    public string $customer_validation;
    public string $api_mode;
    public string $return_url;
    public string $authToken;

    protected string $basket_id;
    protected string $txnamt;
    protected string $customer_mobile_no;
    protected string $customer_email_address;
    protected string $account_type_id;
    protected string $card_number;
    protected string $expiry_month;
    protected string $expiry_year;
    protected string $cvv;
    protected string $order_date;
    protected string $data_3ds_callback_url;
    protected string $store_id;
    protected string $currency_code;
    protected string $account_title;
    protected string $transaction_id;
    protected string $data_3ds_secureId;
    protected string $data_3ds_pares;
    protected string $paRes;

    private string $_doTranUrl;
    private string $_proTranUrl;
    protected string $ip;

    public function load(): array
    {
        // Directly set class properties from configuration
        $this->api_mode = config('payfast.mode');
        $this->merchant_id = config('payfast.merchant_id');
        $this->store_id = config('payfast.store_id');
        $this->return_url = config('payfast.return_url');
        $this->secured_key = config('payfast.secured_key');
        $this->grant_type = config('payfast.grant_type');
        $this->ip = request()->ip();

        // Decide on API URL based on mode
        $apiUrl = $this->api_mode === 'sandbox' ? config('payfast.sandbox_api_url') : config('payfast.api_url');
        $this->setApiUrl($apiUrl);

        // Construct and return an options array with structured key-value pairs
        $options = [
            'api_mode' => $this->api_mode,
            'api_url' => $this->getApiUrl(), 
            'merchant_id' => $this->merchant_id,
            'store_id' => $this->store_id,
            'return_url' => $this->return_url,
            'secured_key' => $this->secured_key,
            'grant_type' => $this->grant_type,
            'ip' => $this->ip,
        ];
        return $options;
    }


    /**
     * Default Setter and Getter for all the variables.
     *
     * @param $method
     * @param $args
     * @return mixed|null
     */
    public function __call($method, $args) {
        $property = lcfirst(substr($method,3));
        if(property_exists($this,$property)) {
            if(str_starts_with($method, 'get')) {
                return $this->$property;
            } elseif(str_starts_with($method, 'set')) {
                return $this->$property = $args[0] ?? null;
            }
        }
        return null;
    }
    public function __get($name) {
        if(property_exists($this, $name)) {
            return $this->$name;
        }
        return Utility::returnError([], "Property ${name} does not exist.");
    }

    public function __set($name, $value) {
        if(property_exists($this, $name)) {
            $this->$name = $value;
            return null;
        }
        return Utility::returnError([], "Property ${name} does not exist.");
    }

    public function setApiUrl($api_url): void
    {
        $this->api_url = $api_url;
    }

    public function getApiUrl(): string
    {
        return $this->api_url;
    }


    public function setHandShake($data) {
        return $this->handshake = $data;
    }

    public function getHandShake()
    {
        return $this->handshake;
    }



    public function PayfastGet($url): bool|string
    {
        $uri = self::getApiUrl() . $url;
        $this->setHandShake(json_decode($this->getToken()->getContent())->data);
        $this->setAuthToken(($handshake = $this->getHandShake()) ? $handshake->token : false);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded",
                'Authorization: Bearer '.$this->getHandShake()->token,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }


    /**
     * This passes the url param as string and also fields with authToken and Post Fields in
     * http_post_fields() format.
     *
     * @param String $url
     */
    public function PayfastPost(string $url, $fields): bool|string
    {
        $uri = self::getApiUrl() . $url;
        Utility::LogData('Payfast','Payfast POST URL 158', $uri);
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.self::getAuthToken()
        ];
        Utility::LogData('Payfast','Payfast POST Headers',$headers);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $uri,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        Utility::LogData('Payfast','Payfast Response ',$response);
        curl_close($curl);
        return $response;
    }




}
