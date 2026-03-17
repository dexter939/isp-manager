<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('commission_rules')->nullOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('status', 20)->default('pending');
            $table->date('period_month');
            $table->foreignId('liquidation_id')->nullable()
                  ->constrained('commission_liquidations')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'status', 'period_month']);
        });
    }

    public function down(): void { Schema::dropIfExists('commission_entries'); }
};
