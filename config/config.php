<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayFast API Endpoints
    |--------------------------------------------------------------------------
    */
    'api_url' => env('PAYFAST_API_URL', ''),
    'sandbox_api_url' => env('PAYFAST_SANDBOX_URL', ''),
    'transaction_check' => env('PAYFAST_VERIFY_TRANSACTION', ''),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'grant_type' => env('PAYFAST_GRANT_TYPE', 'client_credentials'),
    'merchant_id' => env('PAYFAST_MERCHANT_ID', ''),
    'secured_key' => env('PAYFAST_SECURED_KEY', ''),
    'store_id' => env('PAYFAST_STORE_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Application URLs & Mode
    |--------------------------------------------------------------------------
    |
    | mode: "sandbox" uses sandbox_api_url, "production" uses api_url.
    | checkout_url: IPN / backend callback. Empty = auto route('payfast.ipn.handle').
    |
    */
    'return_url' => env('PAYFAST_RETURN_URL', ''),
    'checkout_url' => env('PAYFAST_CHECKOUT_URL', ''),
    'mode' => env('PAYFAST_MODE', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Package Boot Options
    |--------------------------------------------------------------------------
    |
    | load_migrations: When true, package migrations run via `php artisan migrate`
    | without publishing. Set false if you publish migrations into your app.
    |
    | routes.enabled: Register the built-in POST payfast/ipn webhook route.
    |
    */
    'load_migrations' => env('PAYFAST_LOAD_MIGRATIONS', true),

    'routes' => [
        'enabled' => env('PAYFAST_ROUTES_ENABLED', true),
        'prefix' => env('PAYFAST_ROUTE_PREFIX', ''),
        // Order matters: log every request FIRST, then verify signature/IP.
        'middleware' => array_values(array_filter([
            env('PAYFAST_IPN_USE_API_MIDDLEWARE', true) ? 'api' : null,
            'payfast.ipn.log',
            'payfast.ipn',
        ])),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    |
    | Duplicate IPN deliveries (same transaction + status + amount) return
    | ALREADY_PROCESSED without re-updating payments or re-firing events.
    | Every delivery attempt is still stored (processing_status=duplicate).
    |
    */
    'idempotency' => [
        'enabled' => env('PAYFAST_IDEMPOTENCY_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request logging
    |--------------------------------------------------------------------------
    */
    'request_logging' => [
        'log_headers' => env('PAYFAST_LOG_HEADERS', true),
        'redact_headers' => ['authorization', 'cookie', 'x-payfast-signature'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile Wallets (bank_code mapping)
    |--------------------------------------------------------------------------
    |
    | Override bank codes if your PayFast merchant account uses different IDs.
    |
    */
    'wallets' => [
        'jazzcash' => [
            'bank_code' => (int) env('PAYFAST_WALLET_JAZZCASH_BANK_CODE', 1),
            'account_type_id' => (int) env('PAYFAST_WALLET_ACCOUNT_TYPE_ID', 4),
        ],
        'easypaisa' => [
            'bank_code' => (int) env('PAYFAST_WALLET_EASYPAISA_BANK_CODE', 13),
            'account_type_id' => (int) env('PAYFAST_WALLET_ACCOUNT_TYPE_ID', 4),
        ],
        'upaisa' => [
            'bank_code' => (int) env('PAYFAST_WALLET_UPAISA_BANK_CODE', 14),
            'account_type_id' => (int) env('PAYFAST_WALLET_ACCOUNT_TYPE_ID', 4),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */
    'admin_emails' => env('PAYFAST_ADMIN_EMAILS', ''),

    'email_templates' => [
        'status_notification' => 'payfast::emails.status-notification',
        'payment_completion' => 'payfast::emails.payment-completion',
        'admin_notification' => 'payfast::emails.admin-notification',
        'payment_failure' => 'payfast::emails.payment-failure',
    ],

    'email_subjects' => [
        'payment_completion' => env('PAYFAST_EMAIL_SUBJECT_COMPLETION', 'Payment Completed Successfully'),
        'admin_notification' => env('PAYFAST_EMAIL_SUBJECT_ADMIN', 'New Payment Completed'),
        'payment_failure' => env('PAYFAST_EMAIL_SUBJECT_FAILURE', 'Payment Failed'),
    ],

    /*
    |--------------------------------------------------------------------------
    | IPN Security
    |--------------------------------------------------------------------------
    |
    | Optional webhook hardening for POST /payfast/ipn.
    | - When ipn.secret is set, requests must present a valid HMAC-SHA256
    |   signature (header or body field).
    | - When ipn.allowed_ips is non-empty, the client IP must be listed.
    | - Leave both empty for open sandbox / local behaviour.
    |
    */
    'ipn' => [
        'secret' => env('PAYFAST_IPN_SECRET', ''),
        'signature_header' => env('PAYFAST_IPN_SIGNATURE_HEADER', 'X-PayFast-Signature'),
        'signature_body_key' => env('PAYFAST_IPN_SIGNATURE_BODY_KEY', 'signature'),
        'allowed_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PAYFAST_IPN_ALLOWED_IPS', ''))
        ))),
        'use_api_middleware' => env('PAYFAST_IPN_USE_API_MIDDLEWARE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | After publishing, you can also add a dedicated "payfast" channel in
    | config/logging.php (see published stub / docs).
    |
    */
    'logging' => [
        'channel' => env('PAYFAST_LOG_CHANNEL', 'payfast'),
        'level' => env('PAYFAST_LOG_LEVEL', 'debug'),
        'days' => (int) env('PAYFAST_LOG_DAYS', 14),
    ],
];
