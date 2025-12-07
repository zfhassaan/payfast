# IPN (Instant Payment Notification) Handling

The IPN service handles webhook notifications from PayFast to update payment statuses automatically.

## Overview

IPN (Instant Payment Notification) is a webhook system that PayFast uses to notify your application about payment status changes. The IPN service:

- Logs all IPN notifications
- Updates payment status based on IPN data
- Dispatches events for completed/failed payments
- Prevents duplicate processing (idempotency)

## Setup

The IPN service is already registered in the service provider and ready to use. You just need to create a controller method and route to handle incoming IPN requests.

## Controller Implementation

Add this method to your controller to handle IPN webhooks:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use zfhassaan\Payfast\Facades\Payfast;

class PaymentController extends Controller
{
    /**
     * Handle IPN webhook from PayFast.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleIPN(Request $request)
    {
        // Get all request data (can be POST or GET)
        $ipnData = $request->all();

        // Log the incoming IPN for debugging
        Log::channel('payfast')->info('IPN Received', [
            'ip' => $request->ip(),
            'data' => $ipnData,
        ]);

        // Process the IPN using PayFast service
        $response = Payfast::handleIPN($ipnData);
        
        // Return response (PayFast expects 200 OK for successful receipt)
        return $response;
    }
}
```

## Route Setup

Add this route to your `routes/web.php` or `routes/api.php`:

```php
// For web routes (recommended for PayFast)
Route::post('/payment/ipn', [PaymentController::class, 'handleIPN']);

// Or for API routes
Route::post('/api/payment/ipn', [PaymentController::class, 'handleIPN'])->middleware('api');
```

**Important**: Disable CSRF protection for the IPN endpoint since PayFast will be calling it from their servers.

### Disable CSRF for IPN Endpoint

In `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'payment/ipn',
    'api/payment/ipn', // If using API route
];
```

## PayFast Configuration

Configure your IPN URL in PayFast dashboard:

- **Production**: `https://yourdomain.com/payment/ipn`
- **Sandbox**: `https://yourdomain.com/payment/ipn`

The IPN URL should be:
- Accessible via HTTPS
- Publicly accessible (not behind authentication)
- Returns 200 OK status for successful processing

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

- `00`, `completed`, `success` → `completed`
- `failed`, `failure` → `failed`
- `cancelled`, `cancel` → `cancelled`

## What Happens When IPN is Received

1. **Validation**: IPN data is validated (checks for required fields)
2. **Idempotency Check**: Checks if IPN was already processed
3. **IPN Logging**: Creates an entry in `payfast_ipn_table`
4. **Payment Update**: Finds and updates the payment record
5. **Event Dispatch**: Dispatches `PaymentCompleted` or `PaymentFailed` events
6. **Email Notifications**: Email notifications are sent automatically (via listeners)

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

## Security Considerations

### 1. IP Whitelisting

Consider whitelisting PayFast IP addresses. Contact PayFast support to get their IP ranges.

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

    // Process IPN
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
    
    // Verify signature
    $expectedSignature = hash_hmac('sha256', $payload, config('payfast.secured_key'));
    
    if (!hash_equals($expectedSignature, $signature)) {
        return response()->json(['error' => 'Invalid signature'], 403);
    }
    
    // Process IPN
    return Payfast::handleIPN($request->all());
}
```

### 3. CSRF Protection

Disable CSRF for IPN endpoint (already mentioned above).

## Testing

### Test Your IPN Endpoint

Using curl:

```bash
curl -X POST https://yourdomain.com/payment/ipn \
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

1. Create a POST request to your IPN URL
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

1. **Check PayFast Dashboard**: Verify IPN URL is configured correctly
2. **Check Server Logs**: Look for incoming requests
3. **Test Endpoint**: Use curl or Postman to test the endpoint
4. **Check Firewall**: Ensure PayFast IPs are not blocked
5. **Check SSL**: Ensure HTTPS is working correctly

### Payment Not Updated

1. **Check Logs**: Look for IPN processing errors
2. **Verify Transaction ID**: Ensure transaction_id matches
3. **Check Order Number**: Ensure order_no/basket_id matches
4. **Check Status Mapping**: Verify status is being mapped correctly

### Duplicate Processing

The service includes idempotency checks. If you're still seeing duplicates:

1. **Check IPN Logs**: Verify if IPN was already processed
2. **Check Database**: Look for duplicate entries
3. **Review Code**: Ensure idempotency logic is working

## Advanced Usage

### Custom IPN Handler

You can extend the IPN service to add custom logic:

```php
use zfhassaan\Payfast\Services\IPNService;

class CustomIPNService extends IPNService
{
    protected function afterPaymentUpdate($payment, $ipnData)
    {
        // Custom logic after payment update
        // e.g., update order status, send SMS, etc.
    }
}
```

Then bind it in a service provider:

```php
$this->app->singleton(IPNServiceInterface::class, CustomIPNService::class);
```

### Event Listeners

IPN processing dispatches events. You can listen to them:

```php
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;

Event::listen(PaymentCompleted::class, function ($event) {
    // Handle payment completion
    $payment = $event->payment;
    // Update order, send notification, etc.
});
```

## Best Practices

1. **Always log IPN requests** for debugging
2. **Implement idempotency** to prevent duplicate processing
3. **Validate IPN data** before processing
4. **Use HTTPS** for IPN endpoints
5. **Handle errors gracefully** and return appropriate status codes
6. **Monitor IPN logs** regularly
7. **Test in sandbox** before going to production

## Next Steps

- [Events and Listeners](Events-and-Listeners.md) - Understand event system
- [Models and Database](Models-and-Database.md) - Database schema
- [Troubleshooting](Troubleshooting.md) - Common issues and solutions

