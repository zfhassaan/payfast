<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use zfhassaan\Payfast\Http\Middleware\LogIncomingIpn;
use zfhassaan\Payfast\Models\IPNLog;
use zfhassaan\Payfast\Repositories\IPNLogRepository;

class LogIncomingIpnTest extends TestCase
{
    use RefreshDatabase;

    public function testPersistsEveryInboundRequestBeforeNextMiddleware(): void
    {
        $middleware = new LogIncomingIpn(new IPNLogRepository());
        $request = Request::create('/payfast/ipn', 'POST', [
            'transaction_id' => 'TXN-LOG-1',
            'basket_id' => 'ORD-LOG-1',
            'status' => '00',
            'amount' => 250,
        ], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_AUTHORIZATION' => 'Bearer secret-token',
        ]);

        $passed = false;
        $middleware->handle($request, function (Request $req) use (&$passed) {
            $passed = true;
            $this->assertNotNull($req->attributes->get(LogIncomingIpn::REQUEST_ATTR_LOG_ID));

            return response()->json(['ok' => true]);
        });

        $this->assertTrue($passed);
        $this->assertDatabaseHas('payfast_ipn_table', [
            'transaction_id' => 'TXN-LOG-1',
            'processing_status' => IPNLog::STATUS_RECEIVED,
        ]);

        $log = IPNLog::where('transaction_id', 'TXN-LOG-1')->first();
        $this->assertNotNull($log);
        $this->assertEquals(['[redacted]'], $log->request_headers['authorization'] ?? null);
    }
}
