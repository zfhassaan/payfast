# Models and Database

This document describes the database schema, models, and how to work with them.

## Database Tables

The package creates three main database tables:

1. `payfast_process_payments_table` - Stores payment records
2. `payfast_activity_logs_table` - Stores payment activity logs
3. `payfast_ipn_table` - Stores IPN (Instant Payment Notification) logs

## ProcessPayment Model

### Table: `payfast_process_payments_table`

Stores all payment records and their status.

### Schema

```php
Schema::create('payfast_process_payments_table', function (Blueprint $table) {
    $table->id();
    $table->uuid('uid')->unique();
    $table->string('token')->nullable();
    $table->string('orderNo')->nullable();
    $table->string('data_3ds_secureid')->nullable();
    $table->string('data_3ds_pares')->nullable();
    $table->string('transaction_id')->nullable();
    $table->enum('status', [
        'pending',
        'validated',
        'otp_verified',
        'completed',
        'failed',
        'cancelled'
    ])->default('pending');
    $table->enum('payment_method', [
        'card',
        'easypaisa',
        'jazzcash',
        'upaisa'
    ])->nullable();
    $table->text('payload')->nullable();
    $table->text('requestData')->nullable();
    $table->timestamp('otp_verified_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    
    $table->index('transaction_id');
    $table->index('orderNo');
    $table->index('status');
});
```

### Model Usage

```php
use zfhassaan\Payfast\Models\ProcessPayment;

// Create a payment
$payment = ProcessPayment::create([
    'uid' => \Str::uuid(),
    'token' => 'auth_token',
    'orderNo' => 'ORD-12345',
    'transaction_id' => 'TXN123456',
    'status' => ProcessPayment::STATUS_VALIDATED,
    'payment_method' => ProcessPayment::METHOD_CARD,
]);

// Find by transaction ID
$payment = ProcessPayment::where('transaction_id', 'TXN123456')->first();

// Find by order number
$payment = ProcessPayment::where('orderNo', 'ORD-12345')->first();

// Find by status
$payments = ProcessPayment::where('status', ProcessPayment::STATUS_COMPLETED)->get();
```

### Status Constants

```php
ProcessPayment::STATUS_PENDING      // Initial state
ProcessPayment::STATUS_VALIDATED    // Customer validated
ProcessPayment::STATUS_OTP_VERIFIED // OTP verified
ProcessPayment::STATUS_COMPLETED     // Payment completed
ProcessPayment::STATUS_FAILED       // Payment failed
ProcessPayment::STATUS_CANCELLED     // Payment cancelled
```

### Payment Method Constants

```php
ProcessPayment::METHOD_CARD       // Card payment
ProcessPayment::METHOD_EASYPAISA // EasyPaisa wallet
ProcessPayment::METHOD_JAZZCASH  // JazzCash wallet
ProcessPayment::METHOD_UPAISA   // UPaisa wallet
```

### Helper Methods

#### Status Check Methods

```php
$payment->isPending();      // Check if pending
$payment->isValidated();    // Check if validated
$payment->isOtpVerified();  // Check if OTP verified
$payment->isCompleted();    // Check if completed
$payment->isFailed();       // Check if failed
```

#### Status Update Methods

```php
$payment->markAsValidated();    // Mark as validated
$payment->markAsOtpVerified();  // Mark as OTP verified
$payment->markAsCompleted();     // Mark as completed
$payment->markAsFailed($reason);  // Mark as failed with reason
```

### Example Usage

```php
use zfhassaan\Payfast\Models\ProcessPayment;

// Create payment
$payment = ProcessPayment::create([
    'uid' => \Str::uuid(),
    'orderNo' => 'ORD-12345',
    'status' => ProcessPayment::STATUS_PENDING,
]);

// Update status
$payment->markAsValidated();

// Check status
if ($payment->isValidated()) {
    // Payment is validated
}

// Find and update
$payment = ProcessPayment::where('transaction_id', 'TXN123456')->first();
if ($payment) {
    $payment->markAsCompleted();
}
```

## ActivityLog Model

### Table: `payfast_activity_logs_table`

Stores payment activity logs for audit purposes.

### Schema

```php
Schema::create('payfast_activity_logs_table', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('transaction_id')->nullable();
    $table->string('order_no')->nullable();
    $table->string('status');
    $table->decimal('amount', 10, 2)->default(0);
    $table->text('details')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('transaction_date')->nullable();
    $table->timestamps();
    
    $table->index('transaction_id');
    $table->index('order_no');
    $table->index('user_id');
});
```

### Model Usage

```php
use zfhassaan\Payfast\Models\ActivityLog;

// Create activity log
ActivityLog::create([
    'user_id' => 1,
    'transaction_id' => 'TXN123456',
    'order_no' => 'ORD-12345',
    'status' => 'completed',
    'amount' => 1000.00,
    'details' => json_encode(['message' => 'Payment completed']),
]);

// Find by transaction ID
$logs = ActivityLog::where('transaction_id', 'TXN123456')->get();

// Find by order number
$logs = ActivityLog::where('order_no', 'ORD-12345')->get();
```

## IPNLog Model

### Table: `payfast_ipn_table`

Stores IPN (Instant Payment Notification) logs.

### Schema

```php
Schema::create('payfast_ipn_table', function (Blueprint $table) {
    $table->id();
    $table->string('transaction_id')->nullable();
    $table->string('order_no')->nullable();
    $table->string('status')->nullable();
    $table->decimal('amount', 10, 2)->nullable();
    $table->string('currency')->nullable();
    $table->text('ipn_data')->nullable();
    $table->boolean('processed')->default(false);
    $table->text('processing_result')->nullable();
    $table->timestamps();
    
    $table->index('transaction_id');
    $table->index('order_no');
    $table->index('processed');
});
```

### Model Usage

```php
use zfhassaan\Payfast\Models\IPNLog;

// Create IPN log
IPNLog::create([
    'transaction_id' => 'TXN123456',
    'order_no' => 'ORD-12345',
    'status' => '00',
    'amount' => 1000.00,
    'currency' => 'PKR',
    'ipn_data' => json_encode($ipnData),
    'processed' => true,
]);

// Find unprocessed IPNs
$unprocessed = IPNLog::where('processed', false)->get();

// Find by transaction ID
$log = IPNLog::where('transaction_id', 'TXN123456')->first();
```

## Repositories

The package uses repository pattern for data access. Repositories provide a clean interface for database operations.

### ProcessPaymentRepository

```php
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;

$repository = app(ProcessPaymentRepositoryInterface::class);

// Create payment
$payment = $repository->create([
    'orderNo' => 'ORD-12345',
    'status' => 'validated',
]);

// Find by transaction ID
$payment = $repository->findByTransactionId('TXN123456');

// Find by basket ID
$payment = $repository->findByBasketId('ORD-12345');

// Update payment
$repository->update($payment->id, [
    'status' => 'completed',
]);
```

### ActivityLogRepository

```php
use zfhassaan\Payfast\Repositories\Contracts\ActivityLogRepositoryInterface;

$repository = app(ActivityLogRepositoryInterface::class);

// Create log
$repository->create([
    'transaction_id' => 'TXN123456',
    'status' => 'completed',
]);
```

### IPNLogRepository

```php
use zfhassaan\Payfast\Repositories\Contracts\IPNLogRepositoryInterface;

$repository = app(IPNLogRepositoryInterface::class);

// Create IPN log
$repository->create([
    'transaction_id' => 'TXN123456',
    'ipn_data' => json_encode($data),
]);
```

## Factories

The package includes factories for testing:

### ProcessPaymentFactory

```php
use zfhassaan\Payfast\Models\ProcessPayment;

// Create a payment
$payment = ProcessPayment::factory()->create();

// Create with specific status
$payment = ProcessPayment::factory()->validated()->create();
$payment = ProcessPayment::factory()->otpVerified()->create();
$payment = ProcessPayment::factory()->completed()->create();
$payment = ProcessPayment::factory()->failed()->create();

// Create with specific data
$payment = ProcessPayment::factory()->create([
    'orderNo' => 'ORD-12345',
    'transaction_id' => 'TXN123456',
]);
```

## Relationships

You can add relationships to your models:

```php
// In your Order model
public function payments()
{
    return $this->hasMany(ProcessPayment::class, 'orderNo', 'order_number');
}

// In your User model
public function paymentActivities()
{
    return $this->hasMany(ActivityLog::class, 'user_id');
}
```

## Querying

### Common Queries

```php
use zfhassaan\Payfast\Models\ProcessPayment;

// Get all completed payments
$payments = ProcessPayment::where('status', ProcessPayment::STATUS_COMPLETED)->get();

// Get payments for a date range
$payments = ProcessPayment::whereBetween('created_at', [$startDate, $endDate])->get();

// Get payments by method
$cardPayments = ProcessPayment::where('payment_method', ProcessPayment::METHOD_CARD)->get();

// Get pending payments
$pending = ProcessPayment::where('status', ProcessPayment::STATUS_PENDING)
    ->orWhere('status', ProcessPayment::STATUS_VALIDATED)
    ->get();

// Get failed payments
$failed = ProcessPayment::where('status', ProcessPayment::STATUS_FAILED)->get();
```

### Aggregations

```php
// Total amount of completed payments
$total = ProcessPayment::where('status', ProcessPayment::STATUS_COMPLETED)
    ->sum('amount');

// Count by status
$counts = ProcessPayment::selectRaw('status, count(*) as count')
    ->groupBy('status')
    ->get();

// Average transaction amount
$average = ProcessPayment::where('status', ProcessPayment::STATUS_COMPLETED)
    ->avg('amount');
```

## Soft Deletes

All models use soft deletes. Deleted records are not permanently removed:

```php
// Soft delete
$payment->delete();

// Restore
$payment->restore();

// Permanently delete
$payment->forceDelete();

// Get with trashed
$payments = ProcessPayment::withTrashed()->get();

// Get only trashed
$trashed = ProcessPayment::onlyTrashed()->get();
```

## Migrations

### Running Migrations

```bash
php artisan migrate
```

### Rolling Back Migrations

```bash
php artisan migrate:rollback
```

### Publishing Migrations

```bash
php artisan vendor:publish --tag=payfast-migrations
```

## Best Practices

1. **Use repositories** - Use repository interfaces for data access
2. **Use status constants** - Don't hardcode status strings
3. **Index frequently queried fields** - transaction_id, orderNo, status
4. **Use soft deletes** - Preserve data for audit purposes
5. **Log activities** - Use ActivityLog for audit trails
6. **Validate data** - Validate before saving to database

## Next Steps

- [Payment Flows](Payment-Flows.md) - Understand payment processing
- [Events and Listeners](Events-and-Listeners.md) - Event system
- [Console Commands](Console-Commands.md) - CLI commands

