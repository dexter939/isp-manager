<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_liquidations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->date('period_month');
            $table->unsignedInteger('total_amount_cents')->default(0);
            $table->string('status', 20)->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->string('iban', 34);
            $table->timestamps();

            $table->unique(['agent_id', 'period_month']);
        });
    }

    public function down(): void { Schema::dropIfExists('commission_liquidations'); }
};
