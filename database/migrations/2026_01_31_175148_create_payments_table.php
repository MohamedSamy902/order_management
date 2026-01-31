<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('payment_id')->unique(); // External gateway payment ID
            $table->string('gateway'); // myfatoorah, tabby, tamara
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('status'); // pending, authorized, captured, failed, refunded
            $table->string('transaction_id')->nullable();
            $table->json('gateway_response')->nullable(); // Store full response for debugging
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('payment_id');
            $table->index('order_id');
            $table->index('gateway');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
