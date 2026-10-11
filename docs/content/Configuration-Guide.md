# Configuration Guide

This guide covers all configuration options available in the PayFast package.

## Publish assets

```bash
# Everything most apps need (config, migrations, emails, logging stub, env example)
php artisan vendor:publish --tag=payfast

# Or selectively:
php artisan vendor:publish --tag=payfast-config
php artisan vendor:publish --tag=payfast-migrations
php artisan vendor:publish --tag=payfast-email-templates
php artisan vendor:publish --tag=payfast-logging
php artisan vendor:publish --tag=payfast-env
```

| Tag | Destination |
|-----|-------------|
| `payfast` | Config + migrations + email views + logging stub + `.env.payfast.example` |
| `payfast-config` | `config/payfast.php` |
| `payfast-migrations` | `database/migrations/*` |
| `payfast-email-templates` | `resources/views/vendor/payfast/emails` |
| `payfast-logging` | `config/payfast-logging-channel.php` |
| `payfast-env` | `.env.payfast.example` |

After `payfast-env`, copy relevant keys from `.env.payfast.example` into `.env`.

If you publish migrations into your app, set `PAYFAST_LOAD_MIGRATIONS=false` to avoid double-loading.

## Configuration File

Published file: `config/payfast.php`.

You can customize:

- API / auth / mode
- Whether package migrations & routes auto-load
- Wallet `bank_code` mappings (JazzCash / EasyPaisa / UPaisa)
- Email templates & subjects
- IPN HMAC secret + IP allowlist
- Route middleware / prefix

## Environment Variables

### Required Configuration

```env
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com
PAYFAST_GRANT_TYPE=client_credentials
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key
PAYFAST_RETURN_URL=https://yourdomain.com/payment/callback
PAYFAST_MODE=sandbox
```

### Optional Configuration

```env
PAYFAST_STORE_ID=your_store_id
PAYFAST_CHECKOUT_URL=
PAYFAST_VERIFY_TRANSACTION=

PAYFAST_LOAD_MIGRATIONS=true
PAYFAST_ROUTES_ENABLED=true
PAYFAST_ROUTE_PREFIX=

PAYFAST_WALLET_JAZZCASH_BANK_CODE=1
PAYFAST_WALLET_EASYPAISA_BANK_CODE=13
PAYFAST_WALLET_UPAISA_BANK_CODE=14
PAYFAST_WALLET_ACCOUNT_TYPE_ID=4

PAYFAST_ADMIN_EMAILS=admin@example.com
PAYFAST_IPN_SECRET=
PAYFAST_IPN_ALLOWED_IPS=
PAYFAST_IPN_USE_API_MIDDLEWARE=true
PAYFAST_IDEMPOTENCY_ENABLED=true
PAYFAST_LOG_HEADERS=true
PAYFAST_LOG_CHANNEL=payfast
PAYFAST_LOG_LEVEL=debug
```

## IPN, idempotency & request logging

| Key | Purpose |
|-----|---------|
| `ipn.secret` / `PAYFAST_IPN_SECRET` | HMAC-SHA256 of raw body |
| `ipn.allowed_ips` / `PAYFAST_IPN_ALLOWED_IPS` | IP or CIDR allowlist |
| `idempotency.enabled` | Duplicate deliveries → `ALREADY_PROCESSED` |
| `request_logging.log_headers` | Persist redacted headers on IPN rows |
| `routes.middleware` | Default: `api`, `payfast.ipn.log`, `payfast.ipn` |

See [IPN Handling](IPN-Handling) for the full webhook pipeline.

## Configuration Structure

See the published `config/payfast.php` for the full annotated structure (API, routes, wallets, email, IPN, logging).

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















