<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabinets', function (Blueprint $table) {
            $table->id();
            $table->string('carrier', 20)->comment('fibercop, openfiber');
            $table->string('cabinet_id', 30)->comment('ID armadio carrier');
            $table->string('comune', 100);
            $table->char('provincia', 2)->index();
            $table->string('indirizzo', 300)->nullable()->comment('Indirizzo fisico armadio');
            $table->unsignedSmallInteger('max_ul_distance_m')->nullable()
                ->comment('Distanza massima utile per VDSL2 in metri');
            $table->timestamps();

            $table->unique(['carrier', 'cabinet_id'], 'cabinets_carrier_id_unique');
            $table->index(['carrier', 'comune'], 'cabinets_carrier_comune_idx');
        });

        // Colonna geometrica per posizione fisica armadio
        DB::statement('ALTER TABLE cabinets ADD COLUMN geom GEOMETRY(Point, 4326)');
        DB::statement('CREATE INDEX cabinets_geom_gist_idx ON cabinets USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cabinets');
    }
};
