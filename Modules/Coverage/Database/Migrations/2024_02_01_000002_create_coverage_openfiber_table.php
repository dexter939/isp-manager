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
        Schema::create('coverage_openfiber', function (Blueprint $table) {
            $table->id();
            $table->string('id_building', 50)->unique()->comment('ID edificio Open Fiber');
            $table->string('codice_ui_of', 20)->nullable()->comment('Codice UI Open Fiber');
            $table->string('comune', 100);
            $table->char('provincia', 2)->index();
            $table->char('cap', 5)->nullable();
            $table->string('via', 200);
            $table->string('civico', 10);
            $table->string('via_normalizzata', 200)->nullable();
            $table->string('civico_normalizzato', 10)->nullable();
            $table->string('tecnologia', 10)->comment('FTTH, FTTC, EVDSL, FWA');
            $table->unsignedSmallInteger('velocita_max_dl')->default(0)->comment('Mbps');
            $table->unsignedSmallInteger('velocita_max_ul')->default(0)->comment('Mbps');
            $table->string('stato_commerciale', 30)->default('vendibile');
            $table->timestamp('imported_at')->nullable();
            $table->string('source_file', 100)->nullable();
            $table->timestamps();

            $table->index(['comune', 'provincia'], 'cov_of_comune_prov_idx');
            $table->index(['via_normalizzata', 'civico_normalizzato'], 'cov_of_via_civico_idx');
            $table->index(['comune', 'provincia', 'via_normalizzata', 'civico_normalizzato'],
                'cov_of_address_idx');
            $table->index('stato_commerciale', 'cov_of_stato_idx');
            $table->index('tecnologia', 'cov_of_tech_idx');
        });

        DB::statement('ALTER TABLE coverage_openfiber ADD COLUMN geom GEOMETRY(Point, 4326)');
        DB::statement('CREATE INDEX cov_of_geom_gist_idx ON coverage_openfiber USING GIST (geom)');
        DB::statement("
            CREATE INDEX cov_of_via_fts_idx ON coverage_openfiber
            USING GIN (to_tsvector('italian', coalesce(via_normalizzata, '')))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_openfiber');
    }
};
