# Release v1.0.6 - JazzCash Wallet Support

## Summary

Adds first-class JazzCash wallet checkout alongside EasyPaisa and UPaisa, aligned with PayFast’s working `customer/validate` flow (`bank_code=1`, `account_type_id=4`, Bearer auth).

## What’s New

- `PayFast::payWithJazzCash(array $data)`
- `WalletPaymentRequestDTO` with bank-code constants:
  - JazzCash = `1`
  - EasyPaisa = `13`
  - UPaisa = `14`
  - Wallet account type = `4`
- Wallet validate responses persist `payment_method = jazzcash`
- Bearer token is sent on wallet `customer/validate` calls

## Usage

```php
$response = PayFast::payWithJazzCash([
    'basket_id' => 'ORD-12345',
    'txnamt' => 1000.00,
    'customer_mobile_no' => '03001234567',
    'customer_email_address' => 'customer@example.com',
]);

$result = json_decode($response->getContent(), true);

if ($result['status']) {
    // $result['data']['transaction_id']
    // $result['data']['payment_id']
    // $result['data']['payment_method'] === 'jazzcash'
    // Continue with OTP / PayFast::walletTransactionInitiate(...)
}
```

## Required Fields

- `basket_id` (or `orderNumber` / `orderNo`)
- `txnamt` (or `transactionAmount`) > 0
- `customer_mobile_no` (or `customerMobileNo`)

## Tests

- `WalletPaymentRequestDTOTest`
- `PayFastTest` JazzCash success / validation / API failure
- `PaymentServiceTest` Bearer header for JazzCash validate
- `PaymentFlowTest::testJazzCashWalletPaymentFlow`

## Upgrade Notes

- EasyPaisa / UPaisa helpers now reject incomplete payloads with `VALIDATION_ERROR` (missing basket/amount/mobile).
- No database migration required (`METHOD_JAZZCASH` already existed).

**Full Changelog**: Compare v1.0.5...v1.0.6
