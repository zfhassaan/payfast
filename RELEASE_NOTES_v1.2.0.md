# Release v1.1.1

Public release after **v1.1.0**: hardened IPN, broader Laravel/PHP support, and updated docs.

## Highlights

1. **Log-first IPN** — `payfast.ipn.log` persists every webhook before signature/IP/FormRequest checks.
2. **Idempotency** — duplicate deliveries return `ALREADY_PROCESSED`, store `processing_status=duplicate`, and do not re-fire payment events.
3. **Terminal payment guards** — completed payments are not overwritten by later failed IPNs.
4. **Laravel 10–13 / PHP 8.1–8.5** — composer constraints + CI matrix (Testbench 8–11).
5. **Docs** — website + wiki synced for IPN pipeline, publish tags, wallets (JazzCash), and security.

## Upgrade

```bash
composer update zfhassaan/payfast
php artisan vendor:publish --tag=payfast
php artisan migrate
```

```env
PAYFAST_CHECKOUT_URL=https://yourdomain.com/payfast/ipn
PAYFAST_IPN_SECRET=
PAYFAST_IPN_ALLOWED_IPS=
PAYFAST_IDEMPOTENCY_ENABLED=true
PAYFAST_LOG_HEADERS=true
```

## Middleware

```
api → payfast.ipn.log → payfast.ipn → IPNController
```

**Full Changelog**: https://github.com/zfhassaan/payfast/compare/v1.1.0...v1.1.1
