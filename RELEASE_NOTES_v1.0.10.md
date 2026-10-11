# Release v1.0.10 - Idempotent IPN + log-every-request

## What you get

1. **No missed webhooks** — `LogIncomingIpn` runs first and writes a DB row + file log before signature/IP/FormRequest can reject the request.
2. **Idempotency** — same transaction/status/amount delivery returns `ALREADY_PROCESSED`, marks the new row `duplicate`, and does not re-fire payment events.
3. **Safe payment updates** — completed payments are not overwritten by later failed IPNs.

## Middleware order

```
api → payfast.ipn.log → payfast.ipn → IPNController
```

## Migrate

```bash
php artisan migrate
# or republish:
php artisan vendor:publish --tag=payfast-migrations --force
```

## Config

```env
PAYFAST_IDEMPOTENCY_ENABLED=true
PAYFAST_LOG_HEADERS=true
```

**Full Changelog**: Compare v1.0.9...v1.0.10
