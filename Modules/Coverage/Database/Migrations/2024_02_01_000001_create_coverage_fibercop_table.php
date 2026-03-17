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
        Schema::create('coverage_fibercop', function (Blueprint $table) {
            $table->id();
            $table->string('codice_ui', 20)->unique()->comment('Codice univoco indirizzo FiberCop');
            $table->string('comune', 100);
            $table->char('provincia', 2)->index();
            $table->char('cap', 5)->nullable();
            $table->string('via', 200);
            $table->string('civico', 10);
            $table->string('via_normalizzata', 200)->nullable()->comment('Via dopo normalizzazione toponomastica');
            $table->string('civico_normalizzato', 10)->nullable();
            $table->string('tecnologia', 10)->comment('FTTH, FTTC, EVDSL, FWA');
            $table->unsignedSmallInteger('velocita_max_dl')->default(0)->comment('Mbps download massimo');
            $table->unsignedSmallInteger('velocita_max_ul')->default(0)->comment('Mbps upload massimo');
            $table->string('stato_commerciale', 30)->default('vendibile')
                ->comment('vendibile, in_costruzione, non_vendibile');
            $table->string('armadio_id', 20)->nullable()->index()->comment('ID armadio stradale FiberCop');
            $table->timestamp('imported_at')->nullable();
            $table->string('source_file', 100)->nullable()->comment('Nome file NetMap di provenienza');
            $table->timestamps();

            $table->index(['comune', 'provincia'], 'cov_fc_comune_prov_idx');
            $table->index(['via_normalizzata', 'civico_normalizzato'], 'cov_fc_via_civico_idx');
            $table->index(['comune', 'provincia', 'via_normalizzata', 'civico_normalizzato'],
                'cov_fc_address_idx');
            $table->index('stato_commerciale', 'cov_fc_stato_idx');
            $table->index('tecnologia', 'cov_fc_tech_idx');
        });

        // Colonna geometrica PostGIS (GEOMETRY Point SRID 4326)
        DB::statement('ALTER TABLE coverage_fibercop ADD COLUMN geom GEOMETRY(Point, 4326)');

        // Indice GIST per query spaziali
        DB::statement('CREATE INDEX cov_fc_geom_gist_idx ON coverage_fibercop USING GIST (geom)');

        // Indice GIN full-text per ricerca toponomastica italiana
        DB::statement("
            CREATE INDEX cov_fc_via_fts_idx ON coverage_fibercop
            USING GIN (to_tsvector('italian', coalesce(via_normalizzata, '')))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_fibercop');
    }
};
