<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use Tests\TestCase;
use zfhassaan\Payfast\DTOs\WalletPaymentRequestDTO;

class WalletPaymentRequestDTOTest extends TestCase
{
    public function testFromArrayWithStandardKeys(): void
    {
        $dto = WalletPaymentRequestDTO::fromArray([
            'orderNumber' => 'ORD-123',
            'transactionAmount' => 1000.00,
            'customerMobileNo' => '03001234567',
            'customer_email' => 'test@example.com',
        ], WalletPaymentRequestDTO::BANK_CODE_JAZZCASH);

        $this->assertEquals('ORD-123', $dto->orderNumber);
        $this->assertEquals(1000.00, $dto->transactionAmount);
        $this->assertEquals('03001234567', $dto->customerMobileNo);
        $this->assertEquals('test@example.com', $dto->customerEmail);
        $this->assertEquals(1, $dto->bankCode);
        $this->assertEquals(4, $dto->accountTypeId);
        $this->assertNotEmpty($dto->orderDate);
    }

    public function testFromArrayWithAlternativeKeys(): void
    {
        $dto = WalletPaymentRequestDTO::fromArray([
            'basket_id' => 'ORD-456',
            'txnamt' => 500.50,
            'customer_mobile_no' => '03009876543',
            'customer_email_address' => 'wallet@example.com',
            'order_date' => '2026-01-15',
            'account_type_id' => 4,
        ], WalletPaymentRequestDTO::BANK_CODE_EASYPAISA);

        $this->assertEquals('ORD-456', $dto->orderNumber);
        $this->assertEquals(500.50, $dto->transactionAmount);
        $this->assertEquals('03009876543', $dto->customerMobileNo);
        $this->assertEquals('wallet@example.com', $dto->customerEmail);
        $this->assertEquals(13, $dto->bankCode);
        $this->assertEquals('2026-01-15', $dto->orderDate);
    }

    public function testToArrayIncludesWalletFields(): void
    {
        $dto = WalletPaymentRequestDTO::fromArray([
            'basket_id' => 'ORD-789',
            'txnamt' => 250.00,
            'customer_mobile_no' => '03001112233',
            'customer_email_address' => 'jc@example.com',
            'order_date' => '2026-02-01',
        ], WalletPaymentRequestDTO::BANK_CODE_JAZZCASH);

        $array = $dto->toArray();

        $this->assertEquals('ORD-789', $array['basket_id']);
        $this->assertEquals(250.00, $array['txnamt']);
        $this->assertEquals(1, $array['bank_code']);
        $this->assertEquals(4, $array['account_type_id']);
        $this->assertEquals('2026-02-01', $array['order_date']);
    }

    public function testIsValidRequiresRequiredFields(): void
    {
        $valid = WalletPaymentRequestDTO::fromArray([
            'basket_id' => 'ORD-1',
            'txnamt' => 100,
            'customer_mobile_no' => '03001234567',
        ], WalletPaymentRequestDTO::BANK_CODE_JAZZCASH);

        $this->assertTrue($valid->isValid());

        $invalid = WalletPaymentRequestDTO::fromArray([
            'customer_mobile_no' => '03001234567',
        ], WalletPaymentRequestDTO::BANK_CODE_JAZZCASH);

        $this->assertFalse($invalid->isValid());
    }

    public function testBankCodeConstants(): void
    {
        $this->assertEquals(1, WalletPaymentRequestDTO::BANK_CODE_JAZZCASH);
        $this->assertEquals(13, WalletPaymentRequestDTO::BANK_CODE_EASYPAISA);
        $this->assertEquals(14, WalletPaymentRequestDTO::BANK_CODE_UPAISA);
        $this->assertEquals(4, WalletPaymentRequestDTO::ACCOUNT_TYPE_WALLET);
    }
}
