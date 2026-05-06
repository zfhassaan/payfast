# IPN (Instant Payment Notification) Handling

The IPN service handles webhook notifications from PayFast to update payment statuses automatically.

## Overview

IPN (Instant Payment Notification) is a webhook system that PayFast uses to notify your application about payment status changes. The PayFast package provides:

- **Built-in IPN route** — A pre-registered `POST /api/payfast/ipn` endpoint
- **Automatic `checkout_url` injection** — Every outgoing API request includes the IPN endpoint URL
- **IPN logging** — All notifications are stored in the `payfast_ipn_table`
- **Payment status updates** — Automatically updates payment records based on IPN data
- **Event dispatching** — Fires `PaymentCompleted` or `PaymentFailed` events
- **Idempotency** — Prevents duplicate processing of the same notification

## How It Works

When you initiate any payment through the package (card payments, wallet payments, etc.), the `checkout_url` parameter is **automatically injected** into the request payload sent to PayFast. This tells PayFast where to send IPN callbacks when the payment status changes.

> 1. **Your App** → PayFast API *(payment request includes checkout_url)*
> 2. **PayFast** processes the payment
> 3. **PayFast** → `POST /api/payfast/ipn` *(sends IPN to your checkout_url)*
> 4. **Package** validates, logs, and updates payment status
> 5. **Events** are dispatched (`PaymentCompleted` / `PaymentFailed`)

## Setup

### Step 1: Configure the Checkout URL

Add `PAYFAST_CHECKOUT_URL` to your `.env` file:

```env
# Option 1: Explicit URL (recommended for production)
PAYFAST_CHECKOUT_URL=https://yourdomain.com/api/payfast/ipn

# Option 2: Leave empty to auto-resolve from route('payfast.ipn.handle')
# PAYFAST_CHECKOUT_URL=
```

> **Note**: If `PAYFAST_CHECKOUT_URL` is not set, the package will auto-resolve the URL using Laravel's `route()` helper and your `APP_URL`. For production environments, it is recommended to set this explicitly.

### Step 2: That's It!

The package automatically:
- Registers the `POST /api/payfast/ipn` route (named `payfast.ipn.handle`)
- Excludes the route from CSRF verification (it uses the `api` middleware group)
- Injects the `checkout_url` into every outgoing PayFast API request
- Processes incoming IPN notifications and updates payment statuses

No manual controller creation, route registration, or CSRF exclusion is needed.

## IPN Data Structure

PayFast sends IPN notifications with various field names. The service handles:

- `transaction_id` or `TRANSACTION_ID`
- `order_no` or `ORDER_NO` or `basket_id` or `BASKET_ID`
- `status` or `STATUS` or `code` or `CODE`
- `amount` or `AMOUNT` or `txnamt` or `TXNAMT`
- `currency` or `CURRENCY` or `currency_code` or `CURRENCY_CODE`

### Example IPN Data

```php
[
    'transaction_id' => 'TXN123456',
    'order_no' => 'ORD-12345',
    'status' => '00',
    'amount' => '1000.00',
    'currency' => 'PKR',
]
```

## Status Mapping

The service automatically maps IPN statuses to payment statuses:

| IPN Status | Payment Status |
|---|---|
| `00`, `completed`, `success` | `completed` |
| `failed`, `failure` | `failed` |
| `cancelled`, `cancel` | `cancelled` |

## What Happens When IPN is Received

1. **Logging**: Incoming request is logged via the `payfast` log channel
2. **Validation**: IPN data is validated (checks for required fields)
3. **Idempotency Check**: Checks if IPN was already processed
4. **IPN Logging**: Creates an entry in `payfast_ipn_table`
5. **Payment Update**: Finds and updates the payment record
6. **Event Dispatch**: Dispatches `PaymentCompleted` or `PaymentFailed` events
7. **Email Notifications**: Email notifications are sent automatically (via listeners)

## Response Format

### Success Response

```json
{
    "status": true,
    "data": {
        "ipn_log_id": 123,
        "transaction_id": "TXN123456",
        "order_no": "ORD-12345",
        "payment_updated": true
    },
    "code": "00"
}
```

### Error Response

```json
{
    "status": false,
    "data": [],
    "message": "Invalid IPN data",
    "code": "INVALID_IPN"
}
```

## Custom IPN Handling (Optional)

If you need custom logic beyond what the built-in controller provides, you have two options:

### Option 1: Listen to Events

The recommended approach — listen to payment events in your application:

```php
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;

// In your EventServiceProvider or via Event::listen()
Event::listen(PaymentCompleted::class, function ($event) {
    $paymentData = $event->paymentData;
    // Update order status, send notifications, etc.
});

Event::listen(PaymentFailed::class, function ($event) {
    // Handle failed payment
});
```

### Option 2: Custom Controller

Override the built-in IPN handling by creating your own controller:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use zfhassaan\Payfast\Facades\Payfast;

class PaymentController extends Controller
{
    public function handleIPN(Request $request)
    {
        // Custom pre-processing logic
        $ipnData = $request->all();

        Log::channel('payfast')->info('Custom IPN Handler', [
            'ip' => $request->ip(),
            'data' => $ipnData,
        ]);

        // Delegate to PayFast service
        $response = Payfast::handleIPN($ipnData);

        // Custom post-processing logic
        return $response;
    }
}
```

Then register your custom route (this will take priority if you set `PAYFAST_CHECKOUT_URL` to your custom endpoint):

```php
// routes/api.php
Route::post('/payment/ipn', [PaymentController::class, 'handleIPN']);
```

Update your `.env`:
```env
PAYFAST_CHECKOUT_URL=https://yourdomain.com/api/payment/ipn
```

### Option 3: Extend the IPN Service

For advanced customization, extend the IPN service:

```php
use zfhassaan\Payfast\Services\IPNService;

class CustomIPNService extends IPNService
{
    public function processIPN(array $data): array
    {
        // Custom pre-processing
        $result = parent::processIPN($data);
        // Custom post-processing
        return $result;
    }
}
```

Then bind it in a service provider:

```php
$this->app->singleton(IPNServiceInterface::class, CustomIPNService::class);
```

## Security Considerations

### 1. IP Whitelisting

For additional security, consider whitelisting PayFast IP addresses in your custom controller:

```php
public function handleIPN(Request $request)
{
    $allowedIPs = [
        '203.0.113.0', // PayFast IP 1
        '203.0.113.1', // PayFast IP 2
        // Add more PayFast IPs
    ];

    if (!in_array($request->ip(), $allowedIPs)) {
        Log::warning('IPN from unauthorized IP', ['ip' => $request->ip()]);
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    return Payfast::handleIPN($request->all());
}
```

### 2. Signature Verification

If PayFast provides signature verification, implement it:

```php
public function handleIPN(Request $request)
{
    $signature = $request->header('X-PayFast-Signature');
    $payload = $request->getContent();

    $expectedSignature = hash_hmac('sha256', $payload, config('payfast.secured_key'));

    if (!hash_equals($expectedSignature, $signature)) {
        return response()->json(['error' => 'Invalid signature'], 403);
    }

    return Payfast::handleIPN($request->all());
}
```

### 3. HTTPS

The IPN endpoint should always be served over HTTPS in production.

## Testing

### Test Your IPN Endpoint

Using curl:

```bash
curl -X POST https://yourdomain.com/api/payfast/ipn \
  -H "Content-Type: application/json" \
  -d '{
    "transaction_id": "TXN123456",
    "order_no": "ORD-12345",
    "status": "00",
    "amount": "1000.00",
    "currency": "PKR"
  }'
```

### Using Postman

1. Create a POST request to `https://yourdomain.com/api/payfast/ipn`
2. Set Content-Type to `application/json`
3. Add IPN data in the body
4. Send request

## Database Queries

### Check IPN Logs

```php
use zfhassaan\Payfast\Models\IPNLog;

// Get all IPN logs
$logs = IPNLog::all();

// Get IPN by transaction ID
$log = IPNLog::where('transaction_id', 'TXN123456')->first();

// Get IPN logs for an order
$logs = IPNLog::where('order_no', 'ORD-12345')->get();

// Get recent IPN logs
$logs = IPNLog::orderBy('created_at', 'desc')->limit(10)->get();
```

### Check Payment Status Updates

```php
use zfhassaan\Payfast\Models\ProcessPayment;

// Check payment status
$payment = ProcessPayment::where('transaction_id', 'TXN123456')->first();
echo $payment->status; // completed, failed, cancelled, etc.

// Get payments updated by IPN
$payments = ProcessPayment::where('status', 'completed')
    ->whereNotNull('completed_at')
    ->get();
```

## Troubleshooting

### IPN Not Received

1. **Check `PAYFAST_CHECKOUT_URL`**: Verify it is set correctly in your `.env`
2. **Verify route exists**: Run `php artisan route:list --name=payfast` to confirm the route is registered
3. **Check Server Logs**: Look for incoming requests in your application logs
4. **Test Endpoint**: Use curl or Postman to test the endpoint directly
5. **Check Firewall**: Ensure PayFast IPs are not blocked
6. **Check SSL**: Ensure HTTPS is working correctly

### Payment Not Updated

1. **Check Logs**: Look for IPN processing errors in the `payfast` log channel
2. **Verify Transaction ID**: Ensure `transaction_id` matches
3. **Check Order Number**: Ensure `order_no`/`basket_id` matches
4. **Check Status Mapping**: Verify status is being mapped correctly

### Duplicate Processing

The service includes idempotency checks. If you're still seeing duplicates:

1. **Check IPN Logs**: Verify if IPN was already processed
2. **Check Database**: Look for duplicate entries
3. **Review Code**: Ensure idempotency logic is working

## Best Practices

1. **Use explicit `PAYFAST_CHECKOUT_URL`** in production environments
2. **Always log IPN requests** for debugging (done automatically by the package)
3. **Implement IP whitelisting** for additional security
4. **Use HTTPS** for IPN endpoints
5. **Handle errors gracefully** and return appropriate status codes
6. **Monitor IPN logs** regularly
7. **Test in sandbox** before going to production

## Next Steps

- [Events and Listeners](Events-and-Listeners.md) - Understand event system
- [Models and Database](Models-and-Database.md) - Database schema
- [Troubleshooting](Troubleshooting.md) - Common issues and solutions
