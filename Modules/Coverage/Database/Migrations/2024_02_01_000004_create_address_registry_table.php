<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro normalizzato degli indirizzi con copertura multipla.
     * Viene popolato/aggiornato da RebuildAddressRegistryJob dopo ogni import.
     * Funge da cache join per le query FeasibilityService.
     */
    public function up(): void
    {
        Schema::create('address_registry', function (Blueprint $table) {
            $table->id();
            $table->string('comune', 100);
            $table->char('provincia', 2);
            $table->char('cap', 5)->nullable();
            $table->string('via_normalizzata', 200);
            $table->string('civico_normalizzato', 10);

            // Riferimenti alle tabelle di copertura (nullable = non coperto da quel carrier)
            $table->unsignedBigInteger('coverage_fibercop_id')->nullable()->index();
            $table->unsignedBigInteger('coverage_openfiber_id')->nullable()->index();

            // Info tecnologie disponibili (denormalizzato per performance)
            $table->boolean('has_ftth_fibercop')->default(false)->index();
            $table->boolean('has_ftth_openfiber')->default(false)->index();
            $table->boolean('has_fttc')->default(false)->index();
            $table->boolean('has_fwa')->default(false)->index();

            // Distanza calcolata dall'armadio più vicino (per VDSL2 speed estimate)
            $table->unsignedSmallInteger('distance_to_cabinet_m')->nullable()
                ->comment('Distanza in metri dall\'armadio FiberCop più vicino');

            $table->timestamp('last_rebuilt_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['comune', 'provincia', 'via_normalizzata', 'civico_normalizzato'],
                'addr_registry_full_address_unique'
            );
            $table->index(['comune', 'provincia'], 'addr_registry_comune_prov_idx');
        });

        // Foreign constraints (opzionali, senza cascade per permettere rebuild)
        DB::statement('
            ALTER TABLE address_registry
            ADD CONSTRAINT addr_registry_fc_fk
            FOREIGN KEY (coverage_fibercop_id) REFERENCES coverage_fibercop(id) ON DELETE SET NULL
        ');
        DB::statement('
            ALTER TABLE address_registry
            ADD CONSTRAINT addr_registry_of_fk
            FOREIGN KEY (coverage_openfiber_id) REFERENCES coverage_openfiber(id) ON DELETE SET NULL
        ');

        // Full-text search
        DB::statement("
            CREATE INDEX addr_registry_via_fts_idx ON address_registry
            USING GIN (to_tsvector('italian', via_normalizzata || ' ' || comune))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('address_registry');
    }
};
