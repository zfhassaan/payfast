# Subscription Management

PayFast supports recurring payments through its subscription API. This allows you to create, manage, and cancel automated payments for your customers.

## Creating a Subscription

To create a new subscription, use the `createSubscription` method. You'll need to provide customer details, an order number, the transaction amount, and a unique plan ID.

```php
use zfhassaan\Payfast\Facades\PayFast;

$subscriptionData = [
    'orderNumber' => 'SUB-1001',           // Unique order identifier
    'transactionAmount' => 500.00,        // Amount to charge per cycle
    'customer_email' => 'user@example.com',
    'customerMobileNo' => '03001234567',
    'planId' => 'PREMIUM_MONTHLY',         // Unique identifier for the plan
    'frequency' => 'monthly',              // Optional: daily, weekly, monthly, yearly
    'iterations' => 12,                    // Optional: Number of recurring cycles
];

$response = PayFast::createSubscription($subscriptionData);
```

### Parameters

- `orderNumber` (string) - Required. Your internal order identifier.
- `transactionAmount` (float) - Required. The amount for each subscription cycle.
- `customer_email` (string) - Required. Customer's email address.
- `customerMobileNo` (string) - Required. Customer's mobile number.
- `planId` (string) - Required. A unique ID for the subscription plan inside PayFast.
- `frequency` (string) - Optional. The billing interval (default: `monthly`).
- `iterations` (int) - Optional. Total number of occurrences (default: `0` for infinite).

## Updating a Subscription

If you need to change the amount or plan details for an existing subscription, use the `updateSubscription` method.

```php
$subscriptionId = 'PF_SUB_12345';
$updateData = [
    'transactionAmount' => 600.00,
];

$response = PayFast::updateSubscription($subscriptionId, $updateData);
```

## Cancelling a Subscription

To stop future recurring payments, you can cancel a subscription using its PayFast Subscription ID.

```php
$subscriptionId = 'PF_SUB_12345';
$response = PayFast::cancelSubscription($subscriptionId);
```

### JSON Response

All subscription methods return a standardized `JsonResponse`.

```json
{
  "status": true,
  "data": {
    "subscription_id": "PF_SUB_12345",
    "status": "active"
  },
  "message": "Subscription created successfully",
  "code": "00"
}
```

## IPN for Subscriptions

When a recurring payment is processed (or fails), PayFast sends an IPN (Instant Payment Notification) to your webhook URL. You should handle these exactly like standard payments using `PayFast::handleIPN($request->all())`.

Refer to the [IPN Handling](IPN-Handling) guide for details on setting up your webhook.
