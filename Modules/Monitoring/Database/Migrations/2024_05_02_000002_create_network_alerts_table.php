<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('cpe_device_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();

            $table->string('source', 30);
            // line_test_of, line_test_fibercop, tr069, snmp, manual

            $table->string('severity', 10)->default('warning');
            // info, warning, critical

            $table->string('type', 60);
            // ont_offline, ont_power_off, massive_fault, degraded_signal,
            // l02_unreachable, l07_mso, tr069_offline

            $table->text('message');
            $table->jsonb('details')->nullable();              // dati raw del test/alert

            $table->string('status', 20)->default('open');
            // open, acknowledged, resolved, auto_resolved

            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status', 'severity']);
            $table->index(['customer_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_alerts');
    }
};
