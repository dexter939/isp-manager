<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log immutabile di OGNI chiamata carrier (in/out).
     * Non si cancella. Retention: almeno 5 anni.
     * Partitioned by year in produzione (PostgreSQL partitioning).
     */
    public function up(): void
    {
        Schema::create('carrier_events_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('carrier', 30)->comment('openfiber, fibercop, fastweb');
            $table->string('direction', 10)->comment('outbound = OLO→carrier | inbound = carrier→OLO webhook');
            $table->string('method_name', 100)->comment('OLO_ActivationSetup, OF_StatusUpdate, ecc.');

            // Riferimento ordine (nullable per log generici)
            $table->foreignId('carrier_order_id')->nullable()->constrained('carrier_orders')->nullOnDelete();
            $table->string('codice_ordine_olo', 50)->nullable()->index();

            // Payload
            $table->longText('payload')->nullable()->comment('XML/JSON raw');
            $table->unsignedSmallInteger('http_status')->nullable()->comment('HTTP status code risposta');
            $table->string('ack_nack', 10)->nullable()->comment('ack, nack, timeout, error');
            $table->text('error_message')->nullable();

            // Timing
            $table->unsignedInteger('duration_ms')->nullable()->comment('Durata chiamata in ms');

            // IP sorgente (per webhook inbound)
            $table->string('source_ip', 45)->nullable();

            $table->timestamp('logged_at')->useCurrent();

            $table->index(['tenant_id', 'carrier', 'logged_at'], 'cel_carrier_date_idx');
            $table->index(['codice_ordine_olo', 'logged_at'], 'cel_order_date_idx');
            $table->index(['direction', 'ack_nack'], 'cel_direction_ack_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_events_log');
    }
};
