<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Events\TokenRefreshed;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\PayFast;
use zfhassaan\Payfast\Repositories\Contracts\ProcessPaymentRepositoryInterface;
use zfhassaan\Payfast\Services\Contracts\AuthenticationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\IPNServiceInterface;
use zfhassaan\Payfast\Services\Contracts\OTPVerificationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\PaymentServiceInterface;
use zfhassaan\Payfast\Services\Contracts\SubscriptionServiceInterface;
use zfhassaan\Payfast\Services\Contracts\TransactionServiceInterface;

class PayFastTest extends TestCase
{
    use RefreshDatabase;

    private AuthenticationServiceInterface $authenticationService;
    private PaymentServiceInterface $paymentService;
    private SubscriptionServiceInterface $subscriptionService;
    private TransactionServiceInterface $transactionService;
    private OTPVerificationServiceInterface $otpVerificationService;
    private ProcessPaymentRepositoryInterface $paymentRepository;
    private IPNServiceInterface $ipnService;
    private PayFast $payFast;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticationService = Mockery::mock(AuthenticationServiceInterface::class);
        $this->paymentService = Mockery::mock(PaymentServiceInterface::class);
        $this->subscriptionService = Mockery::mock(SubscriptionServiceInterface::class);
        $this->transactionService = Mockery::mock(TransactionServiceInterface::class);
        $this->otpVerificationService = Mockery::mock(OTPVerificationServiceInterface::class);
        $this->paymentRepository = Mockery::mock(ProcessPaymentRepositoryInterface::class);
        $this->ipnService = Mockery::mock(IPNServiceInterface::class);

        $this->payFast = new PayFast(
            $this->authenticationService,
            $this->paymentService,
            $this->subscriptionService,
            $this->transactionService,
            $this->otpVerificationService,
            $this->paymentRepository,
            $this->ipnService
        );
    }

    public function testGetTokenReturnsSuccessResponse(): void
    {
        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $response = $this->payFast->getToken();
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testGetTokenHandlesFailure(): void
    {
        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '401',
                'message' => 'Unauthorized',
            ]);

        $response = $this->payFast->getToken();
        $result = json_decode($response->getContent(), true);

        $this->assertFalse($result['status']);
    }

    public function testRefreshTokenDispatchesEvent(): void
    {
        Event::fake();

        $this->authenticationService
            ->shouldReceive('refreshToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'new_token_123'],
            ]);

        $response = $this->payFast->refreshToken('old_token', 'refresh_token');
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        Event::assertDispatched(TokenRefreshed::class);
    }

    public function testGetOTPScreenStoresPaymentInDatabase(): void
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

        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $this->paymentService
            ->shouldReceive('validateCustomer')
            ->once()
            ->andReturn([
                'code' => '00',
                'transaction_id' => 'TXN-123',
                'data_3ds_secureid' => '3DS-123',
            ]);

        $payment = ProcessPayment::factory()->make([
            'transaction_id' => 'TXN-123',
        ]);

        $this->paymentRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($payment);

        $response = $this->payFast->getOTPScreen($paymentData);
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('transaction_id', $result['data']);
        $this->assertArrayHasKey('payment_id', $result['data']);

        Event::assertDispatched(PaymentValidated::class);
    }

    public function testVerifyOTPAndStorePares(): void
    {
        $this->otpVerificationService
            ->shouldReceive('verifyOTPAndStorePares')
            ->once()
            ->with('TXN-123', '123456', 'pares_value')
            ->andReturn([
                'status' => true,
                'code' => '00',
                'data' => ['transaction_id' => 'TXN-123'],
            ]);

        $response = $this->payFast->verifyOTPAndStorePares('TXN-123', '123456', 'pares_value');
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);
    }

    public function testCompleteTransactionFromPares(): void
    {
        Event::fake();

        $this->otpVerificationService
            ->shouldReceive('completeTransactionFromPares')
            ->once()
            ->with('pares_value')
            ->andReturn([
                'status' => true,
                'code' => '00',
                'data' => ['transaction_id' => 'TXN-123'],
            ]);

        $response = $this->payFast->completeTransactionFromPares('pares_value');
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);
    }

    public function testListBanks(): void
    {
        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $this->transactionService
            ->shouldReceive('listBanks')
            ->once()
            ->with('test_token_123')
            ->andReturn([
                'banks' => [
                    ['id' => 1, 'name' => 'Bank A'],
                    ['id' => 2, 'name' => 'Bank B'],
                ],
            ]);

        $response = $this->payFast->listBanks();
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testGetTransactionDetails(): void
    {
        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $this->transactionService
            ->shouldReceive('getTransactionDetails')
            ->once()
            ->with('TXN-123', 'test_token_123')
            ->andReturn([
                'code' => '00',
                'transaction_id' => 'TXN-123',
                'status' => 'completed',
            ]);

        $response = $this->payFast->getTransactionDetails('TXN-123');
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertEquals('TXN-123', $result['data']['transaction_id']);
    }

    public function testVoidTransaction(): void
    {
        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $this->transactionService
            ->shouldReceive('voidTransaction')
            ->once()
            ->with('TXN-123', 'test_token_123')
            ->andReturn(['code' => '00', 'message' => 'Voided']);

        $response = $this->payFast->voidTransaction('TXN-123');
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);
    }

    public function testGetSettlementStatus(): void
    {
        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $this->transactionService
            ->shouldReceive('getSettlementStatus')
            ->once()
            ->with('TXN-123', 'test_token_123')
            ->andReturn(['code' => '00', 'status' => 'settled']);

        $response = $this->payFast->getSettlementStatus('TXN-123');
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);
    }

    public function testCreateSubscription(): void
    {
        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $data = [
            'orderNumber' => 'SUB-123',
            'transactionAmount' => 100.0,
            'customer_email' => 'test@example.com',
            'customerMobileNo' => '03001234567',
            'planId' => 'PLAN-1',
        ];

        $this->subscriptionService
            ->shouldReceive('createSubscription')
            ->once()
            ->with(Mockery::type(\zfhassaan\Payfast\DTOs\SubscriptionRequestDTO::class), 'test_token_123')
            ->andReturn(['code' => '00', 'message' => 'Success']);

        $response = $this->payFast->createSubscription($data);
        $result = json_decode($response->getContent(), true);

        $this->assertTrue($result['status']);
        $this->assertEquals('00', $result['code']);
    }

    public function testInitiateTransactionReturnsJsonResponse(): void
    {
        Event::fake();

        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $data = [
            'orderNumber' => 'ORD-123',
            'transactionAmount' => 1000.00,
            'customerMobileNo' => '03001234567',
            'customer_email' => 'test@example.com',
            'cardNumber' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
            'transaction_id' => 'TXN-123',
            'data_3ds_pares' => 'pares',
            'data_3ds_secureid' => '3DS',
        ];

        $this->paymentService
            ->shouldReceive('initiateTransaction')
            ->once()
            ->andReturn(['code' => '00', 'message' => 'Success']);

        $response = $this->payFast->initiateTransaction($data);
        $result = json_decode($response->getContent(), true);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertTrue($result['status']);
    }

    public function testRefundTransactionRequestReturnsJsonResponse(): void
    {
        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $this->transactionService
            ->shouldReceive('refundTransaction')
            ->once()
            ->andReturn(['code' => '00', 'message' => 'Refunded']);

        $response = $this->payFast->refundTransactionRequest(['transactionId' => 'TXN-123']);
        $result = json_decode($response->getContent(), true);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertTrue($result['status']);
    }

    public function testPayWithEasyPaisaReturnsJsonResponse(): void
    {
        \Illuminate\Support\Facades\Event::fake();

        $this->authenticationService
            ->shouldReceive('getToken')
            ->once()
            ->andReturn([
                'code' => '00',
                'data' => ['token' => 'test_token_123'],
            ]);

        $this->paymentService
            ->shouldReceive('validateWalletTransaction')
            ->once()
            ->andReturn(['code' => '00', 'transaction_id' => 'TXN-123']);

        $payment = ProcessPayment::factory()->make();
        $this->paymentRepository->shouldReceive('create')->andReturn($payment);

        $response = $this->payFast->payWithEasyPaisa(['customerMobileNo' => '03001234567']);
        $result = json_decode($response->getContent(), true);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertTrue($result['status']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

