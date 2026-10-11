<?php

declare(strict_types=1);

namespace zfhassaan\Payfast\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Never-fail logger for payment / IPN paths.
 * Ensures a daily channel exists so request logging is never skipped by misconfig.
 */
final class PayfastLogger
{
    public static function channel(): string
    {
        return (string) config('payfast.logging.channel', 'payfast');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        try {
            $channel = self::channel();

            if (!config("logging.channels.{$channel}")) {
                config([
                    "logging.channels.{$channel}" => [
                        'driver' => 'daily',
                        'path' => storage_path("logs/{$channel}/{$channel}.log"),
                        'level' => config('payfast.logging.level', 'debug'),
                        'days' => (int) config('payfast.logging.days', 14),
                    ],
                ]);
            }

            Log::channel($channel)->{$level}($message, $context);
        } catch (Throwable $e) {
            try {
                Log::{$level}('[payfast-fallback] ' . $message, $context + ['logger_error' => $e->getMessage()]);
            } catch (Throwable) {
                // Intentionally swallow — logging must never break payment flow.
            }
        }
    }
}
