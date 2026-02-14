# Testing Guide

This guide covers testing the PayFast package, including unit tests, feature tests, and best practices.

## Overview

The PayFast package includes a comprehensive test suite covering:

- Service layer (Authentication, Payment, Transaction, OTP)
- Repository pattern implementation
- DTO creation and conversion
- Model methods and status transitions
- Event dispatching and handling
- Console commands
- Complete payment flows
- Error handling
- Edge cases

## Test Structure

```
tests/
├── Unit/
│   └── PayFast/
│       ├── TestCase.php                    # Base test case
│       ├── Services/
│       │   ├── AuthenticationServiceTest.php
│       │   ├── PaymentServiceTest.php
│       │   └── OTPVerificationServiceTest.php
│       ├── Repositories/
│       │   └── ProcessPaymentRepositoryTest.php
│       ├── DTOs/
│       │   └── PaymentRequestDTOTest.php
│       ├── Models/
│       │   └── ProcessPaymentTest.php
│       ├── Events/
│       │   └── PaymentEventsTest.php
│       ├── Listeners/
│       │   └── LogPaymentActivityTest.php
│       ├── Console/
│       │   └── CABPaymentsTest.php
│       ├── Helpers/
│       │   └── UtilityTest.php
│       └── PayFastTest.php                 # Main class tests
└── Feature/
    └── PayFast/
        └── PaymentFlowTest.php             # Integration tests
```

## Running Tests

### Run All Tests

```bash
php artisan test
```

### Run Only PayFast Tests

```bash
php artisan test --filter PayFast
```

### Run Specific Test Suite

```bash
# Unit tests only
php artisan test tests/Unit/PayFast

# Feature tests only
php artisan test tests/Feature/PayFast
```

### Run Specific Test Class

```bash
php artisan test tests/Unit/PayFast/Services/AuthenticationServiceTest.php
```

### Run with Coverage

```bash
php artisan test --coverage
```

## Test Categories

### Unit Tests

#### Service Tests

- **AuthenticationServiceTest**: Tests token retrieval and refresh
- **PaymentServiceTest**: Tests payment validation and transaction initiation
- **OTPVerificationServiceTest**: Tests OTP verification and pares handling
- **TransactionServiceTest**: Tests transaction queries and refunds

#### Repository Tests

- **ProcessPaymentRepositoryTest**: Tests CRUD operations and queries

#### DTO Tests

- **PaymentRequestDTOTest**: Tests data transfer object creation and conversion

#### Model Tests

- **ProcessPaymentTest**: Tests model methods and status transitions

#### Event Tests

- **PaymentEventsTest**: Tests event instantiation and data

#### Listener Tests

- **LogPaymentActivityTest**: Tests event listeners

#### Console Command Tests

- **CABPaymentsTest**: Tests console command functionality

### Feature Tests

#### Payment Flow Tests

- **PaymentFlowTest**: Tests complete payment flows including:
  - Card payment with OTP verification
  - Payment validation failures
  - Wallet payment flows

## Test Coverage

The test suite covers:

- Service layer (Authentication, Payment, Transaction, OTP)
- Repository pattern implementation
- DTO creation and conversion
- Model methods and status transitions
- Event dispatching and handling
- Console commands
- Complete payment flows
- Error handling
- Edge cases

## Mocking

Tests use Mockery for mocking dependencies:

```php
use Mockery;

$mock = Mockery::mock(ServiceInterface::class);
$mock->shouldReceive('method')
    ->once()
    ->andReturn(['result' => 'data']);
```

### Example: Mocking HTTP Client

```php
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;

$httpClient = Mockery::mock(HttpClientInterface::class);
$httpClient->shouldReceive('post')
    ->once()
    ->andReturn(['code' => '00', 'token' => 'test_token']);

$this->app->instance(HttpClientInterface::class, $httpClient);
```

## Factories

The package includes a factory for `ProcessPayment`:

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

## Test Data

Test data is generated using Faker and factories. Configuration is set in `TestCase::setUp()`:

```php
config([
    'payfast.api_url' => 'https://api.payfast.test',
    'payfast.merchant_id' => 'test_merchant_id',
    'payfast.secured_key' => 'test_secured_key',
    'payfast.mode' => 'sandbox',
]);
```

## Writing New Tests

### Unit Test Example

```php
<?php

namespace Tests\Unit\PayFast\Services;

use Tests\Unit\PayFast\TestCase;
use Mockery;
use zfhassaan\Payfast\Services\AuthenticationService;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;

class AuthenticationServiceTest extends TestCase
{
    public function test_get_token_returns_success(): void
    {
        // Arrange
        $httpClient = Mockery::mock(HttpClientInterface::class);
        $httpClient->shouldReceive('post')
            ->once()
            ->andReturn(['code' => '00', 'token' => 'test_token']);

        $service = new AuthenticationService($httpClient, app(ConfigService::class));

        // Act
        $result = $service->getToken();

        // Assert
        $this->assertEquals('00', $result['code']);
        $this->assertEquals('test_token', $result['token']);
    }
}
```

### Feature Test Example

```php
<?php

namespace Tests\Feature\PayFast;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Facades\PayFast;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_payment_flow(): void
    {
        // Arrange
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

        // Act
        $response = PayFast::getOTPScreen($paymentData);
        $result = json_decode($response->getContent(), true);

        // Assert
        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);

        // Verify payment was created
        $payment = ProcessPayment::where('orderNo', 'ORD-12345')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(ProcessPayment::STATUS_VALIDATED, $payment->status);
    }
}
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

## Testing Console Commands

```php
public function test_command_checks_pending_payments(): void
{
    // Create test payment
    $payment = ProcessPayment::factory()->validated()->create();

    // Run command
    $this->artisan('payfast:check-pending-payments', [
        '--limit' => 1,
    ])->assertExitCode(0);
}
```

## Best Practices

1. **Use descriptive test names**: `testGetTokenReturnsSuccessResponse()`
2. **Follow AAA pattern**: Arrange, Act, Assert
3. **Mock external dependencies**: Don't make real API calls
4. **Test edge cases**: Invalid inputs, failures, etc.
5. **Keep tests isolated**: Each test should be independent
6. **Use factories**: For creating test data
7. **Clean up**: Use `tearDown()` for cleanup

## Continuous Integration

Tests should pass in CI/CD pipelines. Ensure:

- All dependencies are installed
- Database migrations run
- Environment variables are set
- No external API calls are made

## Troubleshooting

### Tests failing due to migrations

Ensure migrations are loaded in `TestCase::setUp()`:

```php
$this->loadMigrationsFrom(__DIR__ . '/../../../packages/zfhassaan/payfast/src/database/migrations');
```

### Mock not working

Ensure you call `Mockery::close()` in `tearDown()`:

```php
protected function tearDown(): void
{
    Mockery::close();
    parent::tearDown();
}
```

### Database issues

Use `RefreshDatabase` trait for feature tests:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
```

## Publishing Tests

You can publish and customize tests:

```bash
php artisan vendor:publish --tag=payfast-tests
```

This will copy test files to your `tests` directory.

## Next Steps

- [Payment Flows](Payment-Flows.md) - Understand payment processing
- [API Reference](API-Reference.md) - Explore available methods
- [Troubleshooting](Troubleshooting.md) - Common issues









