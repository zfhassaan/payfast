# IPN (Instant Payment Notification) Handling

The IPN service handles webhook notifications from PayFast to update payment statuses automatically.

## Overview

IPN (Instant Payment Notification) is a webhook system that PayFast uses to notify your application about payment status changes. As of **v1.0.10**, the package provides:

- **Built-in IPN route** — `POST /payfast/ipn` (optional prefix via config), named `payfast.ipn.handle`
- **Log-first capture** — every request is persisted **before** signature/IP/FormRequest checks
- **Idempotency** — duplicate deliveries return `ALREADY_PROCESSED` without re-firing events
- **HMAC + IP allowlist** — optional via `VerifyPayfastIpn` middleware
- **Automatic `checkout_url` injection** — outgoing API requests include your IPN URL
- **Payment status updates** — with terminal-state guards (completed never regresses)
- **Events** — `PaymentCompleted` / `PaymentFailed`

## Middleware pipeline

```
api → payfast.ipn.log → payfast.ipn → IPNController → IPNService
```

| Alias | Class | Role |
|-------|--------|------|
| `payfast.ipn.log` | `LogIncomingIpn` | File log + DB row (`processing_status=received`) **first** |
| `payfast.ipn` | `VerifyPayfastIpn` | Optional HMAC / IP allowlist; marks log `rejected` on failure |

Customize order/prefix in published `config/payfast.php` → `routes.middleware` / `routes.prefix`.

## How It Works

> 1. **Your App** → PayFast API *(payment request includes `checkout_url`)*
> 2. **PayFast** processes the payment
> 3. **PayFast** → `POST …/payfast/ipn`
> 4. **LogIncomingIpn** stores the raw attempt (never skipped)
> 5. **VerifyPayfastIpn** optionally checks IP + HMAC
> 6. **IPNRequest** validates payload shape
> 7. **IPNService** applies idempotency, updates payment, dispatches events

### Processing statuses (`payfast_ipn_table.processing_status`)

| Status | Meaning |
|--------|---------|
| `received` | Inbound row created (log-first) |
| `processed` | Business logic applied successfully |
| `duplicate` | Same idempotency key already processed |
| `rejected` | Failed IP/signature/validation |
| `failed` | Exception during processing |

## Setup

### Step 1: Publish & migrate

```bash
php artisan vendor:publish --tag=payfast
php artisan migrate
```

### Step 2: Configure checkout URL

```env
# Recommended in production
PAYFAST_CHECKOUT_URL=https://yourdomain.com/payfast/ipn

# Or leave empty to auto-resolve route('payfast.ipn.handle')
```

### Step 3: Optional security (production)

```env
PAYFAST_IPN_SECRET=your-shared-hmac-secret
PAYFAST_IPN_ALLOWED_IPS=203.0.113.10,198.51.100.0/24
PAYFAST_IPN_SIGNATURE_HEADER=X-PayFast-Signature
PAYFAST_IDEMPOTENCY_ENABLED=true
PAYFAST_LOG_HEADERS=true
```

**HMAC:** `hash_hmac('sha256', raw_body, PAYFAST_IPN_SECRET)` in header (or body key `signature`).

Leave secret and allowed IPs empty for open local/sandbox behaviour.

### Step 4: That's it

The package automatically:

- Registers the IPN route (disable with `PAYFAST_ROUTES_ENABLED=false`)
- Uses the `api` middleware group when `PAYFAST_IPN_USE_API_MIDDLEWARE=true` (CSRF-free)
- Injects `checkout_url` into outgoing PayFast API calls
- Logs every delivery attempt and processes idempotently

## Idempotency

Key material (SHA-256):

`transaction_id | order_no | status | amount`

| Scenario | Result |
|----------|--------|
| First delivery | `code: "00"`, `processing_status=processed`, payment may update, events may fire |
| Same delivery again | `code: "ALREADY_PROCESSED"`, new row with `processing_status=duplicate`, **no** re-event |
| Payment already `completed` | Later failed IPN is ignored (terminal-state guard) |

Disable with `PAYFAST_IDEMPOTENCY_ENABLED=false` (not recommended in production).

## IPN Data Structure

Accepted field aliases:

- `transaction_id` / `TRANSACTION_ID`
- `order_no` / `ORDER_NO` / `basket_id` / `BASKET_ID`
- `status` / `STATUS` / `code` / `CODE`
- `amount` / `AMOUNT` / `txnamt` / `TXNAMT`
- `currency` / `CURRENCY` / `currency_code` / `CURRENCY_CODE`

### Example payload

```php
[
    'transaction_id' => 'TXN123456',
    'order_no' => 'ORD-12345',
    'status' => '00',
    'amount' => '1000.00',
    'currency' => 'PKR',
]
```

## Status Mapping

| IPN Status | Payment Status |
|---|---|
| `00`, `completed`, `success` | `completed` |
| `failed`, `failure` | `failed` |
| `cancelled`, `cancel` | `cancelled` |

## Response Format

### Success

```json
{
  "status": true,
  "data": {
    "ipn_log_id": 123,
    "transaction_id": "TXN123456",
    "order_no": "ORD-12345",
    "payment_updated": true,
    "idempotency_key": "…"
  },
  "code": "00"
}
```

### Duplicate (still HTTP success for the gateway)

```json
{
  "status": true,
  "message": "IPN already processed",
  "code": "ALREADY_PROCESSED",
  "data": {
    "ipn_log_id": 124,
    "original_ipn_log_id": 123,
    "payment_updated": false
  }
}
```

### Rejected / invalid

```json
{
  "status": false,
  "message": "Invalid IPN data",
  "code": "INVALID_IPN"
}
```

Other codes: `IPN_IP_FORBIDDEN`, `IPN_SIGNATURE_INVALID`, `IPN_ERROR`, `VALIDATION_ERROR`.

## Custom IPN Handling (Optional)

### Option 1: Listen to Events (recommended)

```php
use zfhassaan\Payfast\Events\PaymentCompleted;
use zfhassaan\Payfast\Events\PaymentFailed;

Event::listen(PaymentCompleted::class, function ($event) {
    // Update order, fulfill, etc.
});

Event::listen(PaymentFailed::class, function ($event) {
    // Handle failure
});
```

### Option 2: Custom route

Point `PAYFAST_CHECKOUT_URL` at your own endpoint and still call:

```php
return PayFast::handleIPN($request->all());
```

Prefer keeping package middleware (`payfast.ipn.log` then `payfast.ipn`) so log-first behaviour is preserved.

### Option 3: Extend `IPNService`

Bind a subclass to `IPNServiceInterface` in your app service provider.

## Testing the endpoint

```bash
curl -X POST https://yourdomain.com/payfast/ipn \
  -H "Content-Type: application/json" \
  -H "X-PayFast-Signature: $(php -r "echo hash_hmac('sha256', file_get_contents('php://stdin'), getenv('PAYFAST_IPN_SECRET'));" <<< '{"transaction_id":"TXN1","status":"00","amount":100}')" \
  -d '{"transaction_id":"TXN1","basket_id":"ORD1","status":"00","amount":100,"currency":"PKR"}'
```

### Inspect logs

```php
use zfhassaan\Payfast\Models\IPNLog;

IPNLog::where('processing_status', IPNLog::STATUS_DUPLICATE)->latest()->get();
IPNLog::where('transaction_id', 'TXN123456')->get();
```

Also check `storage/logs/payfast/*.log` (or your configured channel).

## Troubleshooting

| Issue | Check |
|-------|--------|
| IPN not received | `PAYFAST_CHECKOUT_URL` / `APP_URL`, firewall, HTTPS |
| Always `IPN_SIGNATURE_INVALID` | Raw body must match HMAC input; header name; secret |
| Duplicates updating twice | Ensure `PAYFAST_IDEMPOTENCY_ENABLED=true` and migrations ran |
| Missing rows for bad requests | Confirm `payfast.ipn.log` is first in `routes.middleware` |

## Best practices

1. Use HTTPS in production  
2. Set `PAYFAST_IPN_SECRET` and/or `PAYFAST_IPN_ALLOWED_IPS`  
3. Keep idempotency enabled  
4. Prefer events over forking the controller  
5. Monitor `processing_status` and the `payfast` log channel  

See also: [Configuration Guide](Configuration-Guide), [Security Best Practices](Security-Best-Practices), [Models and Database](Models-and-Database).
