<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('payfast_process_payments_table', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'validated',
                'otp_verified',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending')->after('transaction_id');
            $table->text('data_3ds_pares')->nullable()->after('data_3ds_secureid');
            $table->string('payment_method')->nullable()->after('status'); // card, easypaisa, jazzcash, upaisa
            $table->timestamp('otp_verified_at')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('otp_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('payfast_process_payments_table', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'data_3ds_pares',
                'payment_method',
                'otp_verified_at',
                'completed_at',
            ]);
        });
    }
};

