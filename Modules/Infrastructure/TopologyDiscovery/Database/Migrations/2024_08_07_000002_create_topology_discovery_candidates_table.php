<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('topology_discovery_candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('discovery_run_id');
            $table->uuid('source_device_id');
            $table->string('target_mac', 17);
            $table->string('target_ip', 45)->nullable();
            $table->string('target_hostname')->nullable();
            $table->string('source_interface');
            $table->string('target_interface')->nullable();
            $table->enum('discovery_method', ['lldp','cdp','snmp_arp','snmp_bridge'])->default('lldp');
            $table->uuid('matched_device_id')->nullable();
            $table->enum('status', ['pending','confirmed','rejected','auto_created'])->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('discovery_run_id')->references('id')->on('topology_discovery_runs')->onDelete('cascade');
            $table->index(['discovery_run_id','status']);
            $table->index('matched_device_id');
        });
    }
    public function down(): void { Schema::dropIfExists('topology_discovery_candidates'); }
};
