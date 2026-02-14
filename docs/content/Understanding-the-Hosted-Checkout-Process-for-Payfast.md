# Understanding the Hosted Checkout Process for Payfast

## Introduction

The Hosted Checkout process for Payfast provides a secure and convenient method for merchants to accept online payments. By following a few simple steps, merchants can integrate Payfast into their websites and offer their customers a seamless payment experience. This guide uses the PayFast Laravel package for implementation.

## Overview

Hosted Checkout redirects customers to PayFast's secure payment page where they complete the payment. This method is ideal for merchants who want to avoid PCI DSS compliance requirements as card data is never handled on their servers.

## Installation

First, install the package:

```bash
composer require zfhassaan/payfast
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=payfast-config
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

## Step 1: Setting Up Merchant Data

Get your merchant credentials from PayFast dashboard:

```php
$merchant_id = config('payfast.merchant_id');
$secured_key = config('payfast.secured_key');
$merchant_name = 'Your Merchant Name'; // Your registered merchant name
```

## Step 2: Collecting Customer Data

Gather customer and order information:

```php
$order_id = 'ORD-' . time(); // Or use your order ID generation logic
$amount = 1000.00; // Transaction amount
$mobile = "03001234567"; // Customer mobile number
$email = 'customer@example.com'; // Customer email
```

## Step 3: Generating the Payment Token

Get an access token from PayFast:

```php
use zfhassaan\Payfast\Facades\PayFast;

// Get authentication token
$tokenResponse = PayFast::getToken();
$tokenData = json_decode($tokenResponse->getContent(), true);

if ($tokenData['status'] && $tokenData['code'] === '00') {
    $ACCESS_TOKEN = $tokenData['data']['token'];
} else {
    // Handle error
    abort(403, 'Error: Auth Token Not Generated.');
}
```

Or using the package's helper:

```php
$payfast = app('payfast');
$response = $payfast->getToken();
$tokenData = json_decode($response->getContent(), true);
$ACCESS_TOKEN = $tokenData['data']['token'] ?? null;
```

## Step 4: Creating the Signature

Generate a signature using MD5 hash:

```php
$signature = md5($merchant_id . ":" . $merchant_name . ":" . $amount . ":" . $order_id);
$backend_callback = "signature=" . $signature . "&order_id=" . $order_id;
```

## Step 5: Constructing the Payload

Build the payload array with all required parameters:

```php
$successUrl = route('payment.success'); // Your success URL
$failUrl = route('payment.failure'); // Your failure URL
$payment_url = config('payfast.mode') === 'production' 
    ? config('payfast.api_url') . '/checkout' 
    : config('payfast.sandbox_api_url') . '/checkout';

$payload = [
    'MERCHANT_ID' => $merchant_id,
    'MERCHANT_NAME' => $merchant_name,
    'TOKEN' => $ACCESS_TOKEN,
    'PROCCODE' => '00',
    'TXNAMT' => $amount,
    'CUSTOMER_MOBILE_NO' => $mobile,
    'CUSTOMER_EMAIL_ADDRESS' => $email,
    'SIGNATURE' => $signature,
    'VERSION' => 'WOOCOM-APPS-PAYMENT-0.9',
    'TXNDESC' => 'Products purchased from ' . $merchant_name,
    'SUCCESS_URL' => urlencode($successUrl),
    'FAILURE_URL' => urlencode($failUrl),
    'BASKET_ID' => $order_id,
    'ORDER_DATE' => date('Y-m-d H:i:s', time()),
    'CHECKOUT_URL' => urlencode($backend_callback),
];
```

## Step 6: Preparing the Payment Form

Generate the payment form with hidden fields:

```php
$payfast_form = '<form action="' . $payment_url . '" method="post" id="payfast_woocom_form">';

foreach ($payload as $key => $value) {
    $payfast_form .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '" />';
}

$payfast_form .= '<input type="submit" class="button payfast-submit" name="submit" value="Proceed to Payment" />';
$payfast_form .= '</form>';

// Return or display the form
return view('payment.form', ['form' => $payfast_form]);
```

Or using Blade template:

```blade
<!-- resources/views/payment/form.blade.php -->
<form action="{{ $payment_url }}" method="post" id="payfast_woocom_form">
    @foreach($payload as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
    @endforeach
    <button type="submit" class="btn btn-primary">Proceed to Payment</button>
</form>
```

## Complete Controller Implementation

Here's a complete controller example:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use zfhassaan\Payfast\Facades\PayFast;

class PaymentController extends Controller
{
    public function initiateHostedCheckout(Request $request)
    {
        // Validate request
        $request->validate([
            'order_id' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'mobile' => 'required|string',
            'email' => 'required|email',
        ]);

        // Get merchant data
        $merchant_id = config('payfast.merchant_id');
        $secured_key = config('payfast.secured_key');
        $merchant_name = 'Your Merchant Name';

        // Customer data
        $order_id = $request->order_id;
        $amount = $request->amount;
        $mobile = $request->mobile;
        $email = $request->email;

        // Get access token
        $tokenResponse = PayFast::getToken();
        $tokenData = json_decode($tokenResponse->getContent(), true);

        if (!$tokenData['status'] || $tokenData['code'] !== '00') {
            return back()->withErrors(['payment' => 'Failed to get authentication token']);
        }

        $ACCESS_TOKEN = $tokenData['data']['token'];

        // Create signature
        $signature = md5($merchant_id . ":" . $merchant_name . ":" . $amount . ":" . $order_id);
        $backend_callback = "signature=" . $signature . "&order_id=" . $order_id;

        // Build payload
        $successUrl = route('payment.success');
        $failUrl = route('payment.failure');
        $payment_url = config('payfast.mode') === 'production' 
            ? config('payfast.api_url') . '/checkout' 
            : config('payfast.sandbox_api_url') . '/checkout';

        $payload = [
            'MERCHANT_ID' => $merchant_id,
            'MERCHANT_NAME' => $merchant_name,
            'TOKEN' => $ACCESS_TOKEN,
            'PROCCODE' => '00',
            'TXNAMT' => $amount,
            'CUSTOMER_MOBILE_NO' => $mobile,
            'CUSTOMER_EMAIL_ADDRESS' => $email,
            'SIGNATURE' => $signature,
            'VERSION' => 'WOOCOM-APPS-PAYMENT-0.9',
            'TXNDESC' => 'Products purchased from ' . $merchant_name,
            'SUCCESS_URL' => urlencode($successUrl),
            'FAILURE_URL' => urlencode($failUrl),
            'BASKET_ID' => $order_id,
            'ORDER_DATE' => date('Y-m-d H:i:s', time()),
            'CHECKOUT_URL' => urlencode($backend_callback),
        ];

        return view('payment.hosted-checkout', [
            'payment_url' => $payment_url,
            'payload' => $payload,
        ]);
    }

    public function handleSuccess(Request $request)
    {
        // Verify signature
        $signature = $request->input('signature');
        $order_id = $request->input('order_id');

        // Verify the signature matches
        // Update order status
        // Send confirmation email
        // etc.

        return view('payment.success', [
            'order_id' => $order_id,
        ]);
    }

    public function handleFailure(Request $request)
    {
        // Handle payment failure
        // Log the failure
        // Notify customer
        // etc.

        return view('payment.failure');
    }
}
```

## Routes Setup

Add routes to `routes/web.php`:

```php
Route::post('/payment/hosted-checkout', [PaymentController::class, 'initiateHostedCheckout'])->name('payment.hosted-checkout');
Route::get('/payment/success', [PaymentController::class, 'handleSuccess'])->name('payment.success');
Route::get('/payment/failure', [PaymentController::class, 'handleFailure'])->name('payment.failure');
```

## Handling Callbacks

### Success Callback

```php
public function handleSuccess(Request $request)
{
    // Verify signature
    $receivedSignature = $request->input('signature');
    $order_id = $request->input('order_id');
    
    // Recalculate signature to verify
    $payment = Payment::where('order_id', $order_id)->first();
    $expectedSignature = md5(
        config('payfast.merchant_id') . ":" . 
        config('payfast.merchant_name') . ":" . 
        $payment->amount . ":" . 
        $order_id
    );

    if ($receivedSignature === $expectedSignature) {
        // Payment successful
        $payment->update(['status' => 'completed']);
        
        // Update order, send email, etc.
        return view('payment.success');
    }

    return redirect()->route('payment.failure');
}
```

### Failure Callback

```php
public function handleFailure(Request $request)
{
    $order_id = $request->input('order_id');
    
    // Update payment status
    $payment = Payment::where('order_id', $order_id)->first();
    if ($payment) {
        $payment->update(['status' => 'failed']);
    }

    return view('payment.failure');
}
```

## Security Considerations

1. **Always verify signatures** - Don't trust callback data without verification
2. **Use HTTPS** - Ensure all payment URLs use HTTPS
3. **Validate all inputs** - Sanitize and validate callback data
4. **Log transactions** - Keep audit trail of all payment attempts
5. **Handle errors gracefully** - Don't expose sensitive information

## Finalizing the Integration

Once the payment form is generated:

1. **Display the form** to customers at checkout
2. **Handle redirects** - Set up success and failure pages
3. **Verify callbacks** - Always verify signatures on callbacks
4. **Update order status** - Mark orders as paid/failed
5. **Send notifications** - Email customers about payment status

## Auto-Submit Form (Optional)

You can auto-submit the form using JavaScript:

```blade
<form action="{{ $payment_url }}" method="post" id="payfast_woocom_form">
    @foreach($payload as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
    @endforeach
</form>

<script>
    document.getElementById('payfast_woocom_form').submit();
</script>
```

## Conclusion

The Hosted Checkout process for Payfast offers a secure and reliable solution for merchants to accept online payments. By following the steps outlined in this guide and using the PayFast Laravel package, merchants can seamlessly integrate Payfast into their websites and provide customers with a smooth and trustworthy payment experience without handling sensitive card data.

## Additional Resources

- [Understanding the Direct Checkout Process](Understanding-the-Direct-Checkout-Process) - Direct checkout guide
- [Payment Flows](Payment-Flows) - Detailed payment flow documentation
- [IPN Handling](IPN-Handling) - Webhook notifications
- [Troubleshooting](Troubleshooting) - Common issues and solutions















