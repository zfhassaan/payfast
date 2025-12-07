# Console Commands

The PayFast package includes a console command for managing payments.

## Available Commands

### payfast:check-pending-payments

Checks for pending payments, verifies their status with PayFast, updates payment status, logs activity, and sends email notifications.

#### Usage

```bash
php artisan payfast:check-pending-payments
```

#### Options

- `--status` - Filter by payment status (pending, validated, otp_verified, completed, failed)
- `--limit` - Limit the number of records to process (default: 50)
- `--no-email` - Skip sending email notifications

#### Examples

```bash
# Check all pending and validated payments
php artisan payfast:check-pending-payments

# Check specific status
php artisan payfast:check-pending-payments --status=otp_verified

# Limit results
php artisan payfast:check-pending-payments --limit=10

# Skip email notifications
php artisan payfast:check-pending-payments --no-email

# Combine options
php artisan payfast:check-pending-payments --status=validated --limit=20 --no-email
```

#### What It Does

1. **Queries Payments**: Finds payments with specified status (default: pending, validated, otp_verified)
2. **Verifies Status**: Checks transaction status with PayFast API
3. **Updates Status**: Updates payment status based on verification result
4. **Logs Activity**: Creates activity log entries
5. **Sends Notifications**: Sends email notifications (unless --no-email is used)

#### Output

```
Processing 5 payment(s)...
Checking payment: ORD-12345 (Status: validated)
✓ Payment ORD-12345 completed successfully
Checking payment: ORD-12346 (Status: validated)
✗ Payment ORD-12346 verification failed: Transaction not found
...

Processing complete!
Completed: 3
Processed: 5
Failed: 2
```

## Scheduling

You can schedule the command to run automatically using Laravel's task scheduler.

### Add to Kernel

In `app/Console/Kernel.php`:

```php
use Illuminate\Console\Scheduling\Schedule;

protected function schedule(Schedule $schedule): void
{
    // Run every 5 minutes
    $schedule->command('payfast:check-pending-payments')
        ->everyFiveMinutes()
        ->withoutOverlapping();
    
    // Or run every hour
    $schedule->command('payfast:check-pending-payments')
        ->hourly()
        ->withoutOverlapping();
    
    // Or run at specific times
    $schedule->command('payfast:check-pending-payments')
        ->dailyAt('02:00')
        ->withoutOverlapping();
}
```

### Cron Setup

Add to your server's crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## Customization

### Publishing Command

You can publish and customize the command:

```bash
php artisan vendor:publish --tag=payfast-command
```

This will create `app/Console/Commands/PayfastCheckPendingPayments.php`.

### Custom Command Example

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Facades\PayFast;

class CheckPayments extends Command
{
    protected $signature = 'payments:check {--status=}';
    protected $description = 'Check payment status';

    public function handle()
    {
        $status = $this->option('status') ?? 'validated';
        
        $payments = ProcessPayment::where('status', $status)
            ->limit(10)
            ->get();

        foreach ($payments as $payment) {
            $response = PayFast::getTransactionDetails($payment->transaction_id);
            $result = json_decode($response->getContent(), true);
            
            if ($result['status']) {
                $payment->markAsCompleted();
                $this->info("Payment {$payment->orderNo} completed");
            }
        }
    }
}
```

## Error Handling

The command includes error handling:

- **Catches exceptions** - Prevents command from crashing
- **Logs errors** - Logs errors for debugging
- **Continues processing** - Continues with next payment if one fails
- **Reports summary** - Shows summary at the end

## Testing

### Manual Testing

```bash
# Test with a single payment
php artisan payfast:check-pending-payments --limit=1

# Test with specific status
php artisan payfast:check-pending-payments --status=validated --limit=1
```

### Automated Testing

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use zfhassaan\Payfast\Models\ProcessPayment;

class PaymentCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_checks_pending_payments()
    {
        // Create test payment
        $payment = ProcessPayment::factory()->validated()->create();
        
        // Run command
        $this->artisan('payfast:check-pending-payments', [
            '--limit' => 1,
        ])->assertExitCode(0);
    }
}
```

## Configuration

The command uses the following configuration:

- `PAYFAST_VERIFY_TRANSACTION` - Transaction verification URL
- `PAYFAST_MERCHANT_ID` - Merchant ID for authentication
- `PAYFAST_SECURED_KEY` - Secured key for authentication

## Best Practices

1. **Schedule regularly** - Run the command on a schedule
2. **Use withoutOverlapping** - Prevent multiple instances
3. **Set appropriate limits** - Don't process too many at once
4. **Monitor logs** - Check logs for errors
5. **Test first** - Test with small limits before production
6. **Use queues** - Consider queuing for large batches

## Troubleshooting

### Command Not Found

**Solution**: Clear cache and ensure command is registered:

```bash
php artisan config:clear
php artisan cache:clear
```

### Transaction Check URL Not Configured

**Solution**: Set `PAYFAST_VERIFY_TRANSACTION` in `.env`:

```env
PAYFAST_VERIFY_TRANSACTION=https://api.payfast.com/transaction/view
```

### Authentication Errors

**Solution**: Verify credentials in `.env`:

```env
PAYFAST_MERCHANT_ID=your_merchant_id
PAYFAST_SECURED_KEY=your_secured_key
```

### Too Many Payments

**Solution**: Use `--limit` option:

```bash
php artisan payfast:check-pending-payments --limit=10
```

## Next Steps

- [Models and Database](Models-and-Database.md) - Database schema
- [Payment Flows](Payment-Flows.md) - Payment processing
- [Troubleshooting](Troubleshooting.md) - Common issues

