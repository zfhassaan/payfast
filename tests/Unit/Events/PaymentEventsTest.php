<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use Tests\TestCase;
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;
use zfhassaan\Payfast\Events\PaymentInitiated;
use zfhassaan\Payfast\Events\PaymentValidated;
use zfhassaan\Payfast\Events\TokenRefreshed;

class PaymentEventsTest extends TestCase
{
    public function testPaymentInitiatedEvent(): void
    {
        $paymentData = ['orderNumber' => 'ORD-123'];
        $response = ['code' => '00'];

        $event = new PaymentInitiated($paymentData, $response);

        $this->assertEquals($paymentData, $event->paymentData);
        $this->assertEquals($response, $event->response);
    }

    public function testPaymentValidatedEvent(): void
    {
        $paymentData = ['orderNumber' => 'ORD-123'];
        $validationResponse = ['transaction_id' => 'TXN-123'];

        $event = new PaymentValidated($paymentData, $validationResponse);

        $this->assertEquals($paymentData, $event->paymentData);
        $this->assertEquals($validationResponse, $event->validationResponse);
    }

    public function testPaymentCompletedEvent(): void
    {
        $transactionData = ['transaction_id' => 'TXN-123'];
        $response = ['code' => '00', 'message' => 'Success'];

        $event = new PaymentCompleted($transactionData, $response);

        $this->assertEquals($transactionData, $event->transactionData);
        $this->assertEquals($response, $event->response);
    }

    public function testPaymentFailedEvent(): void
    {
        $paymentData = ['orderNumber' => 'ORD-123'];
        $errorCode = '14';
        $errorMessage = 'Invalid card details';

        $event = new PaymentFailed($paymentData, $errorCode, $errorMessage);

        $this->assertEquals($paymentData, $event->paymentData);
        $this->assertEquals($errorCode, $event->errorCode);
        $this->assertEquals($errorMessage, $event->errorMessage);
    }

    public function testTokenRefreshedEvent(): void
    {
        $oldToken = 'old_token_123';
        $newToken = 'new_token_456';

        $event = new TokenRefreshed($oldToken, $newToken);

        $this->assertEquals($oldToken, $event->oldToken);
        $this->assertEquals($newToken, $event->newToken);
    }
}

