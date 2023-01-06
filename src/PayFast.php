<?php

namespace zfhassaan\Payfast;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use zfhassaan\Payfast\Payment\Payment;
use Interface\PaymentInterface\PaymentInterface;

class PayFast extends Payment implements PaymentInterface {

    /**
     * Get the required Auth Token from Payfast.
     *
     * @return mixed
     */
    public function getToken() {
        $fields = [
            "grant_type" => $this->grant_type,
            "merchant_id" => $this->merchant_id,
            "secured_key" => $this->secured_key
        ];

        $response = $this->sendCurlRequest('token', $fields, 'POST');
        $this->handshake = json_decode($response);
        if($this->handshake != null && $this->handshake->token != null){
            $this->setAuthToken($this->handshake->token);
        } else {
            $this->setAuthToken(false);
        }
        return $this->handshake;
    }


    /**
     * Refresh the required auth token from payfast.
     *
     * @return mixed
     */
    public function refreshToken() {
        $fields = [
            "grant_type" => 'refresh_token',
            "refresh_token" => $this->handshake->refresh_token
        ];
        $response = $this->sendCurlRequest('refreshtoken', $fields, 'POST', ['Authorization: Bearer '.$this->handshake->token]);
        $refresh_token = json_decode($response);
        $this->setRefreshToken($refresh_token->token);
        return $this->getRefreshToken();
    }

    /**
     * Validate the Customer Information from Payfast.
     *
     * @param $data
     * @return JsonResponse
     */
    public function customer_validate($data): JsonResponse
    {
        $data['order_date'] = Carbon::today()->toDateString();
        $data['account_type_id'] = 1;
        $field_string = http_build_query($data);
        $response = $this->sendCurlRequest('customer/validate', $field_string, 'POST', ['Authorization: Bearer '.$this->getAuthToken()]);
        return response()->json(['response' => json_decode($response), 'token'=>$this->getAuthToken()]);
    }

    /**
     * Send Request to Payfast for EasyPaisa Payments.
     *
     * @param $data
     * @return JsonResponse
     */
    public function wallet($data): bool|string
    {
        // Data Received on Post Request for OTP Screen
        $data['order_date'] = Carbon::today()->toDateString();
        $data['bank_code'] = 13; // Change it according to your own Bank i.e. Easy Paisa / Jazz Cash / UPaisa
        $data['account_type_id'] = 4;

        $field_string = http_build_query($data);
        $response = $this->sendCurlRequest('customer/validate', $field_string, 'POST', ['Authorization: Bearer '.$this->getAuthToken()]);
        if(json_decode($response)->code == "00") {
            $data['token'] = $this->getAuthToken();
            $data['transaction_id'] = json_decode($response)->transaction_id;
            return $this->wallet_transaction($data);
        }
        return $response;
    }

    /**
     * Initiate the transaction to send request
     *
     * @param $data
     * @return JsonResponse
     */
    public function initiate_transaction($data): bool|string
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

        return $this->sendCurlRequest('transaction', http_build_query($res), 'POST', ['Authorization: Bearer '.$this->getAuthToken()]);
    }

    /**
     * List all the banks.
     * @return JsonResponse
     */
    public function list_banks()
    {
        $response = $this->sendCurlRequest('list/banks', [], "GET");
        return $response;
    }

    /**
     * Bank Instrument with Bank code.
     *
     * @param $data
     * @return JsonResponse
     */
    public function payment_instrument_type($data)
    {
        $url = $this->getApiUrl().'list/instruments?bank_code='.$data;
        $headers = [
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded",
            'Authorization: Bearer '.$this->handshake->token,
        ];
        return $this->sendCurlRequest($url, [], "GET", $headers);
    }

    /**
     * This API will provide the available list of issuer/bank based on instrument id.
     *
     * @param $data
     * @return JsonResponse
     */
    public function issuer_bank_instrument_id($data)
    {
        $url = $this->getApiUrl().'list/instrumentbanks?instrument_id='.$data;
        $headers = [
            "cache-control: no-cache",
            "content-type: application/x-www-form-urlencoded",
            'Authorization: Bearer '.$this->handshake->token,
        ];

        return $this->sendCurlRequest($url,[],'GET',$headers);
    }

    /**
     * Send Request to the Payfast Payment Gateway API.
     *
     * @param $endpoint
     * @param array $fields
     * @param string $method
     * @param array $headers
     * @return JsonResponse
     */
    private function sendCurlRequest($endpoint, $fields, string $method = 'POST', array $headers = []): bool|string
    {
        $field_string = http_build_query($fields);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->getApiUrl().$endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $field_string,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/x-www-form-urlencoded',
            ], $headers),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
