<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('elevation_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('network_site_id');
            $table->decimal('customer_lat', 10, 8);
            $table->decimal('customer_lon', 11, 8);
            $table->string('customer_address')->nullable();
            $table->decimal('distance_km', 6, 3);
            $table->smallInteger('max_elevation_m');
            $table->smallInteger('min_elevation_m');
            $table->smallInteger('fresnel_clearance_percent')->nullable();
            $table->boolean('has_obstruction')->default(false);
            $table->jsonb('profile_data');
            $table->smallInteger('antenna_height_m')->default(10);
            $table->smallInteger('cpe_height_m')->default(3);
            $table->decimal('frequency_ghz', 4, 2)->nullable();
            $table->timestamp('calculated_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['network_site_id','customer_lat','customer_lon']);
        });
    }
    public function down(): void { Schema::dropIfExists('elevation_profiles'); }
};
