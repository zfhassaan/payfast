<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This migration creates the 'activity_logs' table to track transactions processed through PayFast,
 * offering a comprehensive history and insights into each transaction. It's designed to prevent data loss
 * during the transaction process and enables the monitoring of user behavior and purchasing patterns on the app.
 * The table includes details such as user identification, transaction status, amounts, and timestamps,
 * alongside metadata for richer transactional context. This structure is pivotal for analyzing user engagement,
 * transactional integrity, and overall app performance.
 *
 **/

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->default(\Illuminate\Support\Str::uuid());
            $table->unsignedBigInteger('user_id')->index()->comment('The ID of the user');
            $table->string('transaction_id')->unique()->comment('Unique transaction ID from PayFast');
            $table->string('order_no')->unique()->comment('Unique Order Number from App.');
            $table->string('status')->comment('Transaction status');
            $table->decimal('amount', 10, 2)->comment('Amount of the transaction');
            $table->text('details')->nullable()->comment('Details of the transaction');
            $table->json('metadata')->nullable()->comment('Additional transaction metadata');
            $table->timestamp('transaction_date')->useCurrent()->comment('The date and time of the transaction');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
