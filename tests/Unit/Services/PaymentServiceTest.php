<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Carbon\Carbon;
use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\DTOs\PaymentRequestDTO;
use zfhassaan\Payfast\Services\ConfigService;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;
use zfhassaan\Payfast\Services\PaymentService;

class PaymentServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private ConfigService $configService;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = Mockery::mock(HttpClientInterface::class);
        $this->configService = new ConfigService();
        $this->paymentService = new PaymentService(
            $this->httpClient,
            $this->configService
        );
    }

    public function testValidateCustomerReturnsSuccessResponse(): void
    {
        $dto = new PaymentRequestDTO(
            orderNumber: 'ORD-123',
            transactionAmount: 1000.00,
            customerMobileNo: '03001234567',
            customerEmail: 'test@example.com',
            cardNumber: '4111111111111111',
            expiryMonth: '12',
            expiryYear: '2025',
            cvv: '123'
        );

        $expectedResponse = [
            'code' => '00',
            'transaction_id' => 'TXN-123',
            'data_3ds_secureid' => '3DS-123',
        ];

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                Mockery::pattern('/customer\/validate$/'),
                Mockery::type('array'),
                Mockery::type('array')
            )
            ->andReturn($expectedResponse);

        $result = $this->paymentService->validateCustomer($dto);

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
        $this->assertArrayHasKey('transaction_id', $result);
    }

    public function testInitiateTransactionReturnsSuccessResponse(): void
    {
        $dto = new PaymentRequestDTO(
            orderNumber: 'ORD-123',
            transactionAmount: 1000.00,
            customerMobileNo: '03001234567',
            customerEmail: 'test@example.com',
            cardNumber: '4111111111111111',
            expiryMonth: '12',
            expiryYear: '2025',
            cvv: '123',
            transactionId: 'TXN-123',
            data3dsPares: 'pares_value',
            data3dsSecureId: '3DS-123'
        );

        $expectedResponse = [
            'code' => '00',
            'message' => 'Transaction successful',
            'transaction_id' => 'TXN-123',
        ];

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                Mockery::pattern('/transaction$/'),
                Mockery::type('array'),
                Mockery::type('array')
            )
            ->andReturn($expectedResponse);

        $result = $this->paymentService->initiateTransaction($dto, 'auth_token');

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
    }

    public function testValidateWalletTransactionReturnsSuccessResponse(): void
    {
        $data = [
            'basket_id' => 'ORD-123',
            'txnamt' => 1000.00,
            'customer_mobile_no' => '03001234567',
            'customer_email_address' => 'test@example.com',
        ];

        $expectedResponse = [
            'code' => '00',
            'transaction_id' => 'TXN-123',
        ];

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        $result = $this->paymentService->validateWalletTransaction($data, 13, 4);

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
    }

    public function testInitiateWalletTransactionReturnsSuccessResponse(): void
    {
        $data = [
            'basket_id' => 'ORD-123',
            'txnamt' => 1000.00,
            'transaction_id' => 'TXN-123',
        ];

        $expectedResponse = [
            'code' => '00',
            'message' => 'Transaction successful',
        ];

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        $result = $this->paymentService->initiateWalletTransaction($data, 'auth_token');

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

