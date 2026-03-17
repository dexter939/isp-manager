<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bts_stations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('name', 100);
            $table->string('code', 30)->unique();              // codice interno
            $table->string('type', 20)->default('fwa');        // fwa, ftth, mixed

            // Posizione (PostGIS)
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->text('location_geom')->nullable();         // geometry(Point,4326) — da aggiornare con PostGIS

            $table->string('address', 255)->nullable();
            $table->string('ip_management', 45)->nullable();

            $table->string('status', 20)->default('active');   // active, maintenance, offline
            $table->unsignedInteger('max_clients')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });

        // BTS Sectors (antenne direzionali)
        Schema::create('bts_sectors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bts_station_id');
            $table->string('name', 60);
            $table->unsignedSmallInteger('azimuth')->default(0);    // 0-359 gradi
            $table->unsignedSmallInteger('beam_width')->default(60);
            $table->string('frequency', 20)->nullable();            // es. 5.8GHz
            $table->string('technology', 20)->default('fwa');       // fwa, lte, 5g
            $table->unsignedInteger('bandwidth_dl_mbps')->default(0);
            $table->unsignedInteger('bandwidth_ul_mbps')->default(0);
            $table->unsignedSmallInteger('max_clients')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('bts_station_id')->references('id')->on('bts_stations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bts_sectors');
        Schema::dropIfExists('bts_stations');
    }
};
