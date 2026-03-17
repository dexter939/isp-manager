<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('payment_method_id')->nullable()->constrained('online_payment_methods')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('gateway', 20);
            $table->string('external_transaction_id');
            $table->unsignedInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_recurring')->default(false);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'status']);
            $table->index('external_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payment_transactions');
    }
};
