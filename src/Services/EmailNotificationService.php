<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Services;

use Illuminate\Support\Facades\Mail;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Services\Contracts\EmailNotificationServiceInterface;

class EmailNotificationService implements EmailNotificationServiceInterface
{
    /**
     * Send payment status notification email.
     *
     * @param ProcessPayment $payment
     * @param string $status
     * @param array<string, mixed> $data
     * @return void
     */
    public function sendPaymentStatusNotification(ProcessPayment $payment, string $status, array $data = []): void
    {
        $requestData = json_decode($payment->requestData ?? '{}', true);
        $customerEmail = $requestData['customer_email'] ?? $requestData['customer_email_address'] ?? null;

        if (!$customerEmail) {
            return;
        }

        $template = config('payfast.email_templates.status_notification', 'payfast::emails.status-notification');
        $subject = $this->getStatusSubject($status);

        Mail::send($template, [
            'payment' => $payment,
            'status' => $status,
            'data' => $data,
        ], function ($message) use ($customerEmail, $subject) {
            $message->to($customerEmail)
                ->subject($subject);
        });
    }

    /**
     * Send payment completion email to customer.
     *
     * @param ProcessPayment $payment
     * @param array<string, mixed> $transactionData
     * @return void
     */
    public function sendPaymentCompletionEmail(ProcessPayment $payment, array $transactionData = []): void
    {
        $requestData = json_decode($payment->requestData ?? '{}', true);
        $customerEmail = $requestData['customer_email'] ?? $requestData['customer_email_address'] ?? null;

        if (!$customerEmail) {
            return;
        }

        $template = config('payfast.email_templates.payment_completion', 'payfast::emails.payment-completion');
        $subject = config('payfast.email_subjects.payment_completion', 'Payment Completed Successfully');

        Mail::send($template, [
            'payment' => $payment,
            'transactionData' => $transactionData,
        ], function ($message) use ($customerEmail, $subject) {
            $message->to($customerEmail)
                ->subject($subject);
        });
    }

    /**
     * Send payment completion email to admins.
     *
     * @param ProcessPayment $payment
     * @param array<string, mixed> $transactionData
     * @return void
     */
    public function sendAdminNotificationEmail(ProcessPayment $payment, array $transactionData = []): void
    {
        $adminEmails = $this->getAdminEmails();

        if (empty($adminEmails)) {
            return;
        }

        $template = config('payfast.email_templates.admin_notification', 'payfast::emails.admin-notification');
        $subject = config('payfast.email_subjects.admin_notification', 'New Payment Completed');

        Mail::send($template, [
            'payment' => $payment,
            'transactionData' => $transactionData,
        ], function ($message) use ($adminEmails, $subject) {
            $message->to($adminEmails)
                ->subject($subject);
        });
    }

    /**
     * Send payment failure email.
     *
     * @param ProcessPayment $payment
     * @param string $reason
     * @return void
     */
    public function sendPaymentFailureEmail(ProcessPayment $payment, string $reason): void
    {
        $requestData = json_decode($payment->requestData ?? '{}', true);
        $customerEmail = $requestData['customer_email'] ?? $requestData['customer_email_address'] ?? null;

        if (!$customerEmail) {
            return;
        }

        $template = config('payfast.email_templates.payment_failure', 'payfast::emails.payment-failure');
        $subject = config('payfast.email_subjects.payment_failure', 'Payment Failed');

        Mail::send($template, [
            'payment' => $payment,
            'reason' => $reason,
        ], function ($message) use ($customerEmail, $subject) {
            $message->to($customerEmail)
                ->subject($subject);
        });
    }

    /**
     * Get admin emails from configuration.
     *
     * @return array<string>
     */
    private function getAdminEmails(): array
    {
        $emails = config('payfast.admin_emails', env('PAYFAST_ADMIN_EMAILS', ''));

        if (is_string($emails)) {
            return array_filter(array_map('trim', explode(',', $emails)));
        }

        return is_array($emails) ? $emails : [];
    }

    /**
     * Get email subject for status.
     *
     * @param string $status
     * @return string
     */
    private function getStatusSubject(string $status): string
    {
        $subjects = [
            ProcessPayment::STATUS_VALIDATED => 'Payment Validated',
            ProcessPayment::STATUS_OTP_VERIFIED => 'OTP Verified',
            ProcessPayment::STATUS_COMPLETED => 'Payment Completed',
            ProcessPayment::STATUS_FAILED => 'Payment Failed',
        ];

        return $subjects[$status] ?? 'Payment Status Update';
    }
}

