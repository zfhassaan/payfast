<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use zfhassaan\Payfast\Helpers\Utility;

class UtilityTest extends TestCase
{
    // No database needed for utility tests

    public function testReturnSuccess(): void
    {
        $data = ['message' => 'Success'];
        $response = Utility::returnSuccess($data, 'Operation completed successfully', '00');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertTrue($content['status']);
        $this->assertEquals($data, $content['data']);
        $this->assertEquals('00', $content['code']);
    }

    public function testReturnError(): void
    {
        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('info')
            ->once();

        // Channel is called twice: once to check if it exists, once to log
        Log::shouldReceive('channel')
            ->twice()
            ->with('Payfast')
            ->andReturn($logChannel);

        $response = Utility::returnError([], 'Error message', 'ERROR_CODE', Response::HTTP_BAD_REQUEST);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertFalse($content['status']);
        $this->assertEquals('Error message', $content['message']);
        $this->assertEquals('ERROR_CODE', $content['code']);
    }

    public function testPayfastErrorCodes(): void
    {
        $response = Utility::payfastErrorCodes('00');
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error_description', $content);
        $this->assertEquals('Processed OK', $content['error_description']);

        $response = Utility::payfastErrorCodes('14');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals('Entered details are Incorrect', $content['error_description']);

        $response = Utility::payfastErrorCodes('UNKNOWN');
        $content = json_decode($response->getContent(), true);

        $this->assertEquals('Unknown Error Code', $content['error_description']);
        $this->assertEquals(406, $response->getStatusCode());
    }

    public function testLogData(): void
    {
        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('info')
            ->once()
            ->with(\Mockery::pattern('/===== Test Identifier ======/'));

        // Channel is called twice: once to check if it exists (line 35), once to log (line 38)
        Log::shouldReceive('channel')
            ->twice()
            ->with('TestChannel')
            ->andReturn($logChannel);

        // Verify the method executes without errors
        Utility::logData('TestChannel', 'Test Identifier', ['test' => 'data']);
        
        // Verify the log data was formatted correctly
        $this->assertTrue(true); // Method executed successfully
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

