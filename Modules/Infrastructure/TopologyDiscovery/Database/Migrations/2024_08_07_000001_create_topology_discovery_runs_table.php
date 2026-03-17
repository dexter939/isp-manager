<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('topology_discovery_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->integer('devices_scanned')->default(0);
            $table->integer('links_discovered')->default(0);
            $table->integer('links_confirmed')->default(0);
            $table->integer('links_removed')->default(0);
            $table->enum('status', ['running','completed','failed'])->default('running');
            $table->text('notes')->nullable();
            $table->index('status');
            $table->index('started_at');
        });
    }
    public function down(): void { Schema::dropIfExists('topology_discovery_runs'); }
};
