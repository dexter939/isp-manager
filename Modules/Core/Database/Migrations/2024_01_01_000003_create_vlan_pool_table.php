<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vlan_pool', function (Blueprint $table) {
            $table->id();
            $table->string('carrier', 50);
            $table->unsignedSmallInteger('vlan_id')->comment('VLAN ID numerico 1-4094');
            $table->string('type', 20)->comment('C-VLAN, S-VLAN');
            $table->string('status', 20)->default('free')->comment('free, assigned, reserved, decommissioned');
            $table->unsignedBigInteger('contract_id')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['carrier', 'vlan_id', 'type'], 'vlan_pool_carrier_vlan_type_unique');
            $table->index(['carrier', 'status'], 'vlan_pool_carrier_status_idx');
            $table->index(['status', 'type'], 'vlan_pool_status_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vlan_pool');
    }
};
