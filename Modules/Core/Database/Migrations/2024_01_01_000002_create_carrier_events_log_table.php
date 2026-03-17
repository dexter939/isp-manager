<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_events_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('carrier_order_id')->nullable()->index();
            $table->string('carrier', 50)->comment('openfiber, fibercop, ...');
            $table->string('direction', 10)->comment('outbound, inbound');
            $table->string('method_name', 150)->comment('Es: OLO_ActivationSetup_OpenStream');
            $table->text('payload')->nullable()->comment('XML/JSON raw inviato o ricevuto');
            $table->smallInteger('http_status')->nullable()->comment('HTTP status code risposta');
            $table->string('ack_nack', 10)->nullable()->comment('ACK, NACK, ERROR');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['carrier', 'created_at'], 'cel_carrier_created_idx');
            $table->index(['carrier_order_id', 'created_at'], 'cel_order_created_idx');
            $table->index('direction', 'cel_direction_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_events_log');
    }
};
