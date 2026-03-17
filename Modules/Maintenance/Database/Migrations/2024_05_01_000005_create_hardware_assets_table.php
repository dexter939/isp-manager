<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro fisico degli apparati ISP (ONT, router, CPE FWA, SIM).
 * Un record = un singolo dispositivo fisico identificato da serial number.
 * Distinto da inventory_items che gestisce lo stock per SKU.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hardware_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('contract_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_by')->nullable();     // user_id tecnico

            // Identificazione fisica
            $table->string('type', 30);                                // ont, router, cpe_fwa, sim, switch, other
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->unique();
            $table->string('mac_address', 17)->nullable()->unique();
            $table->string('qr_code', 100)->nullable()->unique();

            // Stato operativo
            $table->string('status', 30)->default('in_stock');        // in_stock, assigned, in_repair, disposed

            // Tracciamento assegnazione
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            // Dati acquisto
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('warranty_expires')->nullable();
            $table->string('supplier', 100)->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'type', 'status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hardware_assets');
    }
};
