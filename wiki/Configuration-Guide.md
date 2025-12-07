# Configuration Guide

This guide covers all configuration options available in the PayFast package.

## Configuration File

The configuration file is located at `config/payfast.php` after publishing. You can publish it using:

```bash
php artisan vendor:publish --tag=payfast-config
```

## Environment Variables

All configuration values are loaded from your `.env` file. Here's a complete list:

### Required Configuration

```env
# API URLs
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com

# Authentication
PAYFAST_GRANT_TYPE=client_credentials
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key

# Application Settings
PAYFAST_RETURN_URL=https://yourdomain.com/payment/callback
PAYFAST_MODE=sandbox
```

### Optional Configuration

```env
# Store Configuration
PAYFAST_STORE_ID=your_store_id

# Transaction Verification
PAYFAST_VERIFY_TRANSACTION=https://api.payfast.com/transaction/view

# Email Notifications
PAYFAST_ADMIN_EMAILS=admin@example.com,admin2@example.com
PAYFAST_EMAIL_SUBJECT_COMPLETION=Payment Completed Successfully
PAYFAST_EMAIL_SUBJECT_ADMIN=New Payment Completed
PAYFAST_EMAIL_SUBJECT_FAILURE=Payment Failed
```

## Configuration Structure

The configuration file structure:

```php
return [
    // API Configuration
    'api_url' => env('PAYFAST_API_URL', ''),
    'sandbox_api_url' => env('PAYFAST_SANDBOX_URL', ''),
    
    // Authentication
    'grant_type' => env('PAYFAST_GRANT_TYPE', ''),
    'merchant_id' => env('PAYFAST_MERCHANT_ID', ''),
    'secured_key' => env('PAYFAST_SECURED_KEY', ''),
    
    // Application Settings
    'store_id' => env('PAYFAST_STORE_ID', ''),
    'return_url' => env('PAYFAST_RETURN_URL', ''),
    'mode' => env('PAYFAST_MODE', 'sandbox'),
    'transaction_check' => env('PAYFAST_VERIFY_TRANSACTION', ''),
    
    // Email Configuration
    'admin_emails' => env('PAYFAST_ADMIN_EMAILS', ''),
    'email_templates' => [
        'status_notification' => 'payfast::emails.status-notification',
        'payment_completion' => 'payfast::emails.payment-completion',
        'admin_notification' => 'payfast::emails.admin-notification',
        'payment_failure' => 'payfast::emails.payment-failure',
    ],
    'email_subjects' => [
        'payment_completion' => env('PAYFAST_EMAIL_SUBJECT_COMPLETION', 'Payment Completed Successfully'),
        'admin_notification' => env('PAYFAST_EMAIL_SUBJECT_ADMIN', 'New Payment Completed'),
        'payment_failure' => env('PAYFAST_EMAIL_SUBJECT_FAILURE', 'Payment Failed'),
    ],
];
```

## Mode Configuration

The package supports two modes:

### Sandbox Mode

```env
PAYFAST_MODE=sandbox
```

- Uses `PAYFAST_SANDBOX_URL` for API calls
- Safe for testing
- Uses test credentials
- No real transactions processed

### Production Mode

```env
PAYFAST_MODE=production
```

- Uses `PAYFAST_API_URL` for API calls
- Real transactions processed
- Requires production credentials
- **Use with caution**

## Email Configuration

### Admin Emails

Configure comma-separated admin emails to receive payment notifications:

```env
PAYFAST_ADMIN_EMAILS=admin@example.com,admin2@example.com
```

### Email Templates

The package includes four email templates:

1. **Status Notification** - General payment status updates
2. **Payment Completion** - Sent to customer on successful payment
3. **Admin Notification** - Sent to admins on payment completion
4. **Payment Failure** - Sent on payment failure

You can customize these templates by publishing them:

```bash
php artisan vendor:publish --tag=payfast-email-templates
```

Templates will be published to `resources/views/vendor/payfast/emails/`.

### Email Subjects

Customize email subjects:

```env
PAYFAST_EMAIL_SUBJECT_COMPLETION=Payment Completed Successfully
PAYFAST_EMAIL_SUBJECT_ADMIN=New Payment Completed
PAYFAST_EMAIL_SUBJECT_FAILURE=Payment Failed
```

## Runtime Configuration

You can modify configuration at runtime:

```php
// Change mode
config(['payfast.mode' => 'production']);

// Update merchant ID
config(['payfast.merchant_id' => 'new_merchant_id']);

// Update return URL
config(['payfast.return_url' => 'https://newdomain.com/callback']);
```

## Accessing Configuration

### Using Config Helper

```php
$merchantId = config('payfast.merchant_id');
$mode = config('payfast.mode');
$apiUrl = config('payfast.api_url');
```

### Using ConfigService

```php
use zfhassaan\Payfast\Services\ConfigService;

$configService = app(ConfigService::class);
$merchantId = $configService->getMerchantId();
$securedKey = $configService->getSecuredKey();
$apiUrl = $configService->getApiUrl();
```

## Environment-Specific Configuration

### Development Environment

```env
PAYFAST_MODE=sandbox
PAYFAST_API_URL=https://sandbox.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com
```

### Staging Environment

```env
PAYFAST_MODE=sandbox
PAYFAST_API_URL=https://staging.payfast.com
PAYFAST_SANDBOX_URL=https://staging.payfast.com
```

### Production Environment

```env
PAYFAST_MODE=production
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com
```

## Security Best Practices

1. **Never commit `.env` file** - Keep credentials secure
2. **Use environment variables** - Don't hardcode values
3. **Rotate credentials regularly** - Update keys periodically
4. **Use different credentials** - Separate sandbox and production
5. **Restrict access** - Limit who can access configuration

## Validation

The package validates configuration on service initialization. Missing required values will throw exceptions:

```php
// Required values
- api_url or sandbox_api_url (based on mode)
- merchant_id
- secured_key
- grant_type
- return_url
```

## Testing Configuration

Test your configuration:

```php
use zfhassaan\Payfast\Facades\PayFast;

// This will use your configuration
$response = PayFast::getToken();

if ($response->getStatusCode() === 200) {
    // Configuration is correct
}
```

## Troubleshooting

### Configuration Not Loading

**Solution**: Clear config cache:

```bash
php artisan config:clear
php artisan config:cache
```

### Wrong API URL Being Used

**Solution**: Check `PAYFAST_MODE` in `.env`:

```bash
# Verify mode
php artisan tinker
>>> config('payfast.mode')
```

### Credentials Not Working

**Solution**: 
- Verify credentials in PayFast dashboard
- Check for extra spaces in `.env` values
- Ensure you're using correct mode (sandbox vs production)

## Next Steps

- [Getting Started](Getting-Started.md) - Start using the package
- [Payment Flows](Payment-Flows.md) - Understand payment processing
- [API Reference](API-Reference.md) - Explore available methods

