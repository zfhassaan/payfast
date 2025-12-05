<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use Tests\TestCase;
use zfhassaan\Payfast\DTOs\PaymentRequestDTO;

class PaymentRequestDTOTest extends TestCase
{
    public function testFromArrayWithStandardKeys(): void
    {
        $data = [
            'orderNumber' => 'ORD-123',
            'transactionAmount' => 1000.00,
            'customerMobileNo' => '03001234567',
            'customer_email' => 'test@example.com',
            'cardNumber' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
        ];

        $dto = PaymentRequestDTO::fromArray($data);

        $this->assertEquals('ORD-123', $dto->orderNumber);
        $this->assertEquals(1000.00, $dto->transactionAmount);
        $this->assertEquals('03001234567', $dto->customerMobileNo);
        $this->assertEquals('test@example.com', $dto->customerEmail);
        $this->assertEquals('4111111111111111', $dto->cardNumber);
        $this->assertEquals('12', $dto->expiryMonth);
        $this->assertEquals('2025', $dto->expiryYear);
        $this->assertEquals('123', $dto->cvv);
    }

    public function testFromArrayWithAlternativeKeys(): void
    {
        $data = [
            'basket_id' => 'ORD-123',
            'txnamt' => 1000.00,
            'customer_mobile_no' => '03001234567',
            'customer_email_address' => 'test@example.com',
            'card_number' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
            'transaction_id' => 'TXN-123',
            'data_3ds_pares' => 'pares_value',
            'data_3ds_secureid' => '3ds_secure_id',
        ];

        $dto = PaymentRequestDTO::fromArray($data);

        $this->assertEquals('ORD-123', $dto->orderNumber);
        $this->assertEquals(1000.00, $dto->transactionAmount);
        $this->assertEquals('TXN-123', $dto->transactionId);
        $this->assertEquals('pares_value', $dto->data3dsPares);
        $this->assertEquals('3ds_secure_id', $dto->data3dsSecureId);
    }

    public function testToArray(): void
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

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('ORD-123', $array['basket_id']);
        $this->assertEquals(1000.00, $array['txnamt']);
        $this->assertEquals('03001234567', $array['customer_mobile_no']);
        $this->assertEquals('test@example.com', $array['customer_email_address']);
        $this->assertEquals('4111111111111111', $array['card_number']);
        $this->assertEquals('12', $array['expiry_month']);
        $this->assertEquals('2025', $array['expiry_year']);
        $this->assertEquals('123', $array['cvv']);
    }

    public function testOptionalFields(): void
    {
        $data = [
            'orderNumber' => 'ORD-123',
            'transactionAmount' => 1000.00,
            'customerMobileNo' => '03001234567',
            'customer_email' => 'test@example.com',
            'cardNumber' => '4111111111111111',
            'expiry_month' => '12',
            'expiry_year' => '2025',
            'cvv' => '123',
            'user_id' => 'USER-123',
        ];

        $dto = PaymentRequestDTO::fromArray($data);

        $this->assertEquals('USER-123', $dto->userId);
        $this->assertNull($dto->transactionId);
        $this->assertNull($dto->data3dsPares);
        $this->assertNull($dto->data3dsSecureId);
    }
}

