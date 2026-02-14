# Security Best Practices

Security guidelines for using the PayFast package safely and securely.

## Environment Variables

### Never Commit Credentials

**❌ Bad**:

```php
// Don't hardcode credentials
$merchantId = '12345';
$securedKey = 'secret_key';
```

** Good**:

```php
// Use environment variables
$merchantId = config('payfast.merchant_id');
$securedKey = config('payfast.secured_key');
```

### Secure .env File

1. **Never commit `.env`** to version control
2. **Use `.env.example`** for documentation
3. **Restrict file permissions**: `chmod 600 .env`
4. **Use different credentials** for sandbox and production
5. **Rotate credentials** regularly

## API Security

### Use HTTPS

Always use HTTPS for:

- API calls to PayFast
- Payment callbacks
- IPN endpoints
- Customer redirects

```php
// Ensure HTTPS in production
if (app()->environment('production')) {
    URL::forceScheme('https');
}
```

### Validate All Inputs

Always validate user input:

```php
$request->validate([
    'orderNumber' => 'required|string|max:255',
    'transactionAmount' => 'required|numeric|min:0.01',
    'customerMobileNo' => 'required|string|regex:/^[0-9]{11}$/',
    'customer_email' => 'required|email',
    'cardNumber' => 'required|string|regex:/^[0-9]{13,19}$/',
    'expiry_month' => 'required|string|regex:/^(0[1-9]|1[0-2])$/',
    'expiry_year' => 'required|string|regex:/^[0-9]{4}$/',
    'cvv' => 'required|string|regex:/^[0-9]{3,4}$/',
]);
```

### Sanitize Data

Sanitize data before storing:

```php
$paymentData = [
    'orderNumber' => htmlspecialchars($request->orderNumber, ENT_QUOTES, 'UTF-8'),
    'customer_email' => filter_var($request->email, FILTER_SANITIZE_EMAIL),
];
```

## IPN Security

### IP Whitelisting

Whitelist PayFast IP addresses:

```php
public function handleIPN(Request $request)
{
    $allowedIPs = [
        '203.0.113.0', // PayFast IP 1
        '203.0.113.1', // PayFast IP 2
        // Get actual IPs from PayFast support
    ];

    if (!in_array($request->ip(), $allowedIPs)) {
        Log::warning('IPN from unauthorized IP', ['ip' => $request->ip()]);
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    return Payfast::handleIPN($request->all());
}
```

### Signature Verification

Verify IPN signatures if provided:

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

### Disable CSRF for IPN

Add IPN route to CSRF exceptions:

```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'payment/ipn',
    'api/payment/ipn',
];
```

## Data Protection

### Encrypt Sensitive Data

Encrypt sensitive payment data:

```php
use Illuminate\Support\Facades\Crypt;

// Encrypt
$encrypted = Crypt::encryptString($cardNumber);

// Decrypt
$decrypted = Crypt::decryptString($encrypted);
```

### Don't Store Card Data

**❌ Bad**:

```php
// Never store full card numbers
ProcessPayment::create([
    'card_number' => $request->cardNumber, // DON'T DO THIS
]);
```

** Good**:

```php
// Store only last 4 digits if needed
ProcessPayment::create([
    'card_last_four' => substr($request->cardNumber, -4),
]);
```

### Use Soft Deletes

Use soft deletes to preserve audit trail:

```php
// Payment records are soft deleted
$payment->delete(); // Soft delete, can be restored
```

## Authentication

### Secure Token Storage

Store tokens securely:

```php
// Use encrypted cache
Cache::store('redis')->put('payfast_token', $token, 3600);

// Or use database with encryption
$payment->token = Crypt::encryptString($token);
$payment->save();
```

### Token Expiration

Handle token expiration:

```php
try {
    $response = PayFast::getToken();
} catch (TokenExpiredException $e) {
    // Refresh token
    $response = PayFast::refreshToken($oldToken, $refreshToken);
}
```

## Database Security

### Use Prepared Statements

Always use Eloquent or query builder (they use prepared statements):

```php
//   Good - Uses prepared statements
ProcessPayment::where('transaction_id', $transactionId)->first();

// ❌ Bad - SQL injection risk
DB::select("SELECT * FROM payments WHERE transaction_id = '{$transactionId}'");
```

### Limit Database Access

1. **Use read-only users** for queries
2. **Restrict database access** to application server
3. **Use connection pooling** for performance
4. **Monitor database logs** for suspicious activity

## Error Handling

### Don't Expose Sensitive Information

**❌ Bad**:

```php
catch (\Exception $e) {
    return response()->json([
        'error' => $e->getMessage(), // May contain sensitive info
        'trace' => $e->getTraceAsString(), // Security risk
    ]);
}
```

** Good**:

```php
catch (\Exception $e) {
    Log::error('Payment error', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    return response()->json([
        'error' => 'Payment processing failed. Please try again.',
    ], 500);
}
```

### Log Security Events

Log security-related events:

```php
Log::warning('Suspicious payment activity', [
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'transaction_id' => $transactionId,
]);
```

## PCI DSS Compliance

### Don't Store Card Data

If you're PCI DSS compliant, you can process cards directly. Otherwise:

1. **Use hosted checkout** - Redirect to PayFast
2. **Don't store card data** - Let PayFast handle it
3. **Use tokenization** - If available from PayFast

### Secure Card Input

If processing cards directly:

1. **Use HTTPS** - Always encrypt card data in transit
2. **Validate on server** - Never trust client-side validation
3. **Use 3DS** - Enable 3D Secure authentication
4. **Tokenize** - Use tokens instead of card numbers

## Rate Limiting

### Implement Rate Limiting

Protect endpoints from abuse:

```php
// In routes/web.php
Route::post('/payment/initiate', [PaymentController::class, 'initiatePayment'])
    ->middleware('throttle:5,1'); // 5 requests per minute
```

### Monitor for Abuse

Monitor for suspicious activity:

```php
// Check for too many failed attempts
$failedAttempts = ProcessPayment::where('status', 'failed')
    ->where('created_at', '>', now()->subHour())
    ->where('customer_email', $email)
    ->count();

if ($failedAttempts > 5) {
    // Block or alert
}
```

## Logging

### Secure Logging

Don't log sensitive data:

```php
// ❌ Bad
Log::info('Payment data', $paymentData); // May contain card numbers

//   Good
Log::info('Payment initiated', [
    'order_number' => $paymentData['orderNumber'],
    'amount' => $paymentData['transactionAmount'],
    // Don't log card numbers, CVV, etc.
]);
```

### Log Access

1. **Restrict log file access** - Only authorized personnel
2. **Rotate logs regularly** - Prevent log files from growing too large
3. **Monitor log files** - Check for suspicious activity
4. **Encrypt logs** - If storing sensitive information

## Updates and Patches

### Keep Package Updated

Regularly update the package:

```bash
composer update zfhassaan/payfast
```

### Review Changelog

Check changelog for security updates:

```bash
# Review changelog
cat packages/zfhassaan/payfast/changelog.md
```

### Test Updates

Always test updates in sandbox before production:

1. Update in sandbox environment
2. Run test transactions
3. Verify all functionality
4. Update production after verification

## Best Practices Summary

1.  **Never commit credentials** - Use environment variables
2.  **Use HTTPS** - Always encrypt data in transit
3.  **Validate inputs** - Never trust user input
4.  **Don't store card data** - Use tokenization
5.  **Implement rate limiting** - Prevent abuse
6.  **Log security events** - Monitor for threats
7.  **Keep updated** - Apply security patches
8.  **Use IP whitelisting** - For IPN endpoints
9.  **Verify signatures** - For webhooks
10. **Handle errors securely** - Don't expose sensitive info

## Compliance

### PCI DSS

If processing cards directly, ensure PCI DSS compliance:

1. **Use secure networks** - Firewalls, encryption
2. **Protect card data** - Encryption, tokenization
3. **Vulnerability management** - Regular scans, patches
4. **Access control** - Restrict access to card data
5. **Monitor and test** - Regular security testing

### GDPR

If handling EU customer data:

1. **Encrypt personal data** - At rest and in transit
2. **Implement access controls** - Limit who can access data
3. **Log access** - Track who accesses what data
4. **Right to deletion** - Implement data deletion
5. **Data breach notification** - Have a plan

## Next Steps

- [Troubleshooting](Troubleshooting.md) - Common issues
- [Configuration Guide](Configuration-Guide.md) - Secure configuration
- [IPN Handling](IPN-Handling.md) - Secure webhook handling









