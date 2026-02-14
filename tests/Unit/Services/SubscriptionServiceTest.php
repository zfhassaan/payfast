<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\DTOs\SubscriptionRequestDTO;
use zfhassaan\Payfast\Services\ConfigService;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;
use zfhassaan\Payfast\Services\SubscriptionService;

class SubscriptionServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private $configService;
    private SubscriptionService $subscriptionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = Mockery::mock(HttpClientInterface::class);
        $this->configService = Mockery::mock(ConfigService::class);
        $this->configService->shouldReceive('getApiUrl')->andReturn('https://api.example.com/');
        
        $this->subscriptionService = new SubscriptionService($this->configService, $this->httpClient);
    }

    public function testCreateSubscriptionReturnsSuccessResponse(): void
    {
        $dto = new SubscriptionRequestDTO(
            'ORD-123',
            100.0,
            'test@example.com',
            '03001234567',
            'PLAN-1'
        );

        $this->httpClient->shouldReceive('post')
            ->once()
            ->with(
                'https://api.example.com/subscription/create',
                Mockery::type('array'),
                Mockery::type('array')
            )
            ->andReturn(['code' => '00', 'message' => 'Success']);

        $response = $this->subscriptionService->createSubscription($dto, 'token123');
        $this->assertEquals('00', $response['code']);
    }

    public function testCancelSubscriptionReturnsSuccessResponse(): void
    {
        $this->httpClient->shouldReceive('post')
            ->once()
            ->with(
                'https://api.example.com/subscription/cancel/sub_123',
                [],
                Mockery::type('array')
            )
            ->andReturn(['code' => '00', 'message' => 'Cancelled']);

        $response = $this->subscriptionService->cancelSubscription('sub_123', 'token123');
        $this->assertEquals('00', $response['code']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
