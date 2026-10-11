<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use Tests\TestCase;
use zfhassaan\Payfast\Http\Requests\CardPaymentRequest;
use zfhassaan\Payfast\Http\Requests\IPNRequest;
use zfhassaan\Payfast\Http\Requests\WalletPaymentRequest;

class FormRequestTest extends TestCase
{
    public function testCardPaymentRequestAcceptsNormalizedKeys(): void
    {
        $validator = CardPaymentRequest::validateArray([
            'basket_id' => 'ORD-1',
            'txnamt' => 100,
            'customer_mobile_no' => '03001234567',
            'customer_email_address' => 'card@example.com',
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2028',
            'cvv' => '123',
        ]);

        $this->assertFalse($validator->fails());
        $this->assertEquals('ORD-1', $validator->validated()['orderNumber']);
    }

    public function testCardPaymentRequestRejectsMissingFields(): void
    {
        $validator = CardPaymentRequest::validateArray([
            'orderNumber' => 'ORD-1',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cardNumber', $validator->errors()->toArray());
    }

    public function testWalletPaymentRequestRequiresBasketAmountMobile(): void
    {
        $valid = WalletPaymentRequest::validateArray([
            'basket_id' => 'ORD-W',
            'txnamt' => 250,
            'customer_mobile_no' => '03001234567',
        ]);
        $this->assertFalse($valid->fails());

        $invalid = WalletPaymentRequest::validateArray([
            'customer_mobile_no' => '03001234567',
        ]);
        $this->assertTrue($invalid->fails());
    }

    public function testIpnRequestRequiresTransactionOrOrderIdentifier(): void
    {
        $valid = IPNRequest::validateArray([
            'transaction_id' => 'TXN-1',
            'status' => 'completed',
        ]);
        $this->assertFalse($valid->fails());

        $alsoValid = IPNRequest::validateArray([
            'basket_id' => 'ORD-1',
            'STATUS' => '00',
        ]);
        $this->assertFalse($alsoValid->fails());

        $invalid = IPNRequest::validateArray([
            'amount' => 100,
        ]);
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('transaction_id', $invalid->errors()->toArray());
    }
}
