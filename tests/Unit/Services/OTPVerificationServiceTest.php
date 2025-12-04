<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;
use zfhassaan\Payfast\Services\Contracts\AuthenticationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\PaymentServiceInterface;
use zfhassaan\Payfast\Services\OTPVerificationService;

class OTPVerificationServiceTest extends TestCase
{
    private ProcessPaymentRepositoryInterface $paymentRepository;
    private PaymentServiceInterface $paymentService;
    private AuthenticationServiceInterface $authenticationService;
    private OTPVerificationService $otpVerificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = Mockery::mock(ProcessPaymentRepositoryInterface::class);
        $this->paymentService = Mockery::mock(PaymentServiceInterface::class);
        $this->authenticationService = Mockery::mock(AuthenticationServiceInterface::class);

        $this->otpVerificationService = new OTPVerificationService(
            $this->paymentRepository,
            $this->paymentService,
            $this->authenticationService
        );
    }

    public function testVerifyOTPAndStoreParesSuccess(): void
    {
        $payment = new ProcessPayment();
        $payment->id = 1;
        $payment->transaction_id = 'TXN-123';
        $payment->status = ProcessPayment::STATUS_VALIDATED;
        $payment->requestData = json_encode(['orderNumber' => 'ORD-123']);

        $this->paymentRepository
            ->shouldReceive('findByTransactionId')
            ->once()
            ->with('TXN-123')
            ->andReturn($payment);

        $this->paymentRepository
            ->shouldReceive('update')
            ->once()
            ->with('1', Mockery::type('array'))
            ->andReturn(true);

        Event::fake();

        $result = $this->otpVerificationService->verifyOTPAndStorePares(
            'TXN-123',
            '123456',
            'pares_value_123'
        );

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);
        $this->assertArrayHasKey('data', $result);

        Event::assertDispatched(PaymentValidated::class);
    }

    public function testVerifyOTPAndStoreParesPaymentNotFound(): void
    {
        $this->paymentRepository
            ->shouldReceive('findByTransactionId')
            ->once()
            ->with('TXN-999')
            ->andReturn(null);

        $result = $this->otpVerificationService->verifyOTPAndStorePares(
            'TXN-999',
            '123456',
            'pares_value_123'
        );

        $this->assertFalse($result['status']);
        $this->assertEquals('PAYMENT_NOT_FOUND', $result['code']);
    }

    public function testVerifyOTPAndStoreParesInvalidStatus(): void
    {
        $payment = new ProcessPayment();
        $payment->id = 1;
        $payment->transaction_id = 'TXN-123';
        $payment->status = ProcessPayment::STATUS_PENDING;

        $this->paymentRepository
            ->shouldReceive('findByTransactionId')
            ->once()
            ->with('TXN-123')
            ->andReturn($payment);

        $result = $this->otpVerificationService->verifyOTPAndStorePares(
            'TXN-123',
            '123456',
            'pares_value_123'
        );

        $this->assertFalse($result['status']);
        $this->assertEquals('INVALID_STATUS', $result['code']);
    }

    public function testCompleteTransactionFromParesSuccess(): void
    {
        $payment = new ProcessPayment();
        $payment->id = 1;
        $payment->data_3ds_pares = 'pares_value_123';
        $payment->status = ProcessPayment::STATUS_OTP_VERIFIED;
        $payment->transaction_id = 'TXN-123';
        $payment->data_3ds_secureid = '3DS-123';
        $payment->requestData = json_encode([
            'orderNumber' => 'ORD-123',
            'transactionAmount' => 1000.00,
        ]);
        $payment->payload = json_encode([
            'customer_validate' => ['transaction_id' => 'TXN-123'],
        ]);

        // Mock repository
        $this->paymentRepository
            ->shouldReceive('findByPares')
            ->with('pares_value_123')
            ->andReturn($payment);

        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'data' => ['token' => 'auth_token_123'],
            ]);

        $this->paymentService
            ->shouldReceive('initiateTransaction')
            ->once()
            ->andReturn([
                'code' => '00',
                'message' => 'Transaction successful',
            ]);

        $this->paymentRepository
            ->shouldReceive('update')
            ->once()
            ->with('1', Mockery::type('array'))
            ->andReturn(true);

        Event::fake();

        $result = $this->otpVerificationService->completeTransactionFromPares('pares_value_123');

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);

        Event::assertDispatched(PaymentCompleted::class);
    }

    public function testCompleteTransactionFromParesPaymentNotFound(): void
    {
        $this->paymentRepository
            ->shouldReceive('findByPares')
            ->with('invalid_pares')
            ->andReturn(null);

        $result = $this->otpVerificationService->completeTransactionFromPares('invalid_pares');

        $this->assertIsArray($result);
        $this->assertFalse($result['status']);
        $this->assertEquals('PAYMENT_NOT_FOUND', $result['code']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testCompleteTransactionFromParesAuthFailure(): void
    {
        $payment = new ProcessPayment();
        $payment->id = 1;
        $payment->data_3ds_pares = 'pares_value_123';
        $payment->status = ProcessPayment::STATUS_OTP_VERIFIED;
        $payment->requestData = json_encode(['orderNumber' => 'ORD-123']);
        $payment->payload = json_encode(['customer_validate' => []]);

        $this->paymentRepository
            ->shouldReceive('findByPares')
            ->with('pares_value_123')
            ->andReturn($payment);

        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([]);

        $result = $this->otpVerificationService->completeTransactionFromPares('pares_value_123');

        $this->assertFalse($result['status']);
        $this->assertEquals('AUTH_ERROR', $result['code']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
