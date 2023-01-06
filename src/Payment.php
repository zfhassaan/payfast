<?php

namespace zfhassaan\Payfast\Payment;

class Payment {

    protected $apiUrl;
    public $merchant_id;
    public $secured_key;
    public $grant_type;
    public $handshake;
    public $refreshToken;
    public $customer_validation;
    public $api_mode;
    public $return_url;

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
}
