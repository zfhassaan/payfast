<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('process_payments_table_in_payfast', function (Blueprint $table) {
            $table->id();
            $table->string('token');
            $table->string('orderNo');
            $table->string('data_3ds_secureid');
            $table->string('transaction_id');
            $table->longText('payload');
            $table->longText('requestData');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_payments_table_in_payfast');
    }
};
