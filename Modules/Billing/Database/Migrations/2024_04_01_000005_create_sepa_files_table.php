<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepa_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('message_id', 35)->unique();        // CBIHdrTrt MsgId
            $table->string('type', 10)->default('pain008');    // pain008 (addebito) | pain002 (status)
            $table->string('filename', 200);

            // Statistiche batch
            $table->integer('transaction_count')->default(0);
            $table->decimal('control_sum', 12, 2)->default(0);
            $table->date('settlement_date');                   // data addebito richiesta

            $table->string('status', 30)->default('generated');
            // generated, submitted, accepted, rejected, partially_rejected

            // Storage (MinIO bucket: ispmanager-invoices)
            $table->string('storage_path')->nullable();

            // Risposta CBI
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('bank_acknowledged_at')->nullable();
            $table->text('bank_response_raw')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('settlement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepa_files');
    }
};
