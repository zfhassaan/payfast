<?php

namespace zfhassaan\Payfast\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Response as JResponse;
use zfhassaan\Payfast\Interfaces\PaymentInterface;


abstract class PayfastService implements PaymentInterface
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
        $this->setApiMode(config('payfast.mode'))
            ->setMerchantId(config('payfast.merchant_id'))
            ->setStoreId(config('payfast.store_id'))
            ->setReturnUrl(config('payfast.return_url'))
            ->setSecuredKey(config('payfast.secured_key'))
            ->setGrantType(config('payfast.grant_type'))
            ->setIp(request()->ip());

        return $this->getOptions();
    }

    // Utility method to construct options array
    private function getOptions(): array
    {
        return [
            'api_mode' => $this->getApiMode(),
            'api_url' => $this->getApiUrl(),
            'merchant_id' => $this->getMerchantId(),
            'store_id' => $this->getStoreId(),
            'return_url' => $this->getReturnUrl(),
            'secured_key' => $this->getSecuredKey(),
            'grant_type' => $this->getGrantType(),
            'ip' => $this->getIp(),
        ];
    }

    public function GetPayfastToken($fields): static
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getApiUrl() . 'token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        return $this->setHandShake($result);
    }

    /**
     * This passes the url param as string and also fields with authToken and Post Fields in
     * http_post_fields() format.
     *
     * @param String $url
     * @param $fields
     * @return mixed
     */

    public function PayfastPost(string $url, $fields): mixed
    {
        $uri = self::getApiUrl() . $url;
//        dd($uri);
        Utility::LogData('Payfast','Payfast POST URL', $uri);
        $headers = [
            'cache-control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.self::getAuthToken()
        ];
        $result = HttpCommunicator::post($this->getAuthToken(), $uri, $fields, $headers);
        return response()->json($result)->getOriginalContent();
    }

    /**
     * @return string
     */
    public function getMerchantId(): string
    {
        return $this->merchant_id;
    }

    /**
     * @param string $merchant_id
     * @return PayfastService
     */
    public function setMerchantId(string $merchant_id): PayfastService
    {
        $this->merchant_id = $merchant_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refresh_token;
    }

    /**
     * @param string $refresh_token
     * @return PayfastService
     */
    public function setRefreshToken(string $refresh_token): PayfastService
    {
        $this->refresh_token = $refresh_token;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecuredKey(): string
    {
        return $this->secured_key;
    }

    /**
     * @param string $secured_key
     * @return PayfastService
     */
    public function setSecuredKey(string $secured_key): PayfastService
    {
        $this->secured_key = $secured_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerValidation(): string
    {
        return $this->customer_validation;
    }

    /**
     * @param string $customer_validation
     * @return PayfastService
     */
    public function setCustomerValidation(string $customer_validation): PayfastService
    {
        $this->customer_validation = $customer_validation;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiMode(): string
    {
        return $this->api_mode;
    }

    public function setApiMode($value): self
    {
        $this->api_mode = $value;
        $apiUrl = $this->api_mode === 'sandbox' ? config('payfast.sandbox_api_url') : config('payfast.api_url');
        $this->setApiUrl($apiUrl);
        return $this;
    }

    /**
     * @return string
     */
    public function getReturnUrl(): string
    {
        return $this->return_url;
    }

    /**
     * @param string $return_url
     * @return PayfastService
     */
    public function setReturnUrl(string $return_url): PayfastService
    {
        $this->return_url = $return_url;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthToken(): string
    {
        return $this->authToken;
    }

    /**
     * @param string $authToken
     * @return PayfastService
     */
    public function setAuthToken(string $authToken): PayfastService
    {
        $this->authToken = $authToken;
        return $this;
    }

    /**
     * @return string
     */
    public function getBasketId(): string
    {
        return $this->basket_id;
    }

    /**
     * @param string $basket_id
     * @return PayfastService
     */
    public function setBasketId(string $basket_id): PayfastService
    {
        $this->basket_id = $basket_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTxnamt(): string
    {
        return $this->txnamt;
    }

    /**
     * @param string $txnamt
     * @return PayfastService
     */
    public function setTxnamt(string $txnamt): PayfastService
    {
        $this->txnamt = $txnamt;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerMobileNo(): string
    {
        return $this->customer_mobile_no;
    }

    /**
     * @param string $customer_mobile_no
     * @return PayfastService
     */
    public function setCustomerMobileNo(string $customer_mobile_no): PayfastService
    {
        $this->customer_mobile_no = $customer_mobile_no;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerEmailAddress(): string
    {
        return $this->customer_email_address;
    }

    /**
     * @param string $customer_email_address
     * @return PayfastService
     */
    public function setCustomerEmailAddress(string $customer_email_address): PayfastService
    {
        $this->customer_email_address = $customer_email_address;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccountTypeId(): string
    {
        return $this->account_type_id;
    }

    /**
     * @param string $account_type_id
     * @return PayfastService
     */
    public function setAccountTypeId(string $account_type_id): PayfastService
    {
        $this->account_type_id = $account_type_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardNumber(): string
    {
        return $this->card_number;
    }

    /**
     * @param string $card_number
     * @return PayfastService
     */
    public function setCardNumber(string $card_number): PayfastService
    {
        $this->card_number = $card_number;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpiryMonth(): string
    {
        return $this->expiry_month;
    }

    /**
     * @param string $expiry_month
     * @return PayfastService
     */
    public function setExpiryMonth(string $expiry_month): PayfastService
    {
        $this->expiry_month = $expiry_month;
        return $this;
    }

    /**
     * @return string
     */
    public function getExpiryYear(): string
    {
        return $this->expiry_year;
    }

    /**
     * @param string $expiry_year
     * @return PayfastService
     */
    public function setExpiryYear(string $expiry_year): PayfastService
    {
        $this->expiry_year = $expiry_year;
        return $this;
    }

    /**
     * @return string
     */
    public function getCvv(): string
    {
        return $this->cvv;
    }

    /**
     * @param string $cvv
     * @return PayfastService
     */
    public function setCvv(string $cvv): PayfastService
    {
        $this->cvv = $cvv;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderDate(): string
    {
        return $this->order_date;
    }

    /**
     * @param string $order_date
     * @return PayfastService
     */
    public function setOrderDate(string $order_date): PayfastService
    {
        $this->order_date = $order_date;
        return $this;
    }

    /**
     * @return string
     */
    public function getData3dsCallbackUrl(): string
    {
        return $this->data_3ds_callback_url;
    }

    /**
     * @param string $data_3ds_callback_url
     * @return PayfastService
     */
    public function setData3dsCallbackUrl(string $data_3ds_callback_url): PayfastService
    {
        $this->data_3ds_callback_url = $data_3ds_callback_url;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreId(): string
    {
        return $this->store_id;
    }

    /**
     * @param string $store_id
     * @return PayfastService
     */
    public function setStoreId(string $store_id): PayfastService
    {
        $this->store_id = $store_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->currency_code;
    }

    /**
     * @param string $currency_code
     * @return PayfastService
     */
    public function setCurrencyCode(string $currency_code): PayfastService
    {
        $this->currency_code = $currency_code;
        return $this;
    }

    /**
     * @return string
     */
    public function getAccountTitle(): string
    {
        return $this->account_title;
    }

    /**
     * @param string $account_title
     * @return PayfastService
     */
    public function setAccountTitle(string $account_title): PayfastService
    {
        $this->account_title = $account_title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transaction_id;
    }

    /**
     * @param string $transaction_id
     * @return PayfastService
     */
    public function setTransactionId(string $transaction_id): PayfastService
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getData3dsSecureId(): string
    {
        return $this->data_3ds_secureId;
    }

    /**
     * @param string $data_3ds_secureId
     * @return PayfastService
     */
    public function setData3dsSecureId(string $data_3ds_secureId): PayfastService
    {
        $this->data_3ds_secureId = $data_3ds_secureId;
        return $this;
    }

    /**
     * @return string
     */
    public function getData3dsPares(): string
    {
        return $this->data_3ds_pares;
    }

    /**
     * @param string $data_3ds_pares
     * @return PayfastService
     */
    public function setData3dsPares(string $data_3ds_pares): PayfastService
    {
        $this->data_3ds_pares = $data_3ds_pares;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaRes(): string
    {
        return $this->paRes;
    }

    /**
     * @param string $paRes
     * @return PayfastService
     */
    public function setPaRes(string $paRes): PayfastService
    {
        $this->paRes = $paRes;
        return $this;
    }

    /**
     * @return string
     */
    public function getDoTranUrl(): string
    {
        return $this->_doTranUrl;
    }

    /**
     * @param string $doTranUrl
     * @return PayfastService
     */
    public function setDoTranUrl(string $doTranUrl): PayfastService
    {
        $this->_doTranUrl = $doTranUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getProTranUrl(): string
    {
        return $this->_proTranUrl;
    }

    /**
     * @param string $proTranUrl
     * @return PayfastService
     */
    public function setProTranUrl(string $proTranUrl): PayfastService
    {
        $this->_proTranUrl = $proTranUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getGrantType(): string
    {
        return $this->grant_type;
    }

    /**
     * @param string $grant_type
     * @return PayfastService
     */
    public function setGrantType(string $grant_type): PayfastService
    {
        $this->grant_type = $grant_type;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->api_url;
    }

    /**
     * @param string $api_url
     * @return PayfastService
     */
    public function setApiUrl(string $api_url): PayfastService
    {
        $this->api_url = $api_url;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHandshake(): mixed
    {
        return json_decode($this->handshake);
    }

    /**
     * @param string $handshake
     * @return PayfastService
     */
    public function setHandshake(string $handshake): PayfastService
    {
        $this->handshake = $handshake;
        return $this;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return PayfastService
     */
    public function setIp(string $ip): PayfastService
    {
        $this->ip = $ip;
        return $this;
    }
}
