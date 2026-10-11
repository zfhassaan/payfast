<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\Http\Middleware\LogIncomingIpn;
use zfhassaan\Payfast\Http\Middleware\VerifyPayfastIpn;
use zfhassaan\Payfast\Services\Contracts\IPNServiceInterface;

class VerifyPayfastIpnTest extends TestCase
{
    private VerifyPayfastIpn $middleware;
    private IPNServiceInterface $ipnService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ipnService = Mockery::mock(IPNServiceInterface::class);
        $this->middleware = new VerifyPayfastIpn($this->ipnService);

        config([
            'payfast.ipn.secret' => '',
            'payfast.ipn.allowed_ips' => [],
            'payfast.ipn.signature_header' => 'X-PayFast-Signature',
            'payfast.ipn.signature_body_key' => 'signature',
        ]);
    }

    public function testAllowsRequestWhenNoSecurityConfigured(): void
    {
        $request = Request::create('/payfast/ipn', 'POST', ['transaction_id' => 'TXN-1']);
        $passed = false;

        $this->middleware->handle($request, function () use (&$passed) {
            $passed = true;
            return response()->json(['ok' => true]);
        });

        $this->assertTrue($passed);
    }

    public function testRejectsDisallowedIpAndMarksLog(): void
    {
        config(['payfast.ipn.allowed_ips' => ['203.0.113.10']]);

        $request = Request::create('/payfast/ipn', 'POST', ['transaction_id' => 'TXN-1'], [], [], [
            'REMOTE_ADDR' => '198.51.100.1',
        ]);
        $request->attributes->set(LogIncomingIpn::REQUEST_ATTR_LOG_ID, 99);

        $this->ipnService->shouldReceive('markRejected')
            ->once()
            ->with(99, 'IP not allowlisted', Mockery::type('array'));

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));
        $payload = json_decode($response->getContent(), true);

        $this->assertFalse($payload['status']);
        $this->assertEquals('IPN_IP_FORBIDDEN', $payload['code']);
    }

    public function testAllowsAllowlistedIp(): void
    {
        config(['payfast.ipn.allowed_ips' => ['127.0.0.1']]);

        $request = Request::create('/payfast/ipn', 'POST', ['transaction_id' => 'TXN-1'], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $passed = false;

        $this->middleware->handle($request, function () use (&$passed) {
            $passed = true;
            return response()->json(['ok' => true]);
        });

        $this->assertTrue($passed);
    }

    public function testRejectsMissingSignatureWhenSecretConfigured(): void
    {
        config(['payfast.ipn.secret' => 'super-secret']);

        $request = Request::create('/payfast/ipn', 'POST', ['transaction_id' => 'TXN-1'], [], [], [], '{"transaction_id":"TXN-1"}');
        $request->attributes->set(LogIncomingIpn::REQUEST_ATTR_LOG_ID, 7);

        $this->ipnService->shouldReceive('markRejected')
            ->once()
            ->with(7, 'Invalid signature');

        $response = $this->middleware->handle($request, fn () => response()->json(['ok' => true]));
        $payload = json_decode($response->getContent(), true);

        $this->assertFalse($payload['status']);
        $this->assertEquals('IPN_SIGNATURE_INVALID', $payload['code']);
    }

    public function testAcceptsValidHmacSignatureHeader(): void
    {
        $secret = 'super-secret';
        $body = '{"transaction_id":"TXN-1"}';
        $signature = hash_hmac('sha256', $body, $secret);

        config(['payfast.ipn.secret' => $secret]);

        $request = Request::create('/payfast/ipn', 'POST', [], [], [], [
            'HTTP_X_PAYFAST_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $body);

        $passed = false;
        $this->middleware->handle($request, function () use (&$passed) {
            $passed = true;
            return response()->json(['ok' => true]);
        });

        $this->assertTrue($passed);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
