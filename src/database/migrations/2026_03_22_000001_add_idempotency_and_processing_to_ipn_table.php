<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency + processing status for log-every-request IPN handling.
 *
 * Note: transaction_id unique was already dropped in
 * 2025_05_06_000001_add_raw_payload_and_ip_to_ipn_table.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('payfast_ipn_table', function (Blueprint $table) {
            if (!Schema::hasColumn('payfast_ipn_table', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->after('uid');
            }
            if (!Schema::hasColumn('payfast_ipn_table', 'payload_hash')) {
                $table->string('payload_hash', 64)->nullable()->after('idempotency_key');
            }
            if (!Schema::hasColumn('payfast_ipn_table', 'processing_status')) {
                $table->string('processing_status', 32)->default('received')->after('status');
            }
            if (!Schema::hasColumn('payfast_ipn_table', 'duplicate_of')) {
                $table->unsignedBigInteger('duplicate_of')->nullable()->after('processing_status');
            }
            if (!Schema::hasColumn('payfast_ipn_table', 'request_headers')) {
                $table->json('request_headers')->nullable()->after('raw_payload');
            }
        });

        $this->addIndexSafely('payfast_ipn_table_processing_status_index', function (Blueprint $table) {
            $table->index('processing_status', 'payfast_ipn_table_processing_status_index');
        });
        $this->addIndexSafely('payfast_ipn_table_idempotency_key_unique', function (Blueprint $table) {
            $table->unique('idempotency_key', 'payfast_ipn_table_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        try {
            Schema::table('payfast_ipn_table', function (Blueprint $table) {
                $table->dropUnique('payfast_ipn_table_idempotency_key_unique');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('payfast_ipn_table', function (Blueprint $table) {
                $table->dropIndex('payfast_ipn_table_processing_status_index');
            });
        } catch (\Throwable) {
        }

        Schema::table('payfast_ipn_table', function (Blueprint $table) {
            $cols = array_values(array_filter([
                Schema::hasColumn('payfast_ipn_table', 'idempotency_key') ? 'idempotency_key' : null,
                Schema::hasColumn('payfast_ipn_table', 'payload_hash') ? 'payload_hash' : null,
                Schema::hasColumn('payfast_ipn_table', 'processing_status') ? 'processing_status' : null,
                Schema::hasColumn('payfast_ipn_table', 'duplicate_of') ? 'duplicate_of' : null,
                Schema::hasColumn('payfast_ipn_table', 'request_headers') ? 'request_headers' : null,
            ]));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }

    /**
     * @param  callable(Blueprint): void  $callback
     */
    private function addIndexSafely(string $indexName, callable $callback): void
    {
        if ($this->indexExists($indexName)) {
            return;
        }

        try {
            Schema::table('payfast_ipn_table', $callback);
        } catch (\Throwable) {
        }
    }

    private function indexExists(string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('payfast_ipn_table')");
            foreach ($indexes as $index) {
                $name = is_object($index) ? (string) $index->name : (string) ($index['name'] ?? '');
                if ($name === $indexName) {
                    return true;
                }
            }

            return false;
        }

        try {
            $rows = $connection->select(
                'SELECT INDEX_NAME FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND INDEX_NAME = ? LIMIT 1',
                [$connection->getDatabaseName(), 'payfast_ipn_table', $indexName]
            );

            return $rows !== [];
        } catch (\Throwable) {
            return false;
        }
    }
};
