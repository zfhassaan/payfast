# IPN (Instant Payment Notification) Service Usage

## Overview

The IPN service handles webhook notifications from PayFast to update payment statuses automatically. This service:
- Logs all IPN notifications
- Updates payment status based on IPN data
- Dispatches events for completed/failed payments
- Prevents duplicate processing (idempotency)

## Setup

The IPN service is already registered in the service provider and ready to use.

## Controller Implementation

Add this method to your controller to handle IPN webhooks:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use zfhassaan\Payfast\Facades\Payfast;
use zfhassaan\Payfast\Helpers\Utility;

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

## PayFast Configuration

Configure your IPN URL in PayFast dashboard:
- Production: `https://yourdomain.com/payment/ipn`
- Sandbox: `https://yourdomain.com/payment/ipn`

## IPN Data Structure

PayFast sends IPN notifications with various field names. The service handles:
- `transaction_id` or `TRANSACTION_ID`
- `order_no` or `ORDER_NO` or `basket_id` or `BASKET_ID`
- `status` or `STATUS` or `code` or `CODE`
- `amount` or `AMOUNT` or `txnamt` or `TXNAMT`
- `currency` or `CURRENCY` or `currency_code` or `CURRENCY_CODE`

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

## Example Response

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

1. **IP Whitelisting**: Consider whitelisting PayFast IP addresses
2. **Signature Verification**: Add signature verification if PayFast provides it
3. **CSRF Protection**: Disable CSRF for IPN endpoint:
   ```php
   // In app/Http/Middleware/VerifyCsrfToken.php
   protected $except = [
       'payment/ipn',
   ];
   ```

## Testing

Test your IPN endpoint:

```bash
# Using curl
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
```

### Check Payment Status Updates
```php
use zfhassaan\Payfast\Models\ProcessPayment;

// Check payment status
$payment = ProcessPayment::where('transaction_id', 'TXN123456')->first();
echo $payment->status; // completed, failed, cancelled, etc.
```

## Troubleshooting

1. **IPN Not Received**: Check PayFast dashboard for IPN URL configuration
2. **Payment Not Updated**: Check logs for transaction_id/order_no matching
3. **Duplicate Processing**: Check idempotency - IPN is only processed once per transaction_id

