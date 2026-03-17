<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrier_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();

            // Identificativi
            $table->string('carrier', 30)->comment('openfiber, fibercop, fastweb');
            $table->string('order_type', 20)->comment('activation, change, deactivation, migration');
            $table->string('codice_ordine_olo', 50)->unique()
                ->comment('Nostro ID univoco — es: ISP-2025-001234');
            $table->string('codice_ordine_of', 50)->nullable()->index()
                ->comment('ID assegnato dal carrier dopo accettazione');

            // Stato ordine (macchina a stati)
            $table->string('state', 20)->default('draft')
                ->comment('draft,sent,accepted,scheduled,in_progress,completed,ko,cancelled,retry_failed');

            // Dettagli tecnici
            $table->dateTime('scheduled_date')->nullable()
                ->comment('Data appuntamento confermata dal carrier');
            $table->string('cvlan', 10)->nullable()
                ->comment('C-VLAN assegnata da OF/FC');
            $table->string('gpon_attestazione', 30)->nullable()
                ->comment('GPON_DI_ATTESTAZIONE — MAX 30 char (spec OF)');
            $table->string('id_apparato_consegnato', 100)->nullable()
                ->comment('ID ONT/CPE consegnato al cliente (OF CompletionOrder)');

            // VLAN assegnata
            $table->foreignId('vlan_pool_id')->nullable()->constrained('vlan_pool')->nullOnDelete();

            // Payload raw (per debugging e compliance)
            $table->longText('payload_sent')->nullable()
                ->comment('XML/SOAP/JSON raw inviato al carrier');
            $table->longText('payload_received')->nullable()
                ->comment('XML/JSON raw ricevuto dal carrier');

            // Retry
            $table->text('last_error')->nullable();
            $table->tinyInteger('retry_count')->default(0);
            $table->timestamp('next_retry_at')->nullable();

            // Agent che ha inviato l'ordine
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'state'], 'co_tenant_state_idx');
            $table->index(['carrier', 'state'], 'co_carrier_state_idx');
            $table->index('scheduled_date', 'co_scheduled_idx');
            $table->index('next_retry_at', 'co_retry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_orders');
    }
};
