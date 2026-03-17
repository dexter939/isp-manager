<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name', 100)->comment('Es: Fibra 1Gbps, FTTC 200Mbps');
            $table->string('carrier', 30)->comment('openfiber, fibercop, fastweb');
            $table->string('technology', 10)->comment('FTTH, FTTC, EVDSL, FWA');

            // Prezzi — DECIMAL, mai float
            $table->decimal('price_monthly', 10, 2)->comment('Canone mensile IVA esclusa');
            $table->decimal('activation_fee', 10, 2)->default(0.00)->comment('Costo attivazione una-tantum');
            $table->decimal('modem_fee', 10, 2)->default(0.00)->comment('Costo modem/ONT (0 se incluso)');

            // Codice prodotto carrier (obbligatorio per ordini)
            $table->string('carrier_product_code', 100)->nullable()
                ->comment('Codice prodotto da usare negli ordini carrier');

            // Banda
            $table->unsignedSmallInteger('bandwidth_dl')->comment('Mbps download nominale');
            $table->unsignedSmallInteger('bandwidth_ul')->comment('Mbps upload nominale');

            // SLA
            $table->string('sla_type', 20)->nullable()->comment('BEST_EFFORT, PREMIUM, GARANTITO');
            $table->unsignedSmallInteger('mtr_hours')->nullable()
                ->comment('Mean Time to Restore in ore (SLA premium)');

            // Stato piano
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_public')->default(true)
                ->comment('False = solo vendita manuale (non visibile in wizard)');

            // Condizioni contrattuali
            $table->unsignedSmallInteger('min_contract_months')->default(24)
                ->comment('Durata minima contratto in mesi');
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'carrier', 'technology'], 'svc_plans_carrier_tech_idx');
            $table->index(['tenant_id', 'is_active'], 'svc_plans_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_plans');
    }
};
