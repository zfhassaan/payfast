# PayFast Package Testing Guide

## Overview

This document provides information about the test suite for the PayFast package.

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

- ✅ Service layer (Authentication, Payment, Transaction, OTP)
- ✅ Repository pattern implementation
- ✅ DTO creation and conversion
- ✅ Model methods and status transitions
- ✅ Event dispatching and handling
- ✅ Console commands
- ✅ Complete payment flows
- ✅ Error handling
- ✅ Edge cases

## Mocking

Tests use Mockery for mocking dependencies:

```php
$mock = Mockery::mock(ServiceInterface::class);
$mock->shouldReceive('method')
    ->once()
    ->andReturn(['result' => 'data']);
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
```

## Test Data

Test data is generated using Faker and factories. Configuration is set in `TestCase::setUp()`:

```php
config([
    'payfast.api_url' => 'https://api.payfast.test',
    'payfast.merchant_id' => 'test_merchant_id',
    // ...
]);
```

## Writing New Tests

### Unit Test Example

```php
<?php

namespace Tests\Unit\PayFast\Services;

use Tests\Unit\PayFast\TestCase;
use Mockery;

class MyServiceTest extends TestCase
{
    public function testMyMethod(): void
    {
        // Arrange
        $service = new MyService();
        
        // Act
        $result = $service->myMethod();
        
        // Assert
        $this->assertNotNull($result);
    }
}
```

### Feature Test Example

```php
<?php

namespace Tests\Feature\PayFast;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function testMyFeature(): void
    {
        // Test implementation
    }
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

