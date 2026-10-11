# IPN usage (quick reference)

Canonical docs: **[docs/content/IPN-Handling.md](docs/content/IPN-Handling.md)** (and the GitHub wiki mirror).

## Built-in endpoint

```
POST /payfast/ipn   →  route name: payfast.ipn.handle
```

Middleware: `api` → `payfast.ipn.log` → `payfast.ipn`

## Production env

```env
PAYFAST_CHECKOUT_URL=https://yourdomain.com/payfast/ipn
PAYFAST_IPN_SECRET=your-shared-hmac-secret
PAYFAST_IPN_ALLOWED_IPS=
PAYFAST_IDEMPOTENCY_ENABLED=true
PAYFAST_LOG_HEADERS=true
```

## What you get (v1.0.10+)

1. **Log-first** — every request is stored before signature/IP/FormRequest can reject it  
2. **Idempotency** — duplicates return `ALREADY_PROCESSED` without re-firing events  
3. **Terminal payments** — `completed` is not overwritten by a later failed IPN  

Prefer listening to `PaymentCompleted` / `PaymentFailed` instead of forking the controller. See the full guide for schemas, response codes, and troubleshooting.
