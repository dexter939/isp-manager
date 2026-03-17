<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bollettini_td896', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('numero_bollettino', 18)->unique();
            $table->unsignedInteger('importo_centesimi');
            $table->string('causale', 60);
            $table->string('conto_corrente', 20);
            $table->string('status', 20)->default('generated');
            $table->timestamp('generated_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('scadenza_at');
            $table->foreignId('reconciliation_file_id')->nullable()
                  ->constrained('poste_reconciliation_files')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'scadenza_at']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bollettini_td896');
    }
};
