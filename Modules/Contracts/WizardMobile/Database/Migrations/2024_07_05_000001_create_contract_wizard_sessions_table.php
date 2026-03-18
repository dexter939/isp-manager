<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_wizard_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relazioni opzionali
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->nullOnDelete();

            // Stato wizard
            $table->unsignedInteger('current_step')->default(0);
            $table->jsonb('step_data')->default('{}');
            $table->string('status')->default('in_progress');

            // OTP
            $table->boolean('otp_verified')->default(false);
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            // Contratto completato
            $table->foreignId('completed_contract_id')
                  ->nullable()
                  ->constrained('contracts')
                  ->nullOnDelete();

            // Timestamps di ciclo di vita
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at');

            $table->timestamps();

            // Indici
            $table->index('status');
            $table->index('agent_id');
            $table->index('expires_at');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_wizard_sessions');
    }
};
