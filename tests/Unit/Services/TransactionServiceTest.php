<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\Services\ConfigService;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;
use zfhassaan\Payfast\Services\TransactionService;

class TransactionServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private ConfigService $configService;
    private TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = Mockery::mock(HttpClientInterface::class);
        $this->configService = new ConfigService();
        $this->transactionService = new TransactionService(
            $this->httpClient,
            $this->configService
        );
    }

    public function testGetTransactionDetailsReturnsSuccessResponse(): void
    {
        $expectedResponse = [
            'code' => '00',
            'transaction_id' => 'TXN-123',
            'status' => 'completed',
        ];

        $this->httpClient
            ->shouldReceive('get')
            ->once()
            ->with(
                Mockery::pattern('/transaction\/TXN-123$/'),
                Mockery::type('array')
            )
            ->andReturn($expectedResponse);

        $result = $this->transactionService->getTransactionDetails('TXN-123', 'auth_token');

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
        $this->assertEquals('TXN-123', $result['transaction_id']);
    }

    public function testGetTransactionDetailsByBasketIdReturnsSuccessResponse(): void
    {
        $expectedResponse = [
            'code' => '00',
            'basket_id' => 'ORD-123',
            'transaction_id' => 'TXN-123',
        ];

        $this->httpClient
            ->shouldReceive('get')
            ->once()
            ->with(
                Mockery::pattern('/transaction\/basket_idORD-123$/'),
                Mockery::type('array')
            )
            ->andReturn($expectedResponse);

        $result = $this->transactionService->getTransactionDetailsByBasketId('ORD-123', 'auth_token');

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
    }

    public function testRefundTransactionReturnsSuccessResponse(): void
    {
        $data = [
            'transactionId' => 'TXN-123',
            'transactionAmount' => 1000.00,
            'refund_reason' => 'Customer request',
        ];

        $expectedResponse = [
            'code' => '00',
            'message' => 'Refund processed',
        ];

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                Mockery::pattern('/transaction\/refund\/TXN-123$/'),
                Mockery::type('array'),
                Mockery::type('array')
            )
            ->andReturn($expectedResponse);

        $result = $this->transactionService->refundTransaction($data, 'auth_token');

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
    }

    public function testListBanksReturnsSuccessResponse(): void
    {
        $expectedResponse = [
            'banks' => [
                ['id' => 1, 'name' => 'Bank A'],
                ['id' => 2, 'name' => 'Bank B'],
            ],
        ];

        $this->httpClient
            ->shouldReceive('get')
            ->once()
            ->with(
                Mockery::pattern('/list\/banks$/'),
                Mockery::type('array')
            )
            ->andReturn($expectedResponse);

        $result = $this->transactionService->listBanks('auth_token');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('banks', $result);
    }

    public function testListInstrumentsWithBankReturnsSuccessResponse(): void
    {
        $expectedResponse = [
            'bankInstruments' => [
                ['id' => 1, 'name' => 'Account'],
                ['id' => 2, 'name' => 'Wallet'],
            ],
        ];

        $this->httpClient
            ->shouldReceive('get')
            ->once()
            ->with(
                Mockery::pattern('/list\/instruments\?bank_code=13$/'),
                Mockery::type('array')
            )
            ->andReturn($expectedResponse);

        $result = $this->transactionService->listInstrumentsWithBank(13, 'auth_token');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('bankInstruments', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

