<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Console;

use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use zfhassaan\Payfast\Models\ProcessPayment;
use zfhassaan\Payfast\Repositories\Contracts\ActivityLogRepositoryInterface;
use zfhassaan\Payfast\Services\ConfigService;
use zfhassaan\Payfast\Services\Contracts\EmailNotificationServiceInterface;
use zfhassaan\Payfast\Services\Contracts\HttpClientInterface;

class CABPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payfast:check-pending-payments 
                            {--status= : Filter by payment status (pending, validated, otp_verified, completed, failed)}
                            {--limit=50 : Limit the number of records to process}
                            {--no-email : Skip sending email notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for pending payments from ProcessPayments model, verifies transaction status with PayFast, updates payment status, logs activity, and sends email notifications.';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ConfigService $configService,
        private readonly ActivityLogRepositoryInterface $activityLogRepository,
        private readonly EmailNotificationServiceInterface $emailNotificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $status = $this->option('status');
        $limit = (int) $this->option('limit');
        $skipEmail = $this->option('no-email');

        $query = ProcessPayment::query();

        if ($status) {
            $query->where('status', $status);
        } else {
            // Default to pending and validated payments
            $query->whereIn('status', [
                ProcessPayment::STATUS_PENDING,
                ProcessPayment::STATUS_VALIDATED,
                ProcessPayment::STATUS_OTP_VERIFIED,
            ]);
        }

        $payments = $query->limit($limit)->get();

        if ($payments->isEmpty()) {
            $this->info('No pending payments found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$payments->count()} payment(s)...");

        $processed = 0;
        $failed = 0;
        $completed = 0;

        foreach ($payments as $payment) {
            $this->line("Checking payment: {$payment->orderNo} (Status: {$payment->status})");

            try {
                $response = $this->voidTransactionCheck($payment->orderNo);
                $responseData = json_decode($response->getContent(), true);

                if (isset($responseData['code']) && $responseData['code'] === '00') {
                    // Payment is completed
                    $oldStatus = $payment->status;
                    $payment->markAsCompleted();

                    // Log activity
                    $this->logActivity($payment, ProcessPayment::STATUS_COMPLETED, $responseData);

                    // Send email notifications if not skipped
                    if (!$skipEmail) {
                        $this->sendCompletionNotifications($payment, $responseData);
                    }

                    $this->info("✓ Payment {$payment->orderNo} completed successfully");
                    $completed++;
                    $processed++;
                } else {
                    // Payment verification failed
                    $errorMessage = $responseData['message'] ?? 'Verification failed';
                    $payment->markAsFailed($errorMessage);

                    // Log activity
                    $this->logActivity($payment, ProcessPayment::STATUS_FAILED, $responseData);

                    // Send failure email if not skipped
                    if (!$skipEmail) {
                        $this->emailNotificationService->sendPaymentFailureEmail($payment, $errorMessage);
                    }

                    $this->warn("✗ Payment {$payment->orderNo} verification failed: {$errorMessage}");
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("✗ Error processing payment {$payment->orderNo}: {$e->getMessage()}");
                $payment->markAsFailed($e->getMessage());

                // Log activity
                $this->logActivity($payment, ProcessPayment::STATUS_FAILED, ['error' => $e->getMessage()]);

                $failed++;
            }
        }

        $this->newLine();
        $this->info("Processing complete!");
        $this->info("Completed: {$completed}");
        $this->info("Processed: {$processed}");
        $this->warn("Failed: {$failed}");

        return self::SUCCESS;
    }

    /**
     * Check transaction status by basket ID.
     *
     * @param string $basketId
     * @return JsonResponse
     */
    private function voidTransactionCheck(string $basketId): JsonResponse
    {
        $username = $this->configService->getMerchantId();
        $password = $this->configService->getSecuredKey();
        $checkUrl = config('payfast.transaction_check', '');

        if (empty($checkUrl)) {
            throw new \RuntimeException('Transaction check URL is not configured');
        }

        $url = $checkUrl . 'transaction/view/basket/id?basket_id=' . $basketId;

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        // Use basic auth
        $response = $this->httpClient->get($url, array_merge($headers, [
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
        ]));

        return response()->json($response ?? []);
    }

    /**
     * Log payment activity.
     *
     * @param ProcessPayment $payment
     * @param string $status
     * @param array<string, mixed> $responseData
     * @return void
     */
    private function logActivity(ProcessPayment $payment, string $status, array $responseData): void
    {
        $requestData = json_decode($payment->requestData ?? '{}', true);
        $userId = $requestData['user_id'] ?? null;

        $this->activityLogRepository->create([
            'user_id' => $userId ?? 0,
            'transaction_id' => $payment->transaction_id ?? '',
            'order_no' => $payment->orderNo ?? '',
            'status' => $status,
            'amount' => $requestData['transactionAmount'] ?? $requestData['txnamt'] ?? 0,
            'details' => json_encode($responseData),
            'metadata' => [
                'payment_method' => $payment->payment_method,
                'payment_id' => $payment->id,
            ],
            'transaction_date' => now(),
        ]);
    }

    /**
     * Send completion notifications.
     *
     * @param ProcessPayment $payment
     * @param array<string, mixed> $transactionData
     * @return void
     */
    private function sendCompletionNotifications(ProcessPayment $payment, array $transactionData): void
    {
        try {
            // Send to customer
            $this->emailNotificationService->sendPaymentCompletionEmail($payment, $transactionData);

            // Send to admins
            $this->emailNotificationService->sendAdminNotificationEmail($payment, $transactionData);
        } catch (\Exception $e) {
            $this->warn("Failed to send email notifications: {$e->getMessage()}");
        }
    }
}
