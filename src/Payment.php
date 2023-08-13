<?php

namespace zfhassaan\Payfast;

use Illuminate\Support\Facades\Http;
use zfhassaan\Payfast\helper\Utility;

class Payment {

    protected $apiUrl;
    public $merchant_id;
    public string $refresh_token;
    public $secured_key;
    public $grant_type;
    public $handshake;
    public $refreshToken;
    public $customer_validation;
    public $api_mode;
    public string $return_url;
    public $authToken;

    protected $basket_id;
    protected $txnamt;
    protected $customer_mobile_no;
    protected $customer_email_address;
    protected $account_type_id;
    protected $card_number;
    protected $expiry_month;
    protected $expiry_year;
    protected $cvv;
    protected $order_date;
    protected $data_3ds_callback_url;
    protected $store_id;
    protected $currency_code;
    protected $account_title;
    protected $transaction_id;
    protected $data_3ds_secureId;
    protected $data_3ds_pares;
    protected $paRes;

    private $_doTranUrl;
    private $_proTranUrl;
    protected $ip;


    public function __construct()
    {
        $this->initConfig();
    }

    protected function initConfig()
    {
        $this->api_mode = config('payfast.mode');
        $this->api_mode === 'sandbox' ? $this->setApiUrl(config('payfast.sandbox_api_url')) : $this->setApiUrl(config('payfast.api_url'));
        $this->merchant_id = config('payfast.merchant_id');
        $this->store_id = config('payfast.store_id');
        $this->return_url = config('payfast.return_url');
        $this->secured_key = config('payfast.secured_key');
        $this->grant_type = config('payfast.grant_type');
        $this->ip = $_SERVER['REMOTE_ADDR'];
    }

    public function setHandShake($data) {
        return $this->handshake = $data;
    }

    public function getHandShake()
    {
        return $this->handshake;
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    public function getAuthToken()
    {
        return $this->authToken;
    }

    public function setAuthToken($authToken)
    {
        $this->authToken = $authToken;
    }

    public function setRefreshToken($token){
        $this->refreshToken = $token;
    }

    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    public function __call($method, $args) {
        $property = lcfirst(substr($method,3));
        if(property_exists($this,$property)) {
            if(strpos($method,'get') === 0) {
                return $this->$property;
            } elseif(strpos($method,'set') === 0) {
                $this->$property = $args[0] ?? null;
            }
        }
    }

    public function getPayfastToken($fields) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getApiUrl().'token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return $this->setHandShake(json_decode(curl_exec($ch)));
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
