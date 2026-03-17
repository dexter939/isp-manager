<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('network_sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['pop','cabinet','datacenter','mast','building','other'])->default('pop');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->smallInteger('altitude_meters')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('lessor_name')->nullable();
            $table->date('lease_expiry')->nullable();
            $table->enum('status', ['active','maintenance','decommissioned'])->default('active');
            $table->enum('importance', ['critical','high','normal','low'])->default('normal');
            $table->timestamps();
            $table->index('status');
            $table->index('type');
        });
    }
    public function down(): void { Schema::dropIfExists('network_sites'); }
};
