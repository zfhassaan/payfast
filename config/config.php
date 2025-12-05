<?php

return [
    'api_url' => env('PAYFAST_API_URL', ''),
    'sandbox_api_url' => env('PAYFAST_SANDBOX_URL', ''),
    'grant_type' => env('PAYFAST_GRANT_TYPE', ''),
    'merchant_id' => env('PAYFAST_MERCHANT_ID', ''),
    'secured_key' => env('PAYFAST_SECURED_KEY', ''),
    'store_id' => env('PAYFAST_STORE_ID', ''),
    'return_url' => env('PAYFAST_RETURN_URL', ''),
    'mode' => env('PAYFAST_MODE', 'sandbox'),
    'transaction_check' => env('PAYFAST_VERIFY_TRANSACTION', ''),

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    |
    | Configure email notifications for payment status updates.
    |
    */
    'admin_emails' => env('PAYFAST_ADMIN_EMAILS', ''), // Comma-separated list

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
];
