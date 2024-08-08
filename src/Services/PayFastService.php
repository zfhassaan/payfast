<?php

namespace zfhassaan\Payfast\Services;

use Carbon\Carbon;
use CurlHandle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Interface\PaymentInterface\PaymentInterface;
use zfhassaan\Payfast\helper\Utility;
use samrabbas112\Payfast\Models\ProcessPayment;
use samrabbas112\Payfast\Payment;
use zfhassaan\Payfast\Models\ProcessPayment as ModelsProcessPayment;
use zfhassaan\Payfast\Payment as PayfastPayment;

class PayFastService extends PayfastPayment implements PaymentInterface
{

     /**
     * Authentication Access Token:
     * Following API will provide you the Authentication token, which will be used to call APIs. Merchant_id
     * and Secured_key is mandatory to get the access token. This token will be sent on all the APIs with
     * standard HTTP header ‘Authorization’.
     *
     * Request Url: /token
     * Request Method: POST
     * @method getip()
     * @return JsonResponse
     */
    public function getToken(): JsonResponse
    {
        try {

            $options = [
                "grant_type" => $this->grant_type,
                "merchant_id" => $this->merchant_id,
                "secured_key" => $this->secured_key,
                "customer_ip" => self::getip()
            ];

            $result = $this->getPayfastToken(http_build_query($options));

            if($result->code == '00') {
                $this->setAuthToken(($handshake = $this->getHandShake()) ? $handshake->token : false);
                return Utility::returnSuccess(($result),'00'.$result->code);
            }

            return Utility::returnError($result);
        } catch(\Exception $e) {

            return Utility::returnError($e->getMessage());
        }
    }

    public function refreshToken(string $token, string $refresh_token): JsonResponse
    {
        $this->setAuthToken($token);
        $this->setRefreshToken($refresh_token);

        $postFields = http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token
        ]);

        $response = json_decode($this->payfastPost('refreshtoken',$postFields));

        return $response->code != '00' ? Utility::returnError($response, $response->code, Response::HTTP_BAD_REQUEST) : Utility::returnSuccess($response,'00'.$response->code);
    }

    public function getOtpScreen(array $data): JsonResponse
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
        DB::beginTransaction();

        try {
        $token = json_decode(self::GetToken()->getContent())->data->token;

        self::setAuthToken($token);

        $url = 'customer/validate';

        $postFields = [
            'basket_id' =>$data['orderNumber'],
            'txnamt' => $data['transactionAmount'],
            'customer_mobile_no'=> $data['customerMobileNo'],
            'customer_email_address' => $data['customer_email'],
            'account_type_id' => '2',
            'card_number'=>$data['cardNumber'],
            'expiry_month' => $data['expiry_month'],
            'expiry_year' => $data['expiry_year'],
            'cvv' => $data['cvv'],
            'order_date' => \Illuminate\Support\Carbon::today()->toDateString(),
            'data_3ds_callback_url' => self::getReturnUrl(),
            'currency_code' => 'PKR'
        ];

        $response = json_decode($this->payfastPost($url,http_build_query($postFields)));

        if($response->code != 00) {
            return Utility::returnError(json_decode(Utility::PayfastErrorCodes($response->code)->getContent())->error_description,$response->code,Response::HTTP_BAD_REQUEST);
        }
        $options = [
            'token' => json_decode(self::getAuthToken())->token,
            'data_3ds_secureid' => json_decode($response)->customer_validation->data_3ds_secureid,
            'transaction_id' => json_decode($response)->customer_validation->transaction_id,
            'payload' => json_encode(['customer_validate'=>json_decode($response)->customer_validation,'user_request'=>$data]),
            'requestData' => json_encode($data)
        ];

            $db = ModelsProcessPayment::create($options);
            Utility::LogData('Payfast','Database Storage Check', $db);
            DB::commit();
    
            return Utility::returnSuccess(['token'=>self::getAuthToken(),'customer_validate' => $response]);
        } catch (\Exception $e) {
            DB::rollBack();
            return Utility::returnError($e->getMessage());
        }
    
    }

    public function listBanks(): JsonResponse
    {
        $uri = '/list/banks';

        $response = json_decode(self::payfastGet($uri));

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
     * @param string $code
     * @return bool|JsonResponse
     */
    public function listInstrumentsWithBank(string $code): bool|JsonResponse
    {
        $uri = '/list/instruments?bank_code='.$code;

        $response = json_decode(self::payfastGet($uri));

        if($response->bankInstruments != null || $response->code == 00) {
            return Utility::returnSuccess($response->bankInstruments);
        }
        return Utility::returnError($response);
    }

    /**
     * This API will fetch transaction details with respect to the transaction id or the basket id (provided by
     * merchant).
     * @param string $transactionId
     * @return JsonResponse
     */
    public function getTransactionDetails(string $transactionId): JsonResponse
    {
        $uri = '/transaction/'.$transactionId;
        $response = json_decode(self::payfastGet($uri));

        if($response->bankInstruments != null || $response->code == 00) {
            return Utility::returnSuccess($response->bankInstruments);
        }

        return Utility::returnError($response);
    }

    /**
     * This API will allow merchant to initiate the request for transaction refund in case of any dispute in the transaction.
     *
     * @param $data
     * @return bool|string
     */
    public function refundTransactionRequest(array $data)
    {

        $uri = '/transaction/refund/'.$data['transactionId'];
        $fields = [
            'transaction_id' => $data['transactionId'],
            'txnamt' => $data['transactionAmount'],
            'refund_reason' => $data['refund_reason'],
            'customer_ip' => $this->getip()
        ];

        $response = json_decode(self::payfastPost($uri,http_build_query($fields)));
        return $response;
    }


    public function payWithEasyPaisa(array $data){

        $data['order_date'] = Carbon::today()->toDateString();
        $data['bank_code'] = 13;
        return $this->ValidateWalletTransaction($data);
    }

    public function PayWithUPaisa(array $data)
    {
        $data['order_date'] = Carbon::today()->toDateString();
        $data['bank_code'] = 14;
        return $this->ValidateWalletTransaction($data);
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function validateWalletTransaction(array $data): string|bool
    {
        $data['account_type_id'] = 4;
        $url = 'customer/validate';
        $field_string = http_build_query($data);
        $response = self::payfastPost($url, $field_string);

        if (json_decode($response)->code == "00") {
            $data['token'] = $this->getAuthToken();
            $data['transaction_id'] = json_decode($response)->transaction_id;
            return $this->walletTransaction($data);
        }
        return $response;
    }

    /**
     * Mobile Wallet Initiate Transaction
     */
    public function walletTransaction(array $data) {

        $field_string = http_build_query($data);
        $response = self::payfastPost('transaction',$data);

        return $response;
    }

    /**
     * Initiate Transaction
     *
     * This API will initiate payment/transaction request without token. e.g. Direct Transaction.
     * This function will be used for credit/debit card transaction.
     */
    public function initiateTransaction(array $data)
    {
        $result = [
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

        return $this->__initiate_transaction($result);
    }

    /**
     * Initiate Transaction Extracted
     *
     */
    public function __initiate_transaction(array $data)
    {
        $uri = $this->getApiUrl().'transaction';
        $field_string = http_build_query($data);
        return $this->payfastPost($uri, $field_string);
        
    }

    public function addPermenantPaymentInstrument(array $data) 
    {
        $uri = '/user/instruments/';
        $fields = [
            "merchant_user_id"=> $data['user_id'],
            "basket_id"=> $data['basket_id'],
            "txnamt" => $data['txnamt'],
            "user_mobile_no" => $data['user_mobile_no'],
            "account_type" => '2',
            "txtamt" => $data['amount'],
            "transaction_id"=> $data['transaction_id'],
            "card_number"=> $data['card_number'],
            "expiry_year"=> $data['expiry_year'],
            "expiry_month"=> $data['expiry_month'],
            "cvv"=>$data['cvv'],
        ];

        $response = json_decode(self::payfastPost($uri,http_build_query($fields)));
        if($response->code != 00) {
            return Utility::returnError(json_decode(Utility::PayfastErrorCodes($response->code)->getContent())->error_description,$response->code,Response::HTTP_BAD_REQUEST);
        }
        return $response;

    }
}
