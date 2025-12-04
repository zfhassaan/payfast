<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * This table is used to store all the processed transactions that are completed and the completion ipn is received from
 * the service that this transaction is completed.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payfast_ipn_table', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->default(Str::uuid())->comment('A unique identifier for each IPN log');
            $table->string('order_no')->default(NULL)->comment('Order No from App to track the status.');
            $table->string('transaction_id')->unique()->comment('Unique transaction ID from the payment processor');
            $table->string('status')->comment('Status of the transaction');
            $table->decimal('amount', 10, 2)->comment('Amount of the transaction');
            $table->string('currency', 3)->comment('Currency code of the transaction amount');
            $table->json('details')->nullable()->comment('JSON containing additional details of the transaction');
            $table->timestamp('received_at')->useCurrent()->comment('Timestamp when the IPN was received');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ipn_table');
    }
};
