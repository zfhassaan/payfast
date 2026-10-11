<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\DTOs\PaymentRequestDTO;
use zfhassaan\Payfast\DTOs\WalletPaymentRequestDTO;
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
        $dto = WalletPaymentRequestDTO::fromArray([
            'basket_id' => 'ORD-123',
            'txnamt' => 1000.00,
            'customer_mobile_no' => '03001234567',
            'customer_email_address' => 'test@example.com',
        ], WalletPaymentRequestDTO::BANK_CODE_EASYPAISA);

        $expectedResponse = [
            'code' => '00',
            'transaction_id' => 'TXN-123',
        ];

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                Mockery::pattern('/customer\/validate$/'),
                Mockery::on(function (array $payload) {
                    return ($payload['bank_code'] ?? null) === '13'
                        && ($payload['account_type_id'] ?? null) === '4';
                }),
                Mockery::on(function (array $headers) {
                    return ($headers['Content-Type'] ?? null) === 'application/x-www-form-urlencoded'
                        && !isset($headers['Authorization']);
                })
            )
            ->andReturn($expectedResponse);

        $result = $this->paymentService->validateWalletTransaction($dto);

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
    }

    public function testValidateWalletTransactionSendsBearerTokenForJazzCash(): void
    {
        $dto = WalletPaymentRequestDTO::fromArray([
            'basket_id' => 'ORD-JC',
            'txnamt' => 750.00,
            'customer_mobile_no' => '03001234567',
            'customer_email_address' => 'jazz@example.com',
            'order_date' => '2026-03-01',
        ], WalletPaymentRequestDTO::BANK_CODE_JAZZCASH);

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                Mockery::pattern('/customer\/validate$/'),
                Mockery::on(function (array $payload) {
                    return ($payload['bank_code'] ?? null) === '1'
                        && ($payload['account_type_id'] ?? null) === '4'
                        && ($payload['order_date'] ?? null) === '2026-03-01';
                }),
                Mockery::on(function (array $headers) {
                    return ($headers['Authorization'] ?? null) === 'Bearer jazz_auth_token';
                })
            )
            ->andReturn([
                'code' => '00',
                'transaction_id' => 'TXN-JC-1',
            ]);

        $result = $this->paymentService->validateWalletTransaction($dto, 'jazz_auth_token');

        $this->assertEquals('00', $result['code']);
        $this->assertEquals('TXN-JC-1', $result['transaction_id']);
    }

    public function testInitiateWalletTransactionReturnsSuccessResponse(): void
    {
        $dto = WalletPaymentRequestDTO::fromArray([
            'basket_id' => 'ORD-123',
            'txnamt' => 1000.00,
            'customer_mobile_no' => '03001234567',
            'transaction_id' => 'TXN-123',
        ], WalletPaymentRequestDTO::BANK_CODE_EASYPAISA);

        $expectedResponse = [
            'code' => '00',
            'message' => 'Transaction successful',
        ];

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        $result = $this->paymentService->initiateWalletTransaction($dto, 'auth_token');

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
