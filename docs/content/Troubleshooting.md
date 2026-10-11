# Troubleshooting

Common issues and solutions when using the PayFast package.

## Installation Issues

### Service Provider Not Found

**Error**: `Class 'zfhassaan\Payfast\Provider\PayFastServiceProvider' not found`

**Solution**:
1. Ensure package is installed: `composer require zfhassaan/payfast`
2. Run `composer dump-autoload`
3. For Laravel < 11, register service provider in `config/app.php`

### Facade Not Found

**Error**: `Class 'PayFast' not found`

**Solution**:
1. Ensure facade is registered in `config/app.php` (Laravel < 11)
2. Check namespace: `use zfhassaan\Payfast\Facades\PayFast;`
3. Run `php artisan config:clear`

### Migration Errors

**Error**: `Table 'payfast_process_payments_table' already exists`

**Solution**:
1. Check if migrations have already been run
2. Rollback if needed: `php artisan migrate:rollback`
3. Or skip existing tables in migration

## Configuration Issues

### Configuration Not Loading

**Error**: Configuration values are null or empty

**Solution**:
1. Clear config cache: `php artisan config:clear`
2. Verify `.env` file has all required variables
3. Check `config/payfast.php` exists
4. Run `php artisan config:cache` after changes

### Wrong API URL Being Used

**Error**: Requests going to wrong endpoint

**Solution**:
1. Check `PAYFAST_MODE` in `.env` (sandbox vs production)
2. Verify `PAYFAST_API_URL` and `PAYFAST_SANDBOX_URL` are correct
3. Clear config cache: `php artisan config:clear`

### Credentials Not Working

**Error**: Authentication failures

**Solution**:
1. Verify credentials in PayFast dashboard
2. Check for extra spaces in `.env` values
3. Ensure you're using correct mode (sandbox vs production)
4. Verify merchant ID and secured key are correct

## Payment Issues

### Token Not Generated

**Error**: `Failed to get authentication token`

**Solution**:
1. Check API URL is correct
2. Verify merchant ID and secured key
3. Check network connectivity
4. Verify PayFast service is available
5. Check logs for detailed error messages

### Customer Validation Fails

**Error**: `Validation failed` or error code from PayFast

**Solution**:
1. Verify all required fields are provided
2. Check card number format
3. Verify expiry date is valid (not expired)
4. Check CVV format
5. Review PayFast error codes documentation

### OTP Verification Fails

**Error**: `OTP verification failed`

**Solution**:
1. Verify OTP is correct
2. Check OTP hasn't expired
3. Verify transaction ID is correct
4. Check if payment is in correct status
5. Review PayFast logs

### Payment Not Completing

**Error**: Payment stuck in `otp_verified` status

**Solution**:
1. Check if callback was received
2. Verify callback URL is accessible
3. Check PayFast logs for callback attempts
4. Manually trigger completion if needed
5. Use console command to check status

### Transaction Not Found

**Error**: `Transaction not found` when querying

**Solution**:
1. Verify transaction ID is correct
2. Check if transaction exists in PayFast
3. Verify you're using correct environment (sandbox vs production)
4. Check transaction hasn't expired

## IPN Issues

### IPN Not Received

**Error**: IPN webhook not being called

**Solution**:
1. Set `PAYFAST_CHECKOUT_URL=https://yourdomain.com/payfast/ipn` (or rely on `route('payfast.ipn.handle')`)
2. Confirm `PAYFAST_ROUTES_ENABLED=true` and run `php artisan route:list --name=payfast`
3. Verify the URL is publicly reachable over HTTPS
4. Check `storage/logs/payfast` and `payfast_ipn_table` (`processing_status=received` means the request hit your app)
5. Check firewall / allowlist if `PAYFAST_IPN_ALLOWED_IPS` is set

### IPN Not Processing / Rejected

**Error**: Row logged but payment not updated

**Solution**:
1. Inspect `processing_status`: `rejected` → IP/HMAC/validation; `failed` → exception; `duplicate` → already applied
2. Match `transaction_id` / `order_no` to `payfast_process_payments_table`
3. Confirm migrations include idempotency / `processing_status` columns
4. Review the `payfast` log channel

### Duplicate IPN Processing

**Error**: Same IPN applied more than once

**Solution**:
1. Keep `PAYFAST_IDEMPOTENCY_ENABLED=true`
2. Expect a second row with `processing_status=duplicate` and response `ALREADY_PROCESSED`
3. Completed payments are terminal — later failed IPNs will not overwrite them

## Database Issues

### Payment Not Saved

**Error**: Payment record not created

**Solution**:
1. Check database connection
2. Verify migrations have been run
3. Check table exists: `payfast_process_payments_table`
4. Review error logs
5. Check model fillable attributes

### Status Not Updating

**Error**: Payment status not changing

**Solution**:
1. Verify status constant is correct
2. Check database constraints
3. Review update logic
4. Check for database locks
5. Verify model is using correct table

## Email Issues

### Emails Not Sending

**Error**: Email notifications not received

**Solution**:
1. Check email configuration in Laravel
2. Verify `MAIL_*` environment variables
3. Check email templates exist
4. Review email service logs
5. Verify admin emails are configured

### Wrong Email Template

**Error**: Wrong template being used

**Solution**:
1. Check email template configuration
2. Verify templates are published
3. Check template paths
4. Review email service code

## Console Command Issues

### Command Not Found

**Error**: `Command "payfast:check-pending-payments" is not defined`

**Solution**:
1. Clear cache: `php artisan config:clear`
2. Verify command is registered
3. Check service provider is loaded
4. Run `composer dump-autoload`

### Command Fails

**Error**: Command execution fails

**Solution**:
1. Check transaction check URL is configured
2. Verify credentials are correct
3. Review command logs
4. Check database connection
5. Verify payment records exist

## Event Issues

### Events Not Firing

**Error**: Event listeners not executing

**Solution**:
1. Verify event is being dispatched
2. Check listener is registered
3. Review event service provider
4. Check for exceptions in listeners
5. Verify event class names are correct

### Listener Errors

**Error**: Listener throws exception

**Solution**:
1. Wrap listener code in try-catch
2. Check listener dependencies
3. Review error logs
4. Verify database connections
5. Check for missing services

## Performance Issues

### Slow Payment Processing

**Error**: Payments taking too long

**Solution**:
1. Check API response times
2. Review database queries
3. Optimize database indexes
4. Use queues for heavy operations
5. Review logging levels

### Memory Issues

**Error**: Out of memory errors

**Solution**:
1. Increase PHP memory limit
2. Optimize database queries
3. Use pagination for large datasets
4. Review logging configuration
5. Check for memory leaks

## Security Issues

### CSRF Token Mismatch

**Error**: CSRF token mismatch on IPN endpoint

**Solution**:
1. Keep `PAYFAST_IPN_USE_API_MIDDLEWARE=true` so the route uses the `api` group (no CSRF)
2. If you use a custom `web` route, add it to `$except` in `VerifyCsrfToken`
3. Confirm `route:list` shows middleware including `api`, not only `web`

### Unauthorized Access / Invalid Signature

**Error**: `IPN_IP_FORBIDDEN` or `IPN_SIGNATURE_INVALID`

**Solution**:
1. Configure `PAYFAST_IPN_ALLOWED_IPS` with PayFast IPs/CIDRs (or leave empty in sandbox)
2. Set `PAYFAST_IPN_SECRET` and sign the **raw** body with HMAC-SHA256
3. Match header name to `PAYFAST_IPN_SIGNATURE_HEADER` (default `X-PayFast-Signature`)
4. Rejected attempts still appear in `payfast_ipn_table` with `processing_status=rejected`

## General Debugging

### Enable Debugging

Add to `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Check Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# PayFast specific logs
tail -f storage/logs/Payfast/*.log
```

### Use Tinker

```bash
php artisan tinker

# Test facade
use zfhassaan\Payfast\Facades\PayFast;
PayFast::getToken();

# Check configuration
config('payfast.mode');
config('payfast.merchant_id');
```

### Database Queries

```php
// Check payments
use zfhassaan\Payfast\Models\ProcessPayment;
ProcessPayment::all();

// Check activity logs
use zfhassaan\Payfast\Models\ActivityLog;
ActivityLog::latest()->limit(10)->get();
```

## Getting Help

If you're still experiencing issues:

1. **Check Documentation**: Review all wiki pages
2. **Check Issues**: Search GitHub issues
3. **Review Logs**: Check application and PayFast logs
4. **Test in Sandbox**: Verify in sandbox mode first
5. **Contact Support**: Email zfhassaan@gmail.com

## Common Error Codes

### PayFast Error Codes

- `00` - Success
- `01` - Invalid request
- `02` - Authentication failed
- `03` - Transaction failed
- `04` - Invalid card
- `05` - Insufficient funds
- `06` - Transaction declined

### Package Error Codes

- `AUTH_ERROR` - Authentication error
- `VALIDATION_ERROR` - Validation error
- `IPN_ERROR` - IPN processing error
- `EXCEPTION` - Exception occurred

## Next Steps

- [Security Best Practices](Security-Best-Practices.md) - Security guidelines
- [API Reference](API-Reference.md) - Method documentation
- [Payment Flows](Payment-Flows.md) - Payment processing















