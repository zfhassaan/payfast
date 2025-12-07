# Installation Guide

This guide will walk you through installing and setting up the PayFast Payment Gateway package in your Laravel application.

## Prerequisites

Before installing the package, ensure you have:

- PHP 7.4 or higher (PHP 8.0+ recommended)
- Laravel 8.0, 9.0, 10.0, 11.0, or 12.0
- Composer installed
- cURL extension enabled
- A PayFast merchant account with:
  - Merchant ID
  - Secured Key
  - API URLs (Production and Sandbox)

## Step 1: Install via Composer

Install the package using Composer:

```bash
composer require zfhassaan/payfast
```

## Step 2: Publish Configuration

Publish the configuration file to your `config` directory:

```bash
php artisan vendor:publish --tag=payfast-config
```

This will create `config/payfast.php` in your application.

## Step 3: Publish Migrations

Publish the database migrations:

```bash
php artisan vendor:publish --tag=payfast-migrations
```

This will copy the following migrations to your `database/migrations` directory:

- `2023_08_14_071018_payfast_create_process_payments_table_in_payfast.php`
- `2024_02_02_194203_payfast_create_activity_logs_table.php`
- `2024_02_02_195511_payfast_create_ipn_table.php`
- `2025_01_15_000001_add_status_and_pares_to_process_payments.php`

## Step 4: Run Migrations

Run the migrations to create the necessary database tables:

```bash
php artisan migrate
```

This will create the following tables:

- `payfast_process_payments_table` - Stores payment records
- `payfast_activity_logs_table` - Stores payment activity logs
- `payfast_ipn_table` - Stores IPN (Instant Payment Notification) logs

## Step 5: Configure Environment Variables

Add the following environment variables to your `.env` file:

```env
# PayFast Configuration
PAYFAST_API_URL=https://api.payfast.com
PAYFAST_SANDBOX_URL=https://sandbox.payfast.com
PAYFAST_GRANT_TYPE=client_credentials
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key
PAYFAST_STORE_ID=your_store_id
PAYFAST_RETURN_URL=https://yourdomain.com/payment/callback
PAYFAST_MODE=sandbox
PAYFAST_VERIFY_TRANSACTION=https://api.payfast.com/transaction/view

# Email Configuration (Optional)
PAYFAST_ADMIN_EMAILS=admin@example.com,admin2@example.com
PAYFAST_EMAIL_SUBJECT_COMPLETION=Payment Completed Successfully
PAYFAST_EMAIL_SUBJECT_ADMIN=New Payment Completed
PAYFAST_EMAIL_SUBJECT_FAILURE=Payment Failed
```

### Environment Variables Explained

| Variable | Description | Required |
|----------|-------------|----------|
| `PAYFAST_API_URL` | Production API URL | Yes |
| `PAYFAST_SANDBOX_URL` | Sandbox API URL | Yes |
| `PAYFAST_GRANT_TYPE` | OAuth grant type (usually `client_credentials`) | Yes |
| `PAYFAST_MERCHANT_ID` | Your PayFast merchant ID | Yes |
| `PAYFAST_SECURED_KEY` | Your PayFast secured key | Yes |
| `PAYFAST_STORE_ID` | Your PayFast store ID | Optional |
| `PAYFAST_RETURN_URL` | URL to redirect after payment | Yes |
| `PAYFAST_MODE` | `sandbox` or `production` | Yes |
| `PAYFAST_VERIFY_TRANSACTION` | Transaction verification URL | Optional |
| `PAYFAST_ADMIN_EMAILS` | Comma-separated admin emails | Optional |
| `PAYFAST_EMAIL_SUBJECT_*` | Email subject templates | Optional |

## Step 6: Register Service Provider (Laravel < 11)

For Laravel 8, 9, and 10, you need to manually register the service provider in `config/app.php`:

```php
'providers' => [
    // ... other providers
    \zfhassaan\Payfast\Provider\PayFastServiceProvider::class,
],
```

For Laravel 11+, the service provider is auto-discovered.

## Step 7: Register Facade (Laravel < 11)

For Laravel 8, 9, and 10, add the facade alias in `config/app.php`:

```php
'aliases' => Facade::defaultAliases()->merge([
    'PayFast' => \zfhassaan\Payfast\Facade\Payfast::class,
])->toArray(),
```

For Laravel 11+, the facade is auto-discovered.

## Step 8: Publish Email Templates (Optional)

If you want to customize email templates:

```bash
php artisan vendor:publish --tag=payfast-email-templates
```

This will publish email templates to `resources/views/vendor/payfast/emails/`.

## Step 9: Publish Console Command (Optional)

If you want to customize the console command:

```bash
php artisan vendor:publish --tag=payfast-command
```

This will publish a stub file to `app/Console/Commands/PayfastCheckPendingPayments.php`.

## Step 10: Publish Tests (Optional)

If you want to customize or extend the test suite:

```bash
php artisan vendor:publish --tag=payfast-tests
```

## Verification

To verify the installation, you can test the service provider registration:

```php
// In tinker or a test route
php artisan tinker

// Test facade
use zfhassaan\Payfast\Facades\PayFast;
PayFast::getToken();
```

## Troubleshooting

### Issue: Service Provider Not Found

**Solution**: Ensure you've registered the service provider in `config/app.php` (Laravel < 11) or that auto-discovery is enabled.

### Issue: Facade Not Found

**Solution**: Ensure you've registered the facade alias in `config/app.php` (Laravel < 11).

### Issue: Migration Errors

**Solution**: 
- Ensure all previous migrations have been run
- Check database connection settings
- Verify table names don't conflict with existing tables

### Issue: Configuration Not Found

**Solution**: 
- Run `php artisan config:clear`
- Ensure `.env` file has all required variables
- Verify `config/payfast.php` exists

### Issue: cURL Extension Missing

**Solution**: Install the cURL extension for PHP:

```bash
# Ubuntu/Debian
sudo apt-get install php-curl

# macOS (Homebrew)
brew install php-curl

# Windows (XAMPP/WAMP)
# Enable extension=curl in php.ini
```

## Next Steps

After installation, proceed to:

1. [Configuration Guide](Configuration-Guide.md) - Configure the package
2. [Getting Started](Getting-Started.md) - Learn basic usage
3. [Payment Flows](Payment-Flows.md) - Understand payment processing

## Additional Resources

- [PayFast Official Documentation](https://gopayfast.com/docs/#preface)
- [Laravel Documentation](https://laravel.com/docs)
- [Package README](../README.md)

