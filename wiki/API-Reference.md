# API Reference

Complete reference documentation for all PayFast package methods and classes.

## Facade Access

All methods are accessible via the `PayFast` facade:

```php
use zfhassaan\Payfast\Facades\PayFast;
```

## Authentication Methods

### getToken()

Get authentication token from PayFast.

```php
$response = PayFast::getToken();
```

**Returns**: `JsonResponse`

**Response Format**:
```json
{
    "status": true,
    "data": {
        "token": "abc123...",
        "expires_in": 3600
    },
    "message": "Token retrieved successfully",
    "code": "00"
}
```

**Example**:
```php
$response = PayFast::getToken();
$data = json_decode($response->getContent(), true);

if ($data['status'] && $data['code'] === '00') {
    $token = $data['data']['token'];
}
```

### refreshToken()

Refresh an existing authentication token.

```php
$response = PayFast::refreshToken($token, $refreshToken);
```

**Parameters**:
- `$token` (string) - Current authentication token
- `$refreshToken` (string) - Refresh token

**Returns**: `JsonResponse|null`

**Example**:
```php
$response = PayFast::refreshToken($currentToken, $refreshToken);
```

## Payment Methods

### getOTPScreen()

Validate customer and get OTP screen for card payments.

```php
$response = PayFast::getOTPScreen($paymentData);
```

**Parameters**:
```php
$paymentData = [
    'orderNumber' => 'ORD-12345',        // Required
    'transactionAmount' => 1000.00,      // Required
    'customerMobileNo' => '03001234567', // Required
    'customer_email' => 'customer@example.com', // Required
    'cardNumber' => '4111111111111111',  // Required
    'expiry_month' => '12',              // Required
    'expiry_year' => '2025',             // Required
    'cvv' => '123',                      // Required
];
```

**Returns**: `JsonResponse`

**Response Format**:
```json
{
    "status": true,
    "data": {
        "token": "abc123...",
        "customer_validate": {...},
        "transaction_id": "TXN123456",
        "payment_id": 1,
        "redirect_url": "https://..."
    },
    "message": "OTP screen retrieved successfully",
    "code": "00"
}
```

**Example**:
```php
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
```

### verifyOTPAndStorePares()

Verify OTP and store 3DS pares.

```php
$response = PayFast::verifyOTPAndStorePares($transactionId, $otp, $pares);
```

**Parameters**:
- `$transactionId` (string) - Transaction ID from getOTPScreen
- `$otp` (string) - OTP entered by customer
- `$pares` (string) - 3DS pares from PayFast

**Returns**: `JsonResponse`

**Response Format**:
```json
{
    "status": true,
    "data": {
        "transaction_id": "TXN123456",
        "payment_id": 1,
        "status": "otp_verified"
    },
    "message": "OTP verified and pares stored successfully",
    "code": "00"
}
```

**Example**:
```php
$response = PayFast::verifyOTPAndStorePares(
    'TXN123456',
    '123456',
    'pares_string_here'
);
```

### completeTransactionFromPares()

Complete transaction using stored pares from callback.

```php
$response = PayFast::completeTransactionFromPares($pares);
```

**Parameters**:
- `$pares` (string) - 3DS pares from PayFast callback

**Returns**: `JsonResponse`

**Response Format**:
```json
{
    "status": true,
    "data": {
        "transaction_id": "TXN123456",
        "status": "completed"
    },
    "message": "Transaction completed successfully",
    "code": "00"
}
```

**Example**:
```php
$response = PayFast::completeTransactionFromPares($pares);
```

### initiateTransaction()

Initiate a transaction directly (without OTP flow).

```php
$response = PayFast::initiateTransaction($data);
```

**Parameters**:
```php
$data = [
    'orderNumber' => 'ORD-12345',
    'transactionAmount' => 1000.00,
    // ... other payment data
];
```

**Returns**: `string|bool` (JSON string)

**Example**:
```php
$response = PayFast::initiateTransaction($paymentData);
$result = json_decode($response, true);
```

## Mobile Wallet Methods

### payWithEasyPaisa()

Process payment with EasyPaisa wallet.

```php
$response = PayFast::payWithEasyPaisa($paymentData);
```

**Parameters**:
```php
$paymentData = [
    'basket_id' => 'ORD-12345',
    'txnamt' => 1000.00,
    'customer_mobile_no' => '03001234567',
    'customer_email_address' => 'customer@example.com',
    'order_date' => '2025-01-15',
];
```

**Returns**: `mixed` (JSON string)

**Example**:
```php
$response = PayFast::payWithEasyPaisa($paymentData);
$result = json_decode($response, true);
```

### payWithUPaisa()

Process payment with UPaisa wallet.

```php
$response = PayFast::payWithUPaisa($paymentData);
```

**Parameters**: Same as `payWithEasyPaisa()`

**Returns**: `mixed` (JSON string)

**Example**:
```php
$response = PayFast::payWithUPaisa($paymentData);
```

### validateWalletTransaction()

Validate wallet transaction (generic method).

```php
$response = PayFast::validateWalletTransaction($data);
```

**Parameters**:
```php
$data = [
    'basket_id' => 'ORD-12345',
    'txnamt' => 1000.00,
    'customer_mobile_no' => '03001234567',
    'customer_email_address' => 'customer@example.com',
    'bank_code' => 13, // 13 for EasyPaisa, 14 for UPaisa
    'order_date' => '2025-01-15',
];
```

**Returns**: `string|bool` (JSON string)

### walletTransactionInitiate()

Initiate wallet transaction after validation.

```php
$response = PayFast::walletTransactionInitiate($data);
```

**Parameters**: Transaction data array

**Returns**: `string|bool` (JSON string)

## Transaction Query Methods

### getTransactionDetails()

Get transaction details by transaction ID.

```php
$response = PayFast::getTransactionDetails($transactionId);
```

**Parameters**:
- `$transactionId` (string) - PayFast transaction ID

**Returns**: `JsonResponse`

**Response Format**:
```json
{
    "status": true,
    "data": {
        "transaction_id": "TXN123456",
        "status": "completed",
        "amount": 1000.00,
        // ... other transaction details
    },
    "message": "Transaction details retrieved successfully",
    "code": "00"
}
```

**Example**:
```php
$response = PayFast::getTransactionDetails('TXN123456');
$result = json_decode($response->getContent(), true);
```

### getTransactionDetailsByBasketId()

Get transaction details by basket/order ID.

```php
$response = PayFast::getTransactionDetailsByBasketId($basketId);
```

**Parameters**:
- `$basketId` (string) - Order number/basket ID

**Returns**: `JsonResponse`

**Example**:
```php
$response = PayFast::getTransactionDetailsByBasketId('ORD-12345');
```

### refundTransactionRequest()

Request a refund for a transaction.

```php
$response = PayFast::refundTransactionRequest($data);
```

**Parameters**:
```php
$data = [
    'transaction_id' => 'TXN123456',
    'amount' => 1000.00,
    'reason' => 'Customer request',
];
```

**Returns**: `string|bool` (JSON string)

**Example**:
```php
$response = PayFast::refundTransactionRequest([
    'transaction_id' => 'TXN123456',
    'amount' => 1000.00,
]);
$result = json_decode($response, true);
```

## Bank and Instrument Methods

### listBanks()

List available banks.

```php
$response = PayFast::listBanks();
```

**Returns**: `JsonResponse`

**Response Format**:
```json
{
    "status": true,
    "data": [
        {
            "bank_code": "01",
            "bank_name": "Bank Name"
        }
    ],
    "message": "Banks listed successfully",
    "code": "00"
}
```

**Example**:
```php
$response = PayFast::listBanks();
$banks = json_decode($response->getContent(), true)['data'];
```

### listInstrumentsWithBank()

List payment instruments for a specific bank.

```php
$response = PayFast::listInstrumentsWithBank($bankCode);
```

**Parameters**:
- `$bankCode` (string|array) - Bank code or array with bank_code

**Returns**: `JsonResponse|bool`

**Example**:
```php
// Using bank code string
$response = PayFast::listInstrumentsWithBank('01');

// Using array
$response = PayFast::listInstrumentsWithBank(['bank_code' => '01']);
```

## IPN Methods

### handleIPN()

Handle Instant Payment Notification webhook from PayFast.

```php
$response = PayFast::handleIPN($ipnData);
```

**Parameters**:
```php
$ipnData = [
    'transaction_id' => 'TXN123456',
    'order_no' => 'ORD-12345',
    'status' => '00',
    'amount' => '1000.00',
    'currency' => 'PKR',
];
```

**Returns**: `JsonResponse`

**Response Format**:
```json
{
    "status": true,
    "data": {
        "ipn_log_id": 123,
        "transaction_id": "TXN123456",
        "order_no": "ORD-12345",
        "payment_updated": true
    },
    "message": "IPN processed successfully",
    "code": "00"
}
```

**Example**:
```php
// In your controller
public function handleIPN(Request $request)
{
    $ipnData = $request->all();
    $response = PayFast::handleIPN($ipnData);
    return $response;
}
```

## Token Management

### getAuthToken()

Get current authentication token.

```php
$token = PayFast::getAuthToken();
```

**Returns**: `string|null`

### setAuthToken()

Set authentication token manually.

```php
PayFast::setAuthToken($token);
```

**Parameters**:
- `$token` (string) - Authentication token

## Response Format

All methods return standardized JSON responses:

### Success Response

```json
{
    "status": true,
    "data": {...},
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

## Error Codes

Common error codes:

- `00` - Success
- `AUTH_ERROR` - Authentication error
- `VALIDATION_ERROR` - Validation error
- `IPN_ERROR` - IPN processing error
- `EXCEPTION` - Exception occurred

## Best Practices

1. **Always check response status** before processing
2. **Handle errors gracefully** with try-catch blocks
3. **Log all transactions** for audit purposes
4. **Validate input data** before making API calls
5. **Use transactions** for database operations
6. **Implement retry logic** for failed requests

## Next Steps

- [Payment Flows](Payment-Flows.md) - Understand payment processing
- [IPN Handling](IPN-Handling.md) - Set up webhook notifications
- [Models and Database](Models-and-Database.md) - Database schema

