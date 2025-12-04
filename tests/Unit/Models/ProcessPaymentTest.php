<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\TestCase;
use zfhassaan\Payfast\Models\ProcessPayment;

class ProcessPaymentTest extends TestCase
{
    public function testStatusConstants(): void
    {
        $this->assertEquals('pending', ProcessPayment::STATUS_PENDING);
        $this->assertEquals('validated', ProcessPayment::STATUS_VALIDATED);
        $this->assertEquals('otp_verified', ProcessPayment::STATUS_OTP_VERIFIED);
        $this->assertEquals('completed', ProcessPayment::STATUS_COMPLETED);
        $this->assertEquals('failed', ProcessPayment::STATUS_FAILED);
        $this->assertEquals('cancelled', ProcessPayment::STATUS_CANCELLED);
    }

    public function testPaymentMethodConstants(): void
    {
        $this->assertEquals('card', ProcessPayment::METHOD_CARD);
        $this->assertEquals('easypaisa', ProcessPayment::METHOD_EASYPAISA);
        $this->assertEquals('jazzcash', ProcessPayment::METHOD_JAZZCASH);
        $this->assertEquals('upaisa', ProcessPayment::METHOD_UPAISA);
    }

    public function testStatusHelperMethods(): void
    {
        $payment = ProcessPayment::factory()->create(['status' => ProcessPayment::STATUS_PENDING]);
        $this->assertTrue($payment->isPending());
        $this->assertFalse($payment->isValidated());

        $payment->status = ProcessPayment::STATUS_VALIDATED;
        $this->assertTrue($payment->isValidated());
        $this->assertFalse($payment->isOtpVerified());

        $payment->status = ProcessPayment::STATUS_OTP_VERIFIED;
        $this->assertTrue($payment->isOtpVerified());

        $payment->status = ProcessPayment::STATUS_COMPLETED;
        $this->assertTrue($payment->isCompleted());

        $payment->status = ProcessPayment::STATUS_FAILED;
        $this->assertTrue($payment->isFailed());
    }

    public function testMarkAsValidated(): void
    {
        $payment = ProcessPayment::factory()->create(['status' => ProcessPayment::STATUS_PENDING]);

        $result = $payment->markAsValidated();

        $this->assertTrue($result);
        $payment->refresh();
        $this->assertEquals(ProcessPayment::STATUS_VALIDATED, $payment->status);
    }

    public function testMarkAsOtpVerified(): void
    {
        $payment = ProcessPayment::factory()->create(['status' => ProcessPayment::STATUS_VALIDATED]);

        $result = $payment->markAsOtpVerified();

        $this->assertTrue($result);
        $payment->refresh();
        $this->assertEquals(ProcessPayment::STATUS_OTP_VERIFIED, $payment->status);
        $this->assertNotNull($payment->otp_verified_at);
    }

    public function testMarkAsCompleted(): void
    {
        $payment = ProcessPayment::factory()->create(['status' => ProcessPayment::STATUS_OTP_VERIFIED]);

        $result = $payment->markAsCompleted();

        $this->assertTrue($result);
        $payment->refresh();
        $this->assertEquals(ProcessPayment::STATUS_COMPLETED, $payment->status);
        $this->assertNotNull($payment->completed_at);
    }

    public function testMarkAsFailed(): void
    {
        $payment = ProcessPayment::factory()->create(['status' => ProcessPayment::STATUS_VALIDATED]);

        $result = $payment->markAsFailed('Payment declined');

        $this->assertTrue($result);
        $payment->refresh();
        $this->assertEquals(ProcessPayment::STATUS_FAILED, $payment->status);

        $payload = json_decode($payment->payload, true);
        $this->assertEquals('Payment declined', $payload['failure_reason'] ?? null);
    }
}

