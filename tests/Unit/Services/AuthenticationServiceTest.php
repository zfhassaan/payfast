<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\Services\AuthenticationService;
use zfhassaan\Payfast\Services\ConfigService;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;

class AuthenticationServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private ConfigService $configService;
    private AuthenticationService $authenticationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = Mockery::mock(HttpClientInterface::class);
        $this->configService = new ConfigService();
        $this->authenticationService = new AuthenticationService(
            $this->httpClient,
            $this->configService
        );
    }

    public function testGetTokenReturnsSuccessResponse(): void
    {
        $expectedResponse = [
            'code' => '00',
            'data' => [
                'token' => 'test_token_123',
                'refresh_token' => 'test_refresh_token_123',
                'expires_in' => 3600,
            ],
        ];

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                Mockery::pattern('/token$/'),
                Mockery::type('array'),
                Mockery::type('array')
            )
            ->andReturn($expectedResponse);

        $result = $this->authenticationService->getToken();

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('token', $result['data']);
    }

    public function testGetTokenHandlesFailure(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn(['code' => '401', 'message' => 'Unauthorized']);

        $result = $this->authenticationService->getToken();

        $this->assertIsArray($result);
        $this->assertEquals('401', $result['code']);
    }

    public function testRefreshTokenReturnsSuccessResponse(): void
    {
        $expectedResponse = [
            'code' => '00',
            'data' => [
                'token' => 'new_token_123',
                'refresh_token' => 'new_refresh_token_123',
            ],
        ];

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                Mockery::pattern('/refreshtoken$/'),
                Mockery::type('array'),
                Mockery::type('array')
            )
            ->andReturn($expectedResponse);

        $result = $this->authenticationService->refreshToken('old_token', 'refresh_token');

        $this->assertIsArray($result);
        $this->assertEquals('00', $result['code']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testRefreshTokenHandlesFailure(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn(['code' => '400', 'message' => 'Invalid refresh token']);

        $result = $this->authenticationService->refreshToken('old_token', 'invalid_refresh');

        $this->assertIsArray($result);
        $this->assertEquals('400', $result['code']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

