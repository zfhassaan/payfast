# Release v1.0.8 - IPN Security, Repositories, Migrations

## Summary

Hardens the IPN webhook, routes payment reconciliation through the repository layer, persists activity logs from event listeners, and auto-loads package migrations.

## Configure IPN security (optional)

```env
PAYFAST_IPN_SECRET=your-shared-secret
PAYFAST_IPN_ALLOWED_IPS=203.0.113.10,198.51.100.0/24
PAYFAST_IPN_SIGNATURE_HEADER=X-PayFast-Signature
```

Signature = `hash_hmac('sha256', raw_body, PAYFAST_IPN_SECRET)` sent in the header (or body `signature`).

Leave secret and allowed IPs empty for open local/sandbox behaviour.

## Other changes

- Middleware alias: `payfast.ipn`
- Route middleware: `api` + `VerifyPayfastIpn` (disable api group via `PAYFAST_IPN_USE_API_MIDDLEWARE=false`)
- `php artisan migrate` picks up package migrations without publishing
- `payfast:check-pending-payments` uses `ProcessPaymentRepository`

**Full Changelog**: Compare v1.0.7...v1.0.8
