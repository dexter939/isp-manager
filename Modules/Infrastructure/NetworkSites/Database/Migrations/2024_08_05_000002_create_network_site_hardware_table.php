<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('network_site_hardware', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('network_site_id');
            $table->uuid('hardware_id');
            $table->boolean('is_access_device')->default(false);
            $table->timestamp('linked_at')->useCurrent();
            $table->foreign('network_site_id')->references('id')->on('network_sites')->onDelete('cascade');
            $table->unique(['network_site_id','hardware_id']);
            $table->index('network_site_id');
        });
    }
    public function down(): void { Schema::dropIfExists('network_site_hardware'); }
};
