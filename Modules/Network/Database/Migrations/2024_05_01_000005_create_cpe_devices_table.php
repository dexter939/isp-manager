<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpe_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('bts_station_id')->nullable(); // per FWA

            $table->string('mac_address', 17)->nullable()->index();   // AA:BB:CC:DD:EE:FF
            $table->string('serial_number', 60)->nullable()->index();
            $table->string('model', 100)->nullable();
            $table->string('manufacturer', 60)->nullable();
            $table->string('firmware_version', 30)->nullable();

            $table->string('type', 20)->default('router');
            // router, ont, cpe_fwa, switch, ont_router

            $table->string('technology', 10)->nullable();
            // ftth, fttc, fwa

            // TR-069 ACS (GenieACS)
            $table->string('tr069_id', 100)->nullable()->index();     // DeviceId.SerialNumber
            $table->string('tr069_inform_ip', 45)->nullable();
            $table->timestamp('tr069_last_inform')->nullable();

            // IP
            $table->string('wan_ip', 45)->nullable();
            $table->string('lan_ip', 45)->nullable();

            $table->string('status', 20)->default('active');
            // active, offline, maintenance, decommissioned

            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('contract_id')->references('id')->on('contracts')->restrictOnDelete();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpe_devices');
    }
};
