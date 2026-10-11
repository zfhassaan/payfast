# Release v1.0.7 - Form Requests, Listener Fix, DTO Hardening

## Summary

Closes architecture gaps from the SOLID / Laravel patterns review: HTTP listeners now fire, Form Requests are first-class, wallet DTOs flow through `PaymentService`, and duplicate payment inserts from `StorePaymentRecord` are prevented.

## Changes

- **Fix:** `registerEventListeners()` runs for web + console (was nested under `runningInConsole()`).
- **Feature:** `CardPaymentRequest`, `WalletPaymentRequest`, `IPNRequest` with `validateArray()` for facade usage.
- **Refactor:** `PaymentService::validateWalletTransaction` / `initiateWalletTransaction` accept `WalletPaymentRequestDTO`.
- **Fix:** `StorePaymentRecord` no-ops when a payment already exists for the transaction/basket id.
- **Chore:** Registers `payfast:check-pending-payments` via the service provider.

## Upgrade Notes

- Wallet service mocks must pass a `WalletPaymentRequestDTO` (not a raw array + bank_code ints).
- Invalid wallet payloads now return Laravel validation error bags under `data` with `code = VALIDATION_ERROR`.
- No database migrations required.

**Full Changelog**: Compare v1.0.6...v1.0.7
