# Payment Flows

This document explains the complete payment flows supported by the PayFast package, including card payments, mobile wallet payments, and the OTP verification process.

## Overview

The PayFast package supports two main payment methods:

1. **Direct Checkout** - PCI DSS compliant card payments
2. **Hosted Checkout** - Redirect-based payment processing
3. **Mobile Wallets** - EasyPaisa, UPaisa, JazzCash

## Card Payment Flow

### Complete Flow Diagram

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

### Step-by-Step Implementation

#### Step 1: Customer Validation

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

#### Step 2: OTP Verification Screen

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

#### Step 3: PayFast Callback Handler

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

## Mobile Wallet Payment Flow

### EasyPaisa Payment

```php
$paymentData = [
    'basket_id' => 'ORD-12345',
    'txnamt' => 1000.00,
    'customer_mobile_no' => '03001234567',
    'customer_email_address' => 'customer@example.com',
    'order_date' => now()->toDateString(),
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

The `ProcessPayment` model tracks payment status through the following states:

### Status Constants

```php
ProcessPayment::STATUS_PENDING      // Initial state
ProcessPayment::STATUS_VALIDATED    // Customer validated, waiting for OTP
ProcessPayment::STATUS_OTP_VERIFIED // OTP verified, pares stored, waiting for callback
ProcessPayment::STATUS_COMPLETED   // Transaction completed successfully
ProcessPayment::STATUS_FAILED      // Transaction failed
ProcessPayment::STATUS_CANCELLED    // Payment cancelled
```

### Status Flow

```
pending → validated → otp_verified → completed
                ↓
            failed/cancelled
```

### Checking Payment Status

```php
use zfhassaan\Payfast\Models\ProcessPayment;

$payment = ProcessPayment::where('transaction_id', 'TXN123456')->first();

if ($payment->isCompleted()) {
    // Payment completed
}

if ($payment->isFailed()) {
    // Payment failed
}

if ($payment->isOtpVerified()) {
    // OTP verified, waiting for callback
}
```

## Database Schema

The `payfast_process_payments_table` stores payment records:

```php
payfast_process_payments_table:
- id (primary key)
- uid (UUID)
- token (authentication token)
- orderNo (order number/basket ID)
- data_3ds_secureid (3DS secure ID)
- data_3ds_pares (3DS pares - stored after OTP verification)
- transaction_id (PayFast transaction ID)
- status (enum: pending, validated, otp_verified, completed, failed, cancelled)
- payment_method (card, easypaisa, jazzcash, upaisa)
- payload (JSON - stores validation response and user request)
- requestData (JSON - original request data)
- otp_verified_at (timestamp)
- completed_at (timestamp)
- created_at
- updated_at
- deleted_at (soft delete)
```

## Payment Methods

### Card Payment

```php
ProcessPayment::METHOD_CARD
```

- Requires card number, expiry, CVV
- Uses 3DS authentication
- Requires OTP verification

### EasyPaisa

```php
ProcessPayment::METHOD_EASYPAISA
```

- Bank code: 13
- Mobile wallet payment
- Requires mobile number

### UPaisa

```php
ProcessPayment::METHOD_UPAISA
```

- Bank code: 14
- Mobile wallet payment
- Requires mobile number

### JazzCash

```php
ProcessPayment::METHOD_JAZZCASH
```

- Mobile wallet payment
- Requires mobile number

## Console Command for Payment Checking

Check pending payments using the console command:

```bash
# Check all pending and validated payments
php artisan payfast:check-pending-payments

# Check specific status
php artisan payfast:check-pending-payments --status=otp_verified

# Limit results
php artisan payfast:check-pending-payments --limit=10

# Skip email notifications
php artisan payfast:check-pending-payments --no-email
```

## Complete Example Controller

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

## Next Steps

- [API Reference](API-Reference.md) - Explore all available methods
- [IPN Handling](IPN-Handling.md) - Set up webhook notifications
- [Events and Listeners](Events-and-Listeners.md) - Understand event system

