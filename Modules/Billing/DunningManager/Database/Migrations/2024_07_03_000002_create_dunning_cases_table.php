<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dunning_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('policy_id')->constrained('dunning_policies');
            $table->string('status')->default('open'); // open|resolved|terminated
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->integer('current_step_index')->default(0);
            $table->timestamp('next_action_at');
            $table->integer('total_penalty_cents')->default(0);
            $table->timestamps();

            $table->index(['status', 'next_action_at']);
            $table->index('customer_id');
            $table->index('contract_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_cases');
    }
};
