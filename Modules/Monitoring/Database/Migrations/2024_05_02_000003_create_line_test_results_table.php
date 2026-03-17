<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_test_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('customer_id');

            $table->string('carrier', 20);                     // openfiber, fibercop
            $table->string('resource_id', 60);                 // codice_ui o CORD

            // Risultato
            $table->string('result', 10);                      // OK, KO
            $table->string('error_code', 10)->nullable();      // L01-L07

            // Dati tecnici (solo se OK)
            $table->string('ont_state', 20)->nullable();       // UP, DOWN, POWER OFF
            $table->decimal('attenuation_dbm', 6, 2)->nullable();
            $table->decimal('optical_distance_m', 8, 1)->nullable();
            $table->string('ont_lan_status', 20)->nullable();  // ENABLED, DISABLED

            // Metadati
            $table->unsignedInteger('test_instance_id')->nullable();
            $table->boolean('is_retryable')->default(false);
            $table->boolean('triggered_ticket')->default(false);
            $table->jsonb('raw_response')->nullable();

            $table->string('initiated_by', 30)->default('system');
            // system (scheduler), operator (manuale), api (esterno)

            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->restrictOnDelete();
            $table->index(['tenant_id', 'carrier', 'result']);
            $table->index(['contract_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_test_results');
    }
};
