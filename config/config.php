<?php 

return [
    'api_url'    => env('PAYFAST_API_URL', ''),
    'sandbox_api_url'=>env('PAYFAST_SANDBOX_URL',''),
    'grant_type' => env('PAYFAST_GRANT_TYPE', ''),
    'merchant_id'=> env('PAYFAST_MERCHANT_ID', ''),
    'secured_key'=> env('PAYFAST_SECURED_KEY', ''),
    'store_id'   => env('PAYFAST_STORE_ID',''),
    'return_url' => env('PAYFAST_RETURN_URL', ''),
    'mode'       => env('PAYFAST_MODE', 'sandbox')
];