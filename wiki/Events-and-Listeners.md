# Events and Listeners

The PayFast package uses Laravel's event system to handle side effects and provide extensibility without modifying core code.

## Overview

Events are dispatched at key points in the payment flow, allowing you to:
- Log payment activities
- Send notifications
- Update related records
- Perform analytics
- Integrate with third-party services

## Available Events

### PaymentInitiated

Dispatched when a payment is initiated.

**Event Class**: `zfhassaan\Payfast\Events\PaymentInitiated`

**When Dispatched**:
- When `initiateTransaction()` is called

**Event Data**:
```php
[
    'paymentData' => [...],  // Payment request data
    'response' => [...],      // API response
]
```

**Usage**:
```php
use zfhassaan\Payfast\Events\PaymentInitiated;

Event::listen(PaymentInitiated::class, function ($event) {
    $paymentData = $event->paymentData;
    $response = $event->response;
    
    // Log payment initiation
    Log::info('Payment initiated', [
        'order' => $paymentData['orderNumber'],
        'amount' => $paymentData['transactionAmount'],
    ]);
});
```

### PaymentValidated

Dispatched when customer validation is successful.

**Event Class**: `zfhassaan\Payfast\Events\PaymentValidated`

**When Dispatched**:
- After successful `getOTPScreen()` call
- After successful wallet validation

**Event Data**:
```php
[
    'paymentData' => [...],  // Payment request data
    'validationResponse' => [...], // Validation response
]
```

**Usage**:
```php
use zfhassaan\Payfast\Events\PaymentValidated;

Event::listen(PaymentValidated::class, function ($event) {
    $paymentData = $event->paymentData;
    $response = $event->validationResponse;
    
    // Send validation notification
    Mail::to($paymentData['customer_email'])->send(new PaymentValidatedMail($paymentData));
});
```

### PaymentCompleted

Dispatched when a payment is completed successfully.

**Event Class**: `zfhassaan\Payfast\Events\PaymentCompleted`

**When Dispatched**:
- After successful `completeTransactionFromPares()` call
- After IPN confirms payment completion
- After wallet transaction initiation success

**Event Data**:
```php
[
    'paymentData' => [...],  // Payment request data
    'response' => [...],      // Completion response
]
```

**Usage**:
```php
use zfhassaan\Payfast\Events\PaymentCompleted;

Event::listen(PaymentCompleted::class, function ($event) {
    $paymentData = $event->paymentData;
    $response = $event->response;
    
    // Update order status
    $order = Order::where('order_number', $paymentData['orderNumber'])->first();
    if ($order) {
        $order->update(['status' => 'paid']);
    }
    
    // Send confirmation email
    Mail::to($paymentData['customer_email'])->send(new PaymentConfirmationMail($order));
});
```

### PaymentFailed

Dispatched when a payment fails.

**Event Class**: `zfhassaan\Payfast\Events\PaymentFailed`

**When Dispatched**:
- When payment validation fails
- When transaction initiation fails
- When IPN confirms payment failure

**Event Data**:
```php
[
    'paymentData' => [...],  // Payment request data
    'errorCode' => 'ERROR_CODE',
    'errorMessage' => 'Error description',
]
```

**Usage**:
```php
use zfhassaan\Payfast\Events\PaymentFailed;

Event::listen(PaymentFailed::class, function ($event) {
    $paymentData = $event->paymentData;
    $errorCode = $event->errorCode;
    $errorMessage = $event->errorMessage;
    
    // Log failure
    Log::error('Payment failed', [
        'order' => $paymentData['orderNumber'],
        'error_code' => $errorCode,
        'error_message' => $errorMessage,
    ]);
    
    // Notify admin
    Mail::to(config('payfast.admin_emails'))->send(new PaymentFailureMail($paymentData, $errorMessage));
});
```

### TokenRefreshed

Dispatched when authentication token is refreshed.

**Event Class**: `zfhassaan\Payfast\Events\TokenRefreshed`

**When Dispatched**:
- After successful `refreshToken()` call

**Event Data**:
```php
[
    'oldToken' => 'old_token',
    'newToken' => 'new_token',
]
```

**Usage**:
```php
use zfhassaan\Payfast\Events\TokenRefreshed;

Event::listen(TokenRefreshed::class, function ($event) {
    $oldToken = $event->oldToken;
    $newToken = $event->newToken;
    
    // Update token in cache or database
    Cache::put('payfast_token', $newToken, 3600);
});
```

## Built-in Listeners

The package includes several built-in listeners that are automatically registered:

### LogPaymentActivity

Logs all payment activities to the activity log table.

**Listener Class**: `zfhassaan\Payfast\Listeners\LogPaymentActivity`

**Handles Events**:
- `PaymentInitiated`
- `PaymentValidated`
- `PaymentCompleted`
- `PaymentFailed`

**What It Does**:
- Creates entries in `payfast_activity_logs_table`
- Stores payment details, status, and metadata

### StorePaymentRecord

Stores payment records in the database.

**Listener Class**: `zfhassaan\Payfast\Listeners\StorePaymentRecord`

**Handles Events**:
- `PaymentValidated`

**What It Does**:
- Creates/updates payment records in `payfast_process_payments_table`

### SendPaymentEmailNotifications

Sends email notifications for payment events.

**Listener Class**: `zfhassaan\Payfast\Listeners\SendPaymentEmailNotifications`

**Handles Events**:
- `PaymentValidated`
- `PaymentCompleted`
- `PaymentFailed`

**What It Does**:
- Sends emails to customers and admins
- Uses configured email templates
- Handles email failures gracefully

## Creating Custom Listeners

### Method 1: Using Event Listeners

Create a listener class:

```php
<?php

namespace App\Listeners;

use zfhassaan\Payfast\Events\PaymentCompleted;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatus
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        $paymentData = $event->paymentData;
        $response = $event->response;
        
        // Update order status
        $order = Order::where('order_number', $paymentData['orderNumber'])->first();
        if ($order) {
            $order->update(['status' => 'paid']);
        }
        
        Log::info('Order status updated', [
            'order' => $paymentData['orderNumber'],
        ]);
    }
}
```

Register the listener in `app/Providers/EventServiceProvider.php`:

```php
use App\Listeners\UpdateOrderStatus;
use zfhassaan\Payfast\Events\PaymentCompleted;

protected $listen = [
    PaymentCompleted::class => [
        UpdateOrderStatus::class,
    ],
];
```

### Method 2: Using Closures

Register event listeners in `app/Providers/EventServiceProvider.php`:

```php
use Illuminate\Support\Facades\Event;
use zfhassaan\Payfast\Events\PaymentCompleted;

public function boot(): void
{
    Event::listen(PaymentCompleted::class, function ($event) {
        $paymentData = $event->paymentData;
        
        // Your custom logic here
        Log::info('Payment completed', $paymentData);
    });
}
```

### Method 3: Using Event Subscribers

Create an event subscriber:

```php
<?php

namespace App\Listeners;

use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;

class PaymentEventSubscriber
{
    /**
     * Handle payment completed events.
     */
    public function handlePaymentCompleted($event): void
    {
        // Handle payment completion
    }

    /**
     * Handle payment failed events.
     */
    public function handlePaymentFailed($event): void
    {
        // Handle payment failure
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): void
    {
        $events->listen(
            PaymentCompleted::class,
            [PaymentEventSubscriber::class, 'handlePaymentCompleted']
        );

        $events->listen(
            PaymentFailed::class,
            [PaymentEventSubscriber::class, 'handlePaymentFailed']
        );
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
use App\Listeners\PaymentEventSubscriber;

protected $subscribe = [
    PaymentEventSubscriber::class,
];
```

## Disabling Built-in Listeners

If you want to disable built-in listeners, you can do so in the service provider:

```php
// In app/Providers/AppServiceProvider.php
public function boot(): void
{
    // Remove specific listener
    Event::forget(\zfhassaan\Payfast\Listeners\SendPaymentEmailNotifications::class);
}
```

## Event Priority

Listeners are executed in the order they are registered. You can set priority:

```php
Event::listen(PaymentCompleted::class, function ($event) {
    // This will run first
}, 10);

Event::listen(PaymentCompleted::class, function ($event) {
    // This will run second
}, 5);
```

## Testing Events

### Testing Event Dispatch

```php
use Illuminate\Support\Facades\Event;
use zfhassaan\Payfast\Events\PaymentCompleted;

Event::fake();

// Your code that dispatches the event
PayFast::completeTransactionFromPares($pares);

// Assert event was dispatched
Event::assertDispatched(PaymentCompleted::class);
```

### Testing Event Data

```php
Event::assertDispatched(PaymentCompleted::class, function ($event) {
    return $event->paymentData['orderNumber'] === 'ORD-12345';
});
```

## Best Practices

1. **Keep listeners lightweight** - Don't perform heavy operations in listeners
2. **Use queues** - Queue heavy operations like sending emails
3. **Handle exceptions** - Wrap listener code in try-catch blocks
4. **Log activities** - Log important actions in listeners
5. **Test listeners** - Write tests for custom listeners
6. **Avoid side effects** - Don't modify payment data in listeners

## Example: Complete Integration

```php
<?php

namespace App\Listeners;

use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use App\Models\Order;
use App\Notifications\PaymentCompletedNotification;
use App\Notifications\PaymentFailedNotification;
use Illuminate\Support\Facades\Notification;

class PaymentEventHandlers
{
    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        $paymentData = $event->paymentData;
        $response = $event->response;
        
        // Update order
        $order = Order::where('order_number', $paymentData['orderNumber'])->first();
        if ($order) {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'transaction_id' => $response['transaction_id'] ?? null,
            ]);
            
            // Send notification
            $order->user->notify(new PaymentCompletedNotification($order));
        }
    }

    public function handlePaymentFailed(PaymentFailed $event): void
    {
        $paymentData = $event->paymentData;
        $errorCode = $event->errorCode;
        $errorMessage = $event->errorMessage;
        
        // Update order
        $order = Order::where('order_number', $paymentData['orderNumber'])->first();
        if ($order) {
            $order->update([
                'status' => 'payment_failed',
                'payment_error' => $errorMessage,
            ]);
            
            // Send notification
            $order->user->notify(new PaymentFailedNotification($order, $errorMessage));
        }
    }
}
```

## Next Steps

- [Models and Database](Models-and-Database.md) - Database schema and models
- [IPN Handling](IPN-Handling.md) - Webhook notifications
- [Troubleshooting](Troubleshooting.md) - Common issues

