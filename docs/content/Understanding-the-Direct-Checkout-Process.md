# Understanding the Direct Checkout Process

## Introduction

The Direct Checkout process for Payfast provides a secure and convenient method for merchants to accept online payments. By following a few simple steps, merchants can integrate Payfast into their websites and offer their customers a seamless payment experience. This guide uses the PayFast Laravel package for implementation.

## Overview

Direct Checkout is a PCI DSS compliant payment method that allows you to process card payments directly on your website. The process involves:

1. Getting an authentication token
2. Validating customer information
3. Getting OTP screen for 3DS authentication
4. Verifying OTP and storing pares
5. Completing the transaction

## Installation

First, install the package:

```bash
composer require zfhassaan/payfast
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag=payfast-config
php artisan vendor:publish --tag=payfast-migrations
php artisan migrate
```

## Configuration

Add your PayFast credentials to `.env`:

```env
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com
PAYFAST_GRANT_TYPE=client_credentials
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key
PAYFAST_RETURN_URL=https://yourdomain.com/payment/callback
PAYFAST_MODE=sandbox
```

## Step 1: Collecting Customer Data

Create a form request to validate customer data:

```bash
php artisan make:request PayfastValidateRequest
```

In your `PayfastValidateRequest`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayfastValidateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'orderNumber' => 'required|string',
            'transactionAmount' => 'required|numeric|min:0.01',
            'customerMobileNo' => 'required|string',
            'customer_email' => 'required|email',
            'cardNumber' => 'required|string',
            'expiry_month' => 'required|string',
            'expiry_year' => 'required|string',
            'cvv' => 'required|string',
        ];
    }
}
```

## Step 2: Validate Customer and Get OTP Screen

In your controller, implement the checkout method:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayfastValidateRequest;
use zfhassaan\Payfast\Facades\PayFast;
use zfhassaan\Payfast\Models\ProcessPayment;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    /**
     * Validate Customer and get OTP Screen.
     * Step 1
     */
    public function checkout(PayfastValidateRequest $request)
    {
        try {
            // Get authentication token
            $payfast = app('payfast');
            $tokenResponse = $payfast->getToken();
            $tokenData = json_decode($tokenResponse->getContent(), true);

            if ($tokenData['status'] && $tokenData['code'] === '00') {
                $payfast->setAuthToken($tokenData['data']['token']);
            } else {
                abort(403, 'Error: Auth Token Not Generated.');
            }

            // Validate customer and get OTP screen
            $paymentData = [
                'orderNumber' => $request->orderNumber,
                'transactionAmount' => $request->transactionAmount,
                'customerMobileNo' => $request->customerMobileNo,
                'customer_email' => $request->customer_email,
                'cardNumber' => $request->cardNumber,
                'expiry_month' => $request->expiry_month,
                'expiry_year' => $request->expiry_year,
                'cvv' => $request->cvv,
            ];

            $show_otp = $payfast->getOTPScreen($paymentData);
            $otpData = json_decode($show_otp->getContent(), true);

            if ($otpData['status'] && $otpData['code'] === '00') {
                // Store payment data
                $payment = ProcessPayment::create([
                    'uid' => \Str::uuid(),
                    'token' => $tokenData['data']['token'],
                    'orderNo' => $request->orderNumber,
                    'data_3ds_secureid' => $otpData['data']['customer_validate']['data_3ds_secureid'] ?? null,
                    'transaction_id' => $otpData['data']['transaction_id'] ?? null,
                    'status' => ProcessPayment::STATUS_VALIDATED,
                    'payment_method' => ProcessPayment::METHOD_CARD,
                    'payload' => json_encode($otpData['data']),
                    'requestData' => json_encode($paymentData),
                ]);

                // Return OTP screen HTML if provided, or redirect to OTP page
                if (isset($otpData['data']['redirect_url'])) {
                    return redirect($otpData['data']['redirect_url'])->with([
                        'transaction_id' => $payment->transaction_id,
                        'payment_id' => $payment->id,
                    ]);
                }

                // If HTML is provided directly
                $data_3ds_html = $otpData['data']['customer_validate']['data_3ds_html'] ?? null;
                if ($data_3ds_html) {
                    return response($data_3ds_html);
                }

                return redirect('/otp-screen')->with([
                    'transaction_id' => $payment->transaction_id,
                    'payment_id' => $payment->id,
                ]);
            }

            return response()->json([
                'message' => $otpData['message'] ?? 'Error processing payment',
            ], Response::HTTP_BAD_REQUEST);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Processing your request.',
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
```

## Step 3: Verify OTP and Store Pares

After the customer enters the OTP, verify it:

```php
/**
 * Verify OTP and store pares.
 * Step 2
 */
public function verifyOTP(Request $request)
{
    $request->validate([
        'transaction_id' => 'required|string',
        'otp' => 'required|string',
        'pares' => 'required|string', // Received from PayFast 3DS
    ]);

    $response = PayFast::verifyOTPAndStorePares(
        $request->transaction_id,
        $request->otp,
        $request->pares
    );

    $result = json_decode($response->getContent(), true);

    if ($result['status']) {
        return response()->json([
            'message' => 'OTP verified successfully',
            'pares' => $request->pares,
        ]);
    }

    return response()->json($result, 400);
}
```

## Step 4: Complete Transaction from Callback

PayFast will send a callback with the pares. Handle it:

```php
/**
 * Complete transaction from callback.
 * Step 3
 */
public function callback(Request $request)
{
    $request->validate([
        'pares' => 'required|string',
    ]);

    // Fetch transaction data from database
    $payment = ProcessPayment::where('data_3ds_pares', $request->pares)
        ->orWhere('transaction_id', $request->transaction_id ?? '')
        ->first();

    if (!$payment) {
        return response()->json([
            'message' => 'Payment not found',
        ], 404);
    }

    // Complete transaction using pares
    $response = PayFast::completeTransactionFromPares($request->pares);
    $result = json_decode($response->getContent(), true);

    if ($result['status']) {
        // Payment completed successfully
        $payment->markAsCompleted();

        // Update your order status, send confirmation email, etc.
        // ...

        return response()->json([
            'status' => 'success',
            'message' => 'Payment completed',
            'transaction_id' => $payment->transaction_id,
        ]);
    }

    return response()->json($result, 400);
}
```

## Alternative: Using Stored Transaction Data

If you prefer to store transaction data and fetch it later:

```php
/**
 * Initiate transaction using stored data.
 */
public function initiateTransaction(Request $request)
{
    $request->validate([
        'pares' => 'required|string',
    ]);

    // Fetch stored transaction data
    $payment = ProcessPayment::where('data_3ds_pares', $request->pares)->first();

    if (!$payment) {
        return response()->json(['message' => 'Payment not found'], 404);
    }

    // Set auth token
    $payfast = app('payfast');
    $payfast->setAuthToken($payment->token);

    // Prepare transaction data
    $requestData = json_decode($payment->requestData, true);
    $data = [
        'user_id' => $requestData['user_id'] ?? null,
        'basket_id' => $requestData['orderNumber'] ?? $payment->orderNo,
        'txnamt' => $requestData['transactionAmount'] ?? 0,
        'customer_mobile_no' => $requestData['customerMobileNo'] ?? '',
        'customer_email_address' => $requestData['customer_email'] ?? '',
        'card_number' => $requestData['cardNumber'] ?? '',
        'expiry_year' => $requestData['expiry_year'] ?? '',
        'expiry_month' => $requestData['expiry_month'] ?? '',
        'cvv' => $requestData['cvv'] ?? '',
        'transaction_id' => $payment->transaction_id,
        'data_3ds_secureid' => $payment->data_3ds_secureid,
        'data_3ds_pares' => $request->pares,
    ];

    // Initiate transaction
    $result = $payfast->initiateTransaction($data);
    $response = json_decode($result, true);

    if (isset($response['code']) && $response['code'] === '00') {
        $payment->markAsCompleted();
        return response()->json($response);
    }

    return response()->json($response, 400);
}
```

## Routes Setup

Add routes to `routes/web.php`:

```php
Route::post('/payment/checkout', [PaymentController::class, 'checkout']);
Route::post('/payment/verify-otp', [PaymentController::class, 'verifyOTP']);
Route::post('/payment/callback', [PaymentController::class, 'callback']);
Route::post('/payment/initiate', [PaymentController::class, 'initiateTransaction']);
```

## Database Cleanup (Optional)

You can clean up old transactions after successful payment:

```php
// After successful payment
$payment->markAsCompleted();

// Optionally, delete the temporary record
// $payment->delete(); // Soft delete preserves audit trail
```

## Finalizing the Integration

Once the payment form is generated, it can be displayed on the merchant's website. Merchants should ensure that:

1. The form is presented to customers at the appropriate stage of the checkout process
2. Success and failure URLs are specified to redirect customers appropriately
3. Error handling is implemented for all payment steps
4. Payment status is tracked in the database

## Conclusion

The Direct Checkout process for Payfast offers a secure and reliable solution for merchants to accept online payments. By following the steps outlined in this guide and using the PayFast Laravel package, merchants can seamlessly integrate Payfast into their websites and provide customers with a smooth and trustworthy payment experience.

## Additional Resources

- [Payment Flows](Payment-Flows) - Detailed payment flow documentation
- [API Reference](API-Reference) - Complete API method documentation
- [IPN Handling](IPN-Handling) - Webhook notifications
- [Troubleshooting](Troubleshooting) - Common issues and solutions















