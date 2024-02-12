<?php

namespace zfhassaan\Payfast;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use zfhassaan\Payfast\Helpers\ConfigLoader;
use zfhassaan\Payfast\Helpers\HttpCommunicator;
use zfhassaan\Payfast\Helpers\PayfastService;
use zfhassaan\Payfast\Helpers\Utility;
use zfhassaan\Payfast\Interfaces\PaymentInterface;
use zfhassaan\Payfast\Models\ProcessPayment;

/**
 * This section contains the details of all APIs provided by PAYFAST. The merchants, acquirers and/or
 * aggregators could call these APIs. These API\’S are based on REST architecture and serve standard HTTP
 * codes for the response payload.
 * @method getup()
 */
class PayFast extends PayfastService
{
    public mixed $config;
    public function __construct(){
        $this->load();
    }

    /**
     * Authentication Access Token:
     * Following API will provide you the Authentication token, which will be used to call APIs. Merchant_id
     * and Secured_key is mandatory to get the access token. This token will be sent on all the APIs with
     * standard HTTP header ‘Authorization’.
     *
     * Request Url: /token
     * Request Method: POST
     *
     * @return JsonResponse
     */
    public function GetToken(): JsonResponse
    {
        try{
            $options = [
                "grant_type" => $this->grant_type,
                "merchant_id" => $this->merchant_id,
                "secured_key" => $this->secured_key,
                "customer_ip" => request()->ip()
            ];
            $result = json_decode($this->GetPayfastToken(http_build_query($options))->handshake);

            if($result->code == '00') {
                $this->setAuthToken(($handshake = $this->getHandshake()) ? $handshake->token : false);
                return Utility::returnSuccess($this->getHandshake(),'00'.$result->code);
            }
            return Utility::returnError($result);
        } catch(\Exception $e)
        {
            return Utility::returnError($e->getMessage());
        }
    }

    /**
     * Any access token can be refreshed upon expiry. A refresh token is given along with original token.
     * The RefreshToken can be used with the Token received from Payfast::GetToken().
     * The result returns token with a refresh token, that can be passed to the Payfast::RefreshToken() to renew
     * the token from payfast.
     *
     * curl -X POST \
     * <BASE_URL>/refreshtoken \
     * -H 'cache-control: no-cache' \
     * -H 'content-type: application/x-www-form-urlencoded'
     *
     * @param string $token
     * @param String $refresh_token
     * @return null|JsonResponse
     */
    public function RefreshToken(String $token,String $refresh_token): JsonResponse|null
    {
        $this->setAuthToken($token);
        $this->setRefreshToken($refresh_token);

        $postFields = http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token
        ]);

        $response = $this->PayfastPost('refreshtoken',$postFields);
        return $response->code != '00' ? Utility::returnError($response, $response->code, ResponseAlias::HTTP_BAD_REQUEST) : Utility::returnSuccess($response,'00'.$response->code);
    }

    /**
     * This API will be used if you choose to send OTP to registered mobile number of the customer
     * that respective Issuer/Bank. This API will be used if you choose to send OTP to registered mobile number of the
     * customer with that respective Issuer/Bank.
     *
     * {
     * "orderNumber": "123456789",
     * "transactionAmount": "1",
     * "customerMobileNo": "03xxxxxxxxx",
     * "customer_email": "example@gmail.com",
     * "cardNumber": "xxxxxxxxxxxxxxxx",
     * "expiry_month": "xx",
     * "expiry_year": "xx",
     * "cvv": "xxxx"
     * }
     *
     * @param $data
     * @return JsonResponse
     */
    public function GetOTPScreen($data): JsonResponse
    {
        $validator = Validator::make($data, [
            'orderNumber' => 'required',
            'transactionAmount' => 'required|numeric',
            'customerMobileNo' => 'required',
            'customer_email' => 'required|email',
            'cardNumber' => 'required',
            'expiry_month' => 'required',
            'expiry_year' => 'required',
            'cvv' => 'required',
        ],[
            'orderNumber.required' => 'Order Number is Required',
            'transactionAmount.required' => 'Transaction Amount is required',
            'customerMobileNo.required' => 'Customer Mobile Number is required',
            'customer_email.required' => 'Customer Email address is required',
            'cardNumber.required' => 'Card Number is required',
            'expiry_month.required' => 'Expiry Month is required',
            'expiry_year.required' => 'Expiry Year is required',
            'cvv.required' => 'CVV is a required Field.'
        ]);

        if ($validator->fails()) return Utility::returnError($validator->errors()->first(), 'VALIDATION_ERROR', Response::HTTP_BAD_REQUEST);

        $token = json_decode(self::GetToken()->getContent())->data->token;

        self::setAuthToken($token);

        $url = 'customer/validate';

        $postFields = [
            'basket_id' =>$data['orderNumber'],
            'txnamt' => $data['transactionAmount'],
            'customer_mobile_no'=> $data['customerMobileNo'],
            'customer_email_address' => $data['customer_email'],
            'account_type_id' => '1',
            'card_number'=>$data['cardNumber'],
            'expiry_month' => $data['expiry_month'],
            'expiry_year' => $data['expiry_year'],
            'cvv' => $data['cvv'],
            'order_date' => \Illuminate\Support\Carbon::today()->toDateString(),
            'data_3ds_callback_url' => $this->getReturnUrl(),
            'currency_code' => 'PKR'
        ];

        $response = $this->PayfastPost($url,http_build_query($postFields));

        if($response->code != 00) {
            return Utility::returnError(json_decode(Utility::PayfastErrorCodes($response->code)->getContent())->error_description,$response->code,Response::HTTP_BAD_REQUEST);
        }

        $options = [
            'token' => self::getAuthToken(),
            'data_3ds_secureid' => $response->data_3ds_secureid,
            'transaction_id' => $response->transaction_id,
            'payload' => json_encode(['customer_validate'=>$response,'user_request'=>$data]),
            'requestData' => json_encode($data)
        ];

        $db = ProcessPayment::create($options);
        Utility::LogData('Payfast','Database Storage Check', $db);

        return Utility::returnSuccess(['token'=>self::getAuthToken(),'customer_validate' => $response]);
    }


    /**
     * This API will provide the available list of issuer/bank.
     * curl -X GET \
     * <BASE_URL>/list/banks \
     * -H 'cache-control: no-cache' \
     * -H 'content-type: application/x-www-form-urlencoded'
     * The above command returns following JSON structure:
     *
     * @return JsonResponse
     */
    public function ListBanks(): JsonResponse
    {
        $uri = $this->getApiUrl().'/list/banks';
        $headers = [
            'cache-control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.self::getAuthToken()
        ];
        $response = HttpCommunicator::get($uri,$headers);

        if($response->banks != null || $response->banks->isNotEmpty()) {
            return Utility::returnSuccess($response->banks);
        }
        return Utility::returnError($response);
    }

    /**
     * This API will provide the Payment type (or account type, e.g. Account, Wallet or Debit Card) based on selected issuer/bank.
     * curl -X GET \
     * '<BASE_URL>/list/instruments?bank_code=<bank code>' \
     * -H 'cache-control: no-cache' \
     * -H 'content-type: application/x-www-form-urlencoded'
     *
     * e.g. bank_code=12
     *
     * @param string|array $code
     * @return bool|JsonResponse
     */
    public function ListInstrumentsWithBank(Array|string $code): bool|JsonResponse
    {

        $uri = $this->getApiUrl().'/list/instruments?bank_code='.$code;
        $headers = [
            'cache-control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.self::getAuthToken()
        ];
        $response = HttpCommunicator::get($uri,$headers);

        if($response->bankInstruments != null || $response->code == 00) {
            return Utility::returnSuccess($response->bankInstruments);
        }
        return Utility::returnError($response);
    }

    /**
     * 3.12 This API will fetch transaction details with respect to the transaction id or the basket id (provided by
     * merchant).
     * @param string $transactionId
     * @return JsonResponse
     */
    public function GetTransactionDetails(string $transactionId): JsonResponse
    {
        $uri = $this->getApiUrl().'/transaction/'.$transactionId;
        $headers = [
            'cache-control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.self::getAuthToken()
        ];
        $response = HttpCommunicator::get($uri, $headers);

        if($response->bankInstruments != null || $response->code == 00) {
            return Utility::returnSuccess($response->bankInstruments);
        }

        return Utility::returnError($response);
    }

    /**
     * 3.12.1 This API will fetch transaction details with respect to the Basket id (provided by
     * merchant).
     * @param string $basket_id
     * @return JsonResponse
     */
    public function GetTransactionDetailsByBasketId(string $basket_id): JsonResponse
    {
        $uri = $this->getApiUrl().'/transaction/basket_id'.$basket_id;
        $headers = [
            'cache-control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.self::getAuthToken()
        ];
        $response = HttpCommunicator::get($uri, $headers);

        if($response->bankInstruments != null || $response->code == 00) {
            return Utility::returnSuccess($response->bankInstruments);
        }

        return Utility::returnError($response);
    }

    /**
     * This API will allow merchant to initiate the request for transaction refund in case of any dispute in the transaction.
     *
     * @param array $data
     * @return bool|string
     */
    public function RefundTransactionRequest(Array $data): bool|string
    {

        $uri = '/transaction/refund/'.$data['transactionId'];
        $fields = [
            'transaction_id' => $data['transactionId'],
            'txnamt' => $data['transactionAmount'],
            'refund_reason' => $data['refund_reason'],
            'customer_ip' => $this->getip()
        ];

        $response = json_decode(self::PayfastPost($uri,http_build_query($fields)));
        return $response;
    }


    public function PayWithEasyPaisa($data){

        $data['order_date'] = Carbon::today()->toDateString();
        $data['bank_code'] = 13;
        return $this->ValidateWalletTransaction($data);
    }

    public function PayWithUPaisa($data)
    {
        $data['order_date'] = Carbon::today()->toDateString();
        $data['bank_code'] = 14;
        return $this->ValidateWalletTransaction($data);
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function ValidateWalletTransaction($data): string|bool
    {
        $data['account_type_id'] = 4;
        $url = 'customer/validate';
        $field_string = http_build_query($data);
        $response = self::PayfastPost($url, $field_string);

        if (json_decode($response)->code == "00") {
            $data['token'] = $this->getAuthToken();
            $data['transaction_id'] = json_decode($response)->transaction_id;
            return $this->WalletTransactionInitiate($data);
        }
        return $response;
    }

    /**
     * Mobile Wallet Initiate Transaction
     */
    public function WalletTransactionInitiate($data): bool|string
    {

        $field_string = http_build_query($data);
        return self::PayfastPost('transaction',$data);
    }

    /**
     * Initiate Transaction
     *
     * This API will initiate payment/transaction request without token. e.g. Direct Transaction.
     * This function will be used for credit/debit card transaction.
     */
    public function InitiateTransaction(Array $data): bool|string
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
     * Initiate Transaction Extracted
     *
     */
    public function __initiate_transaction($data)
    {
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






}
