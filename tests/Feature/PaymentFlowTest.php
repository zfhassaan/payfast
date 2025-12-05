<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use Mockery;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use zfhassaan\Payfast\PayFast;
use zfhassaan\Payfast\Provider\PayFastServiceProvider;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Services\Contracts\AuthenticationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\PaymentServiceInterface;

class PaymentFlowTest extends OrchestraTestCase
{

    public function __construct(private readonly Payfast $payfast, string $name)
    {
        parent::__construct($name);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../src/database/migrations');

        // Set test configuration
        $this->app['config']->set('payfast.api_url', 'https://api.payfast.test');
        $this->app['config']->set('payfast.sandbox_api_url', 'https://sandbox.payfast.test');
        $this->app['config']->set('payfast.merchant_id', 'test_merchant_id');
        $this->app['config']->set('payfast.secured_key', 'test_secured_key');
        $this->app['config']->set('payfast.grant_type', 'client_credentials');
        $this->app['config']->set('payfast.return_url', 'https://example.com/callback');
        $this->app['config']->set('payfast.mode', 'sandbox');
        $this->app['config']->set('payfast.store_id', 'test_store_id');
        $this->app['config']->set('payfast.admin_emails', 'admin@example.com');
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PayFastServiceProvider::class,
        ];
    }

    public function testCompletePaymentFlowWithOTPVerification(): void
    {
        Event::fake();

        $paymentData = [
            'orderNumber' => 'ORD-123',
            'transactionAmount' => 1000.00,
            'customerMobileNo' => '03001234567',
            'customer_email' => 'test@example.com',
            'cardNumber' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
        ];

        // Mock authentication service
        $authService = Mockery::mock(AuthenticationServiceInterface::class);
        $authService->shouldReceive('getToken')
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        // Mock payment service
        $paymentService = Mockery::mock(PaymentServiceInterface::class);
        $paymentService->shouldReceive('validateCustomer')
            ->andReturn([
                'code' => '00',
                'transaction_id' => 'TXN-123',
                'data_3ds_secureid' => '3DS-123',
            ]);

        $this->app->instance(AuthenticationServiceInterface::class, $authService);
        $this->app->instance(PaymentServiceInterface::class, $paymentService);

        // Step 1: Get OTP Screen (Customer Validation)
        $response = $this->payfast->getOTPScreen($paymentData);
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);
        $this->assertArrayHasKey('transaction_id', $result['data']);

        // Verify payment stored in database
        $payment = ProcessPayment::where('transaction_id', 'TXN-123')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(ProcessPayment::STATUS_VALIDATED, $payment->status);

        Event::assertDispatched(PaymentValidated::class);

        // Step 2: Verify OTP and Store Pares
        $pares = 'pares_value_123';
        $response = $this->payfast->verifyOTPAndStorePares('TXN-123', '123456', $pares);
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);

        $payment->refresh();
        $this->assertEquals(ProcessPayment::STATUS_OTP_VERIFIED, $payment->status);
        $this->assertEquals($pares, $payment->data_3ds_pares);

        // Step 3: Complete Transaction from Callback
        $paymentService->shouldReceive('initiateTransaction')
            ->andReturn([
                'code' => '00',
                'message' => 'Transaction successful',
            ]);

        $response = $this->payfast->completeTransactionFromPares($pares);
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);

        $payment->refresh();
        $this->assertEquals(ProcessPayment::STATUS_COMPLETED, $payment->status);
        $this->assertNotNull($payment->completed_at);

        Event::assertDispatched(PaymentCompleted::class);
    }

    public function testPaymentFlowWithValidationFailure(): void
    {
        Event::fake();

        $paymentData = [
            'orderNumber' => 'ORD-123',
            'transactionAmount' => 1000.00,
            'customerMobileNo' => '03001234567',
            'customer_email' => 'test@example.com',
            'cardNumber' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
        ];

        $authService = Mockery::mock(AuthenticationServiceInterface::class);
        $authService->shouldReceive('getToken')
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $paymentService = Mockery::mock(PaymentServiceInterface::class);
        $paymentService->shouldReceive('validateCustomer')
            ->andReturn([
                'code' => '14',
                'message' => 'Entered details are Incorrect',
            ]);

        $this->app->instance(AuthenticationServiceInterface::class, $authService);
        $this->app->instance(PaymentServiceInterface::class, $paymentService);

        $response = $this->payfast->getOTPScreen($paymentData);
        $result = json_decode($response->getContent(), true);

        $this->assertFalse($result['status']);
        $this->assertEquals('14', $result['code']);

        Event::assertDispatched(PaymentFailed::class);
    }

    public function testWalletPaymentFlow(): void
    {
        Event::fake();

        $paymentData = [
            'basket_id' => 'ORD-123',
            'txnamt' => 1000.00,
            'customer_mobile_no' => '03001234567',
            'customer_email_address' => 'test@example.com',
        ];

        $authService = Mockery::mock(AuthenticationServiceInterface::class);
        $authService->shouldReceive('getToken')
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $paymentService = Mockery::mock(PaymentServiceInterface::class);
        $paymentService->shouldReceive('validateWalletTransaction')
            ->andReturn([
                'code' => '00',
                'transaction_id' => 'TXN-123',
            ]);

        $this->app->instance(AuthenticationServiceInterface::class, $authService);
        $this->app->instance(PaymentServiceInterface::class, $paymentService);

        $response = $this->payfast->payWithEasyPaisa($paymentData);
        $result = json_decode($response, true);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);

        $payment = ProcessPayment::where('transaction_id', 'TXN-123')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(ProcessPayment::METHOD_EASYPAISA, $payment->payment_method);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

