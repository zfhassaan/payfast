<?php 

namespace zfhassaan\Payfast;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PayFast {

    protected $apiUrl;
    public $merchant_id;
    public $secured_key;
    public $grant_type;
    public $handshake;
    public $refreshToken;
    public $customer_validation;

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

    /**
     * Constructor for Payfast
     * @return void
     */
    public function __construct()
    {
        $this->initConfig();
    }

    /**
     * Initialize Config Values
     * @return void
     */
    public function initConfig()
    {
        config('payfast.mode') === 'sandbox' ? $this->setApiUrl(config('payfast.sandbox_api_url')) : $this->setApiUrl(config('payfast.api_url'));   
        $this->merchant_id = config('payfast.merchant_id');
        $this->store_id = config('payfast.store_id');
        $this->api_mode = config('payfast.mode');
        $this->return_url = config('payfast.return_url');
        $this->secured_key = config('payfast.secured_key');
        $this->grant_type = config('payfast.grant_type');
    }

    /**
     * Following function will provide you the Authentication token, which will be used to call
     * APIs. Merchant_id and Secured_key is mandatory to get the access token. This token
     * will be sent on all the APIs with standard HTTP header ‘Authorization’.
     * 
     * Request Url: /token
     * 
     * @return hash key
     */
    public function getToken() {
        $fields = [
            "grant_type" => $this->grant_type,
            "merchant_id" => $this->merchant_id,
            "secured_key" => $this->secured_key
        ];

        $field_string = http_build_query($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getApiUrl().'token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $this->handshake = json_decode($result);
        if($this->handshake != null && $this->handshake->token != null){
            $AuthToken = $this->handshake->token;
            $this->setAuthToken($AuthToken);
        } else {
            $this->setAuthToken(false);
        }
        return $this->handshake;
    }

    /**
     * Any access token can be refreshed upon expiry. A refresh token is given along with
     * original token.
     * 
     * Request URL: /refreshtoken
     * 
     * @return hash key
     */
    public function refreshToken(){
        $fields = [
            "grant_type" => 'refresh_token',
            "refresh_token" => $this->handshake->refresh_token
        ];
        $field_string = http_build_query($fields);

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->getApiUrl().'refreshtoken',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $field_string,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->handshake->token,
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $refresh_token = json_decode($response);
        $this->setRefreshToken($refresh_token->token);
        return $this->getRefreshToken();
    }

    /**
     * This API will be used if you choose to send OTP to registered mobile number of the customer 
     * that respective Issuer/Bank.
     * @return JsonResponse
     */
    public function customer_validate($data){
        // Data Received on Post Request for OTP Screen
        $data['order_date'] = Carbon::today()->toDateString();
        $data['account_type_id'] = 1;
        $field_string = http_build_query($data);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->getApiUrl().'customer/validate',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $field_string,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->getAuthToken()
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return response()->json(['response' => json_decode($response), 'token'=>$this->getAuthToken()]);
    }



    /**
     * Mobile Wallet Transaction
     *
     * @param [type] $data
     * @return void
     */
    public function wallet($data){
        // Data Received on Post Request for OTP Screen
        $data['order_date'] = Carbon::today()->toDateString();
        $data['bank_code'] = 13; // Change it according to your own Bank i.e. Easy Paisa / Jazz Cash / UPaisa
        $data['account_type_id'] = 4;

        $field_string = http_build_query($data);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->getApiUrl().'customer/validate',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $field_string,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->getAuthToken()
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        if(json_decode($response)->code == "00"){
            $data['token'] = $this->getAuthToken();
            $data['transaction_id'] = json_decode($response)->transaction_id;
            return $this->wallet_transaction($data);
        }
        return $response;
    }    

    /**
     * Initiate Transaction 
     * 
     * This API will initiate payment/transaction request without token. e.g. Direct Transaction.
     * This function will be used for credit/debit card transaction.
     */
    public function initiate_transaction($data)
    {
        $res = [
            "user_id"=> $data['user_id'],
            "basket_id"=> $data['basket_id'],
            "txnamt" => $data['txnamt'],
            "customer_mobile_no" => $data['customer_mobile_no'],
            "customer_email_address"=> $data['customer_email_address'],
            "order_date"=> Carbon::today()->toDateString(),
            "transaction_id"=> $data['transaction_id'],
            "card_number"=> $data['card_number'],
            "expiry_year"=> $data['expiry_year'],
            "expiry_month"=> $data['expiry_month'],
            "cvv"=>$data['cvv'],
            "data_3ds_pares"=> $data['data_3ds_pares'],
            "data_3ds_secureid"=> $data['data_3ds_secureid']
        ];

        return $this->__initiate_transaction($res);
    }

    /**
     * Mobile Wallet Initiate Transaction
     */
    public function wallet_transaction($data) {
        // dd($data);
        $field_string = http_build_query($data);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->getApiUrl().'transaction',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $field_string,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->getAuthToken()
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
    /**
     * Initiate Transaction Extracted 
     * 
     */
    public function __initiate_transaction($data) {
        // dd($data);
        $field_string = http_build_query($data);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->getApiUrl().'transaction',
            CURLOPT_RETURNTRANSFER => true, 
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $field_string,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Bearer: '.$this->getAuthToken()
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;        
    }
    /**
     * This API will provide the available list of issuer/bank.
     * 
     * Request URL: /list/banks
     */
    public function list_banks()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => $this->getApiUrl().'list/banks',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "content-type: application/x-www-form-urlencoded",
            'Authorization: Bearer '.$this->handshake->token,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    /**
     * This API endpoint will provide the Payment type ( or account type e.g. Account, 
     * Wallet, or Debit Card) based on selected issuer/bank. 
     *   
     */
    public function payment_instrument_type($data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8443",
            CURLOPT_URL => $this->getApiUrl().'list/instruments?bank_code='.$data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
              "cache-control: no-cache",
              "content-type: application/x-www-form-urlencoded",
              'Authorization: Bearer '.$this->handshake->token,
            ),
        ));
          
        $response = curl_exec($curl);          
        curl_close($curl);
        return $response;
    }

    /**
     * This API will provide the available list of issuer/bank based on instrument id.
     * 
     */
    public function issuer_bank_instrument_id($data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8443",
            CURLOPT_URL => $this->getApiUrl().'list/instrumentbanks?instrument_id='.$data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
              "cache-control: no-cache",
              "content-type: application/x-www-form-urlencoded",
              'Authorization: Bearer '.$this->handshake->token,
            ),
        ));
          
        $response = curl_exec($curl);          
        curl_close($curl);
        return $response;
    }



    /**
     * Get the value of Pares
     */
    public function getPares()
    {
        return $this->paRes;
    }

    /**
     * Set the value of PaRes 
     * 
     * @return self
     */
    public function setPares($paRes)
    {
        $this->paRes = $paRes;
        return $this;
    }

    /**
     * Get the value of apiUrl
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Set the value of apiUrl
     *
     * @return  self
     */
    public function setApiUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;

        return $this;
    }

    /**
     * Get the value of auth_token
     */
    public function getAuthToken()
    {
        return $this->auth_token;
    }

    /**
     * Set the value of auth_token
     *
     * @return  self
     */
    public function setAuthToken($auth_token)
    {
        $this->auth_token = $auth_token;

        return $this;
    }

    /**
     * Set the value for refreshToken
     * 
     * @return self
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    /**
     * Get the value for refreshToken
     * 
     * @return self
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Set the value of Transaction Token
     * 
     * @return self
     */
    public function setTransactionId($tranId)
    {
        $this->transaction_id = $tranId;
        return $this;
    }

    /**
     * Get the value of Transaction Id
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * Set the value for data_3ds_secureid
     */
    public function set_data_3ds_secureid($secureId)
    {
        $this->data_3ds_secureId = $secureId;
        return $this;
    }

    /**
     * Get the value for data 3ds SecureId
     */
    public function get_data_3ds_secureid()
    {
        return $this->data_3ds_secureId;
    }
}