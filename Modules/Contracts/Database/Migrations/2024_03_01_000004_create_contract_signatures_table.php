<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log completo della firma FEA (Firma Elettronica Avanzata).
     * Compliance: art. 26 eIDAS, delibere AgID.
     * Questo log NON va mai cancellato (conservazione 10 anni).
     */
    public function up(): void
    {
        Schema::create('contract_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();

            // OTP
            $table->string('otp_hash', 255)->comment('bcrypt dell\'OTP inviato al cliente');
            $table->string('otp_channel', 20)->default('sms')
                ->comment('sms, whatsapp, email');
            $table->string('otp_sent_to', 100)->nullable()
                ->comment('Numero/email a cui è stato inviato l\'OTP (parzialmente mascherato)');
            $table->timestamp('otp_sent_at')->nullable();
            $table->timestamp('otp_expires_at')->nullable()
                ->comment('Scadenza OTP (sent_at + 24h)');
            $table->timestamp('otp_verified_at')->nullable();
            $table->boolean('otp_used')->default(false);

            // Dati firma (popolati dopo verifica OTP)
            $table->string('signer_ip', 45)->nullable();
            $table->text('signer_user_agent')->nullable();
            $table->timestamp('signed_at')->nullable();

            // Hash documento (audit trail)
            $table->string('pdf_hash_pre_firma', 64)->nullable()
                ->comment('SHA-256 del PDF PRIMA della firma visuale');
            $table->string('pdf_hash_post_firma', 64)->nullable()
                ->comment('SHA-256 del PDF DOPO aggiunta firma visuale');

            // Esito
            $table->string('status', 20)->default('pending')
                ->comment('pending, signed, expired, failed');
            $table->text('failure_reason')->nullable();

            // Numero tentativi OTP falliti
            $table->tinyInteger('failed_attempts')->default(0);

            $table->timestamps();

            $table->index('contract_id', 'contract_sig_contract_idx');
            $table->index(['contract_id', 'status'], 'contract_sig_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_signatures');
    }
};
