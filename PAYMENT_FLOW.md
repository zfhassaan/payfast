# Payment Flow with OTP Verification and 3DS Pares

## Overview

This document explains the complete payment flow with payment holding, OTP verification, and 3DS pares callback handling.

## Payment Flow Diagram

```
1. Customer Initiates Payment
   ↓
2. Validate Customer (getOTPScreen)
   ↓
3. Payment Stored in DB (status: validated)
   ↓
4. Redirect to OTP Screen
   ↓
5. Customer Enters OTP
   ↓
6. Verify OTP & Store Pares (verifyOTPAndStorePares)
   ↓
7. Payment Updated (status: otp_verified, pares stored)
   ↓
8. PayFast Callback with Pares
   ↓
9. Complete Transaction (completeTransactionFromPares)
   ↓
10. Payment Completed (status: completed)
```

## Step-by-Step Implementation

### 1. Customer Validation (Card Payment)

```php
use zfhassaan\Payfast\Facades\PayFast;

$paymentData = [
    'orderNumber' => 'ORD-12345',
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

if ($result['status']) {
    // Payment stored in DB with status: 'validated'
    $transactionId = $result['data']['transaction_id'];
    $paymentId = $result['data']['payment_id'];
    
    // Redirect to OTP screen
    $redirectUrl = $result['data']['redirect_url'] ?? '/otp-screen';
    
    return redirect($redirectUrl)->with([
        'transaction_id' => $transactionId,
        'payment_id' => $paymentId,
    ]);
}
```

### 2. OTP Verification Screen

Create a route and controller for OTP verification:

```php
// routes/web.php
Route::post('/payment/verify-otp', [PaymentController::class, 'verifyOTP']);

// app/Http/Controllers/PaymentController.php
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
        // Payment updated with status: 'otp_verified' and pares stored
        return response()->json([
            'message' => 'OTP verified successfully',
            'pares' => $request->pares,
        ]);
    }

    return response()->json($result, 400);
}
```

### 3. PayFast Callback Handler

Create a callback route that PayFast will call with the pares:

```php
// routes/web.php
Route::post('/payment/callback', [PaymentController::class, 'handleCallback']);

// app/Http/Controllers/PaymentController.php
public function handleCallback(Request $request)
{
    $request->validate([
        'pares' => 'required|string',
    ]);

    // Complete transaction using stored pares
    $response = PayFast::completeTransactionFromPares($request->pares);
    $result = json_decode($response->getContent(), true);

    if ($result['status']) {
        // Payment completed successfully
        // Payment status updated to 'completed' in DB
        
        // Get payment record
        $payment = \zfhassaan\Payfast\Models\ProcessPayment::where('data_3ds_pares', $request->pares)->first();
        
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

## Wallet Payment Flow (EasyPaisa, JazzCash, UPaisa)

### EasyPaisa Payment

```php
$paymentData = [
    'basket_id' => 'ORD-12345',
    'txnamt' => 1000.00,
    'customer_mobile_no' => '03001234567',
    'customer_email_address' => 'customer@example.com',
    // ... other required fields
];

$response = PayFast::payWithEasyPaisa($paymentData);
$result = json_decode($response, true);

if ($result['status']) {
    // Payment stored in DB with status: 'validated'
    // Same flow as card payment - redirect to OTP screen
    $transactionId = $result['data']['transaction_id'];
    $paymentId = $result['data']['payment_id'];
    
    return redirect('/otp-screen')->with([
        'transaction_id' => $transactionId,
        'payment_id' => $paymentId,
    ]);
}
```

### UPaisa Payment

```php
$response = PayFast::payWithUPaisa($paymentData);
// Same flow as EasyPaisa
```

## Payment Status Tracking

The `ProcessPayment` model tracks payment status:

- `pending` - Initial state
- `validated` - Customer validated, waiting for OTP
- `otp_verified` - OTP verified, pares stored, waiting for callback
- `completed` - Transaction completed successfully
- `failed` - Transaction failed
- `cancelled` - Payment cancelled

## Database Schema

```php
payfast_process_payments_table:
- id
- uid (UUID)
- token
- orderNo
- data_3ds_secureid
- data_3ds_pares (stored after OTP verification)
- transaction_id
- status (enum: pending, validated, otp_verified, completed, failed, cancelled)
- payment_method (card, easypaisa, jazzcash, upaisa)
- payload (JSON)
- requestData (JSON)
- otp_verified_at
- completed_at
- created_at
- updated_at
- deleted_at
```

## Console Command

Check pending payments:

```bash
# Check all pending and validated payments
php artisan payfast:check-pending-payments

# Check specific status
php artisan payfast:check-pending-payments --status=otp_verified

# Limit results
php artisan payfast:check-pending-payments --limit=10
```

## Example Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use zfhassaan\Payfast\Facades\PayFast;
use zfhassaan\Payfast\Models\ProcessPayment;

class PaymentController extends Controller
{
    public function initiatePayment(Request $request)
    {
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

    public function verifyOTP(Request $request)
    {
        $response = PayFast::verifyOTPAndStorePares(
            $request->transaction_id,
            $request->otp,
            $request->pares
        );

        $result = json_decode($response->getContent(), true);

        if ($result['status']) {
            return response()->json([
                'message' => 'OTP verified. Waiting for payment confirmation...',
                'pares' => $request->pares,
            ]);
        }

        return response()->json($result, 400);
    }

    public function handleCallback(Request $request)
    {
        $response = PayFast::completeTransactionFromPares($request->pares);
        $result = json_decode($response->getContent(), true);

        if ($result['status']) {
            $payment = ProcessPayment::where('data_3ds_pares', $request->pares)->first();
            
            // Update your order
            // Send confirmation email
            // etc.
            
            return response()->json([
                'status' => 'success',
                'transaction_id' => $payment->transaction_id,
            ]);
        }

        return response()->json($result, 400);
    }
}
```

## Security Considerations

1. **Validate all inputs** - Always validate transaction_id, OTP, and pares
2. **Verify payment status** - Check payment status before processing
3. **Use HTTPS** - Always use HTTPS for payment callbacks
4. **Verify pares** - Validate pares format and ensure it's from PayFast
5. **Idempotency** - Handle duplicate callbacks gracefully

## Error Handling

```php
try {
    $response = PayFast::completeTransactionFromPares($pares);
    $result = json_decode($response->getContent(), true);
    
    if (!$result['status']) {
        // Log error
        \Log::error('Payment completion failed', [
            'pares' => $pares,
            'error' => $result['message'],
            'code' => $result['code'],
        ]);
        
        // Update payment status
        $payment = ProcessPayment::where('data_3ds_pares', $pares)->first();
        if ($payment) {
            $payment->markAsFailed($result['message']);
        }
    }
} catch (\Exception $e) {
    \Log::error('Payment completion exception', [
        'pares' => $pares,
        'exception' => $e->getMessage(),
    ]);
}
```


