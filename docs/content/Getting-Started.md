# Getting Started

This guide will help you get started with the PayFast package by walking through basic usage examples.

## Basic Setup

After installation and configuration, you can start using the package. The package provides a Facade for easy access:

```php
use zfhassaan\Payfast\Facades\PayFast;
```

## Step 1: Get Authentication Token

Before making any payment requests, you need to obtain an authentication token:

```php
use zfhassaan\Payfast\Facades\PayFast;

$response = PayFast::getToken();
$data = json_decode($response->getContent(), true);

if ($data['status'] && $data['code'] === '00') {
    $token = $data['data']['token'];
    // Token is ready to use
}
```

## Step 2: Process a Card Payment

### Basic Card Payment Flow

```php
use zfhassaan\Payfast\Facades\PayFast;
use Illuminate\Http\Request;

public function processPayment(Request $request)
{
    $paymentData = [
        'orderNumber' => 'ORD-' . time(),
        'transactionAmount' => 1000.00,
        'customerMobileNo' => '03001234567',
        'customer_email' => 'customer@example.com',
        'cardNumber' => '4111111111111111',
        'expiry_month' => '12',
        'expiry_year' => '2025',
        'cvv' => '123',
    ];

    $response = PayFast::getOTPScreen($paymentData);
    $result = json_decode($response->getContent(), true);

    if ($result['status'] && $result['code'] === '00') {
        // Payment validated, redirect to OTP screen
        $transactionId = $result['data']['transaction_id'];
        $paymentId = $result['data']['payment_id'];
        
        return redirect('/otp-screen')->with([
            'transaction_id' => $transactionId,
            'payment_id' => $paymentId,
        ]);
    }

    return back()->withErrors(['payment' => $result['message']]);
}
```

## Step 3: Verify OTP

After customer enters OTP on the OTP screen:

```php
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

## Step 4: Handle Payment Callback

PayFast will send a callback with the pares. Handle it:

```php
public function handleCallback(Request $request)
{
    $request->validate([
        'pares' => 'required|string',
    ]);

    $response = PayFast::completeTransactionFromPares($request->pares);
    $result = json_decode($response->getContent(), true);

    if ($result['status']) {
        // Payment completed successfully
        $payment = \zfhassaan\Payfast\Models\ProcessPayment::where('data_3ds_pares', $request->pares)->first();
        
        // Update your order status, send confirmation email, etc.
        
        return response()->json([
            'status' => 'success',
            'message' => 'Payment completed',
            'transaction_id' => $payment->transaction_id,
        ]);
    }

    return response()->json($result, 400);
}
```

## Mobile Wallet Payments

### EasyPaisa Payment

```php
public function payWithEasyPaisa(Request $request)
{
    $paymentData = [
        'basket_id' => 'ORD-' . time(),
        'txnamt' => 1000.00,
        'customer_mobile_no' => '03001234567',
        'customer_email_address' => 'customer@example.com',
        'order_date' => now()->toDateString(),
    ];

    $response = PayFast::payWithEasyPaisa($paymentData);
    $result = json_decode($response, true);

    if ($result['status'] && $result['code'] === '00') {
        $transactionId = $result['data']['transaction_id'];
        $paymentId = $result['data']['payment_id'];
        
        return redirect('/otp-screen')->with([
            'transaction_id' => $transactionId,
            'payment_id' => $paymentId,
        ]);
    }

    return back()->withErrors(['payment' => $result['message'] ?? 'Payment failed']);
}
```

### UPaisa Payment

```php
public function payWithUPaisa(Request $request)
{
    $paymentData = [
        'basket_id' => 'ORD-' . time(),
        'txnamt' => 1000.00,
        'customer_mobile_no' => '03001234567',
        'customer_email_address' => 'customer@example.com',
        'order_date' => now()->toDateString(),
    ];

    $response = PayFast::payWithUPaisa($paymentData);
    // Same flow as EasyPaisa
}
```

## Complete Example Controller

Here's a complete example controller:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use zfhassaan\Payfast\Facades\PayFast;
use zfhassaan\Payfast\Models\ProcessPayment;

class PaymentController extends Controller
{
    /**
     * Initiate card payment
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'mobile' => 'required|string',
            'email' => 'required|email',
            'card_number' => 'required|string',
            'expiry_month' => 'required|string',
            'expiry_year' => 'required|string',
            'cvv' => 'required|string',
        ]);

        $paymentData = [
            'orderNumber' => $request->order_number,
            'transactionAmount' => $request->amount,
            'customerMobileNo' => $request->mobile,
            'customer_email' => $request->email,
            'cardNumber' => $request->card_number,
            'expiry_month' => $request->expiry_month,
            'expiry_year' => $request->expiry_year,
            'cvv' => $request->cvv,
        ];

        $response = PayFast::getOTPScreen($paymentData);
        $result = json_decode($response->getContent(), true);

        if ($result['status']) {
            return redirect('/otp-screen')->with([
                'transaction_id' => $result['data']['transaction_id'],
                'payment_id' => $result['data']['payment_id'],
            ]);
        }

        return back()->withErrors(['payment' => $result['message']]);
    }

    /**
     * Verify OTP
     */
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'otp' => 'required|string',
            'pares' => 'required|string',
        ]);

        $response = PayFast::verifyOTPAndStorePares(
            $request->transaction_id,
            $request->otp,
            $request->pares
        );

        $result = json_decode($response->getContent(), true);

        if ($result['status']) {
            return response()->json([
                'message' => 'OTP verified. Waiting for payment confirmation...',
            ]);
        }

        return response()->json($result, 400);
    }

    /**
     * Handle payment callback
     */
    public function handleCallback(Request $request)
    {
        $request->validate([
            'pares' => 'required|string',
        ]);

        $response = PayFast::completeTransactionFromPares($request->pares);
        $result = json_decode($response->getContent(), true);

        if ($result['status']) {
            $payment = ProcessPayment::where('data_3ds_pares', $request->pares)->first();
            
            if ($payment) {
                // Update your order status
                // Send confirmation email
                // etc.
            }
            
            return response()->json([
                'status' => 'success',
                'transaction_id' => $payment->transaction_id ?? null,
            ]);
        }

        return response()->json($result, 400);
    }

    /**
     * Check payment status
     */
    public function checkStatus($transactionId)
    {
        $response = PayFast::getTransactionDetails($transactionId);
        $result = json_decode($response->getContent(), true);

        return response()->json($result);
    }
}
```

## Routes Setup

Add routes to `routes/web.php`:

```php
Route::post('/payment/initiate', [PaymentController::class, 'initiatePayment']);
Route::post('/payment/verify-otp', [PaymentController::class, 'verifyOTP']);
Route::post('/payment/callback', [PaymentController::class, 'handleCallback']);
Route::get('/payment/status/{transactionId}', [PaymentController::class, 'checkStatus']);
```

## Response Format

All methods return a standardized JSON response:

### Success Response

```json
{
    "status": true,
    "data": {
        "token": "abc123...",
        "transaction_id": "TXN123456",
        "payment_id": 1
    },
    "message": "Operation successful",
    "code": "00"
}
```

### Error Response

```json
{
    "status": false,
    "data": [],
    "message": "Error message",
    "code": "ERROR_CODE"
}
```

## Error Handling

Always check the response status and handle errors:

```php
$response = PayFast::getToken();
$result = json_decode($response->getContent(), true);

if (!$result['status']) {
    // Handle error
    $errorCode = $result['code'];
    $errorMessage = $result['message'];
    
    // Log error
    \Log::error('PayFast Error', [
        'code' => $errorCode,
        'message' => $errorMessage,
    ]);
    
    // Return error to user
    return back()->withErrors(['payment' => $errorMessage]);
}
```

## Next Steps

- [Payment Flows](Payment-Flows.md) - Understand complete payment flows
- [API Reference](API-Reference.md) - Explore all available methods
- [IPN Handling](IPN-Handling.md) - Set up webhook notifications















