### v1.0.10

- Feature: Log-first IPN middleware (`payfast.ipn.log`) persists every webhook before verify/validate.
- Feature: Stronger idempotency — duplicate deliveries stored as `duplicate`, payments/events not re-applied.
- Feature: Terminal-state payment guards (completed payments never regress).
- Feature: `PayfastLogger` never-fail channel bootstrap for request logging.
- Migration: `idempotency_key`, `payload_hash`, `processing_status`, `duplicate_of`, `request_headers`.
- Tests: IPNService idempotency + LogIncomingIpn coverage.

### v1.0.9

- Publish: Fixed migration publish destinations (now include filenames under `database/migrations`).
- Publish: Umbrella tag `payfast` (config + migrations + emails + logging stub + env example).
- Publish: New tags `payfast-logging`, `payfast-env`; expanded publishable `config/payfast.php`.
- Config: Wallet bank codes, route middleware/prefix, `load_migrations` / `routes.enabled` toggles.
- Docs/README: Updated `vendor:publish` instructions for consumer apps.

### v1.0.8

- Security: Added `VerifyPayfastIpn` middleware (HMAC-SHA256 secret + optional IP/CIDR allowlist).
- Feature: IPN route uses `api` middleware (configurable) and aliases `payfast.ipn`.
- Feature: Auto-load package migrations via `loadMigrationsFrom`.
- Fix: `LogPaymentActivity` now persists rows through `ActivityLogRepository`.
- Refactor: `CABPayments` uses `ProcessPaymentRepository` (`findForStatusCheck` / mark helpers).
- Config: New `payfast.ipn.*` keys (`PAYFAST_IPN_SECRET`, `PAYFAST_IPN_ALLOWED_IPS`, …).
- Tests: Middleware, repository status-check, updated listener/console coverage.

### v1.0.7

- Fix: Event listeners now register for HTTP requests (no longer console-only).
- Feature: Added Laravel Form Requests — `CardPaymentRequest`, `WalletPaymentRequest`, `IPNRequest` (with programmatic `validateArray()`).
- Refactor: Wallet validate/initiate now accept `WalletPaymentRequestDTO` end-to-end in `PaymentService`.
- Fix: `StorePaymentRecord` skips duplicate creates when PayFast already persisted the payment.
- Chore: Registered `payfast:check-pending-payments` console command in the service provider.
- Tests: Form Request, StorePaymentRecord, and updated wallet/PaymentService coverage.

### v1.0.6

- Feature: Added `PayFast::payWithJazzCash()` (bank_code `1`, account_type_id `4`) with `METHOD_JAZZCASH` persistence.
- Feature: Added `WalletPaymentRequestDTO` for EasyPaisa / UPaisa / JazzCash wallet payloads.
- Fix: Wallet `customer/validate` requests now send the Bearer auth token (required by PayFast for JazzCash).
- Tests: Unit + feature coverage for JazzCash success, validation failure, API failure, and Bearer header.
- Docs: Updated API Reference, Payment Flows, Getting Started, and wiki mirrors.

### v1.0.5

- CI/CD: Added GitHub Actions workflow for testing across multiple PHP and Laravel versions.
- Refactor: Enhanced ProcessPayment factory autoloading, error handling, and compatibility.
- Refactor: Improved TestCase setup.
- Chore: Adjusted PHPUnit bootstrap path in `phpunit.xml`.
- Documentation: Enhanced documentation and updated README badges.

### v1.0.0

- Updated Namespace for Service Provider
- Updated namespace for Facades
- Hosted Checkout
- Wallet Checkout
- Direct Checkout
