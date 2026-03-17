<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('topology_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_device_id');
            $table->uuid('target_device_id');
            $table->uuid('network_site_id')->nullable();
            $table->enum('link_type', ['fiber','radio','copper','uplink','aggregate','other'])->default('fiber');
            $table->integer('bandwidth_mbps')->nullable();
            $table->string('source_interface')->nullable();
            $table->string('target_interface')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_monitored')->default(true);
            $table->enum('status', ['up','down','degraded','unknown'])->default('unknown');
            $table->timestamp('last_status_change')->nullable();
            $table->timestamps();
            $table->index('source_device_id');
            $table->index('target_device_id');
            $table->index('network_site_id');
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('topology_links'); }
};
