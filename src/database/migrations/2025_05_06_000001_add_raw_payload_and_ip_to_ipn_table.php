<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add raw_payload and ip_address columns to the payfast_ipn_table
 * for comprehensive IPN data storage and audit trail.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payfast_ipn_table', function (Blueprint $table) {
            $table->text('raw_payload')->nullable()->after('details')
                ->comment('Raw request body from PayFast IPN callback');
            $table->string('ip_address', 45)->nullable()->after('raw_payload')
                ->comment('IP address of the IPN request sender');

            // Make transaction_id nullable since some IPNs may only have order_no
            $table->string('transaction_id')->nullable()->change();

            // Drop the unique constraint on transaction_id and replace with index
            // since PayFast may send multiple IPNs for the same transaction
            $table->dropUnique(['transaction_id']);
            $table->index('transaction_id');
            $table->index('order_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payfast_ipn_table', function (Blueprint $table) {
            $table->dropIndex(['order_no']);
            $table->dropIndex(['transaction_id']);
            $table->unique('transaction_id');
            $table->string('transaction_id')->change();
            $table->dropColumn(['raw_payload', 'ip_address']);
        });
    }
};
