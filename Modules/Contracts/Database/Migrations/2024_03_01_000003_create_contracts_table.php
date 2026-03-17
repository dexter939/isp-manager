<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_plan_id')->constrained();

            // Indirizzo installazione
            $table->jsonb('indirizzo_installazione')
                ->comment('{"via","civico","comune","provincia","cap","scala","piano","interno"}');

            // Riferimenti carrier (popolati dopo feasibility check)
            $table->string('codice_ui', 20)->nullable()
                ->comment('Codice UI FiberCop (per ordini FC)');
            $table->string('id_building', 50)->nullable()
                ->comment('ID edificio Open Fiber (per ordini OF)');
            $table->string('carrier', 30)->comment('openfiber, fibercop, fastweb');

            // Billing
            $table->string('billing_cycle', 10)->default('monthly')
                ->comment('monthly, annual');
            $table->tinyInteger('billing_day')->default(1)
                ->comment('Giorno del mese per fatturazione (1-28)');

            // Prezzi snapshot al momento della firma (mai modificare dopo firma)
            $table->decimal('monthly_price', 10, 2)
                ->comment('Canone mensile IVA esclusa al momento contratto');
            $table->decimal('activation_fee', 10, 2)->default(0.00);
            $table->decimal('modem_fee', 10, 2)->default(0.00);

            // Date
            $table->date('activation_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->date('min_end_date')->nullable()
                ->comment('Data minima di cessazione (durata minima contratto)');

            // Stato workflow
            $table->string('status', 30)->default('draft')
                ->comment('draft, pending_signature, active, suspended, terminated');

            // Firma FEA
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_ip', 45)->nullable();

            // Documento
            $table->string('pdf_path', 500)->nullable()
                ->comment('Path su MinIO bucket contracts (WORM)');
            $table->string('pdf_hash_sha256', 64)->nullable()
                ->comment('SHA-256 del PDF post-firma per verifica integrità');

            // Agent che ha concluso il contratto
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status'], 'contracts_tenant_status_idx');
            $table->index(['customer_id', 'status'], 'contracts_customer_status_idx');
            $table->index(['billing_day', 'status'], 'contracts_billing_day_idx');
            $table->index('carrier', 'contracts_carrier_idx');
            $table->index('codice_ui', 'contracts_codice_ui_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
