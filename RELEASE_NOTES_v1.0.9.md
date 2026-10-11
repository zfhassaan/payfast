# Release v1.0.9 - Publishable configuration for consumers

## Summary

Improves `php artisan vendor:publish` so apps can own and customize PayFast settings, migrations, emails, logging, and env samples.

## Quick start for consumers

```bash
composer require zfhassaan/payfast
php artisan vendor:publish --tag=payfast
# copy keys from .env.payfast.example into .env
php artisan migrate
```

## Publish tags

| Tag | Purpose |
|-----|---------|
| `payfast` | Config + migrations + email templates + logging stub + env example |
| `payfast-config` | `config/payfast.php` |
| `payfast-migrations` | App-owned migration copies |
| `payfast-email-templates` | Blade email views |
| `payfast-logging` | `config/payfast-logging-channel.php` |
| `payfast-env` | `.env.payfast.example` |
| `payfast-command` | Console command stub |
| `payfast-tests` | Test suite copies |

## Configure after publish

Edit `config/payfast.php` for:

- API / sandbox / merchant credentials
- Wallet bank codes (`wallets.jazzcash|easypaisa|upaisa`)
- IPN secret + allowed IPs
- Route enable/prefix/middleware
- Whether package migrations auto-load (`load_migrations`)

If you publish migrations into the app, set:

```env
PAYFAST_LOAD_MIGRATIONS=false
```

**Full Changelog**: Compare v1.0.8...v1.0.9
