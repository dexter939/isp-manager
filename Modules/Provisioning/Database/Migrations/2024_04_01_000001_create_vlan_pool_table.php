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
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('carrier', 30)->comment('openfiber, fibercop, fastweb');
            $table->string('vlan_type', 10)->default('C-VLAN')->comment('C-VLAN, S-VLAN');
            $table->unsignedSmallInteger('vlan_id')->comment('Range 1-4094');

            $table->string('status', 10)->default('free')->comment('free, assigned, reserved');
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['carrier', 'vlan_type', 'vlan_id'], 'vlan_pool_unique_vlan');
            $table->index(['tenant_id', 'carrier', 'status'], 'vlan_pool_carrier_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vlan_pool');
    }
};
