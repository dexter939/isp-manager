<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── floating_ip_pairs ─────────────────────────────────────────────────
        Schema::create('floating_ip_pairs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->uuid('master_pppoe_account_id');
            $table->uuid('failover_pppoe_account_id');
            $table->string('status')->default('master_active')
                  ->comment('master_active|failover_active|both_down');
            $table->timestamp('last_failover_at')->nullable();
            $table->timestamp('last_recovery_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('master_pppoe_account_id')
                  ->references('id')->on('pppoe_accounts')
                  ->onDelete('restrict');

            $table->foreign('failover_pppoe_account_id')
                  ->references('id')->on('pppoe_accounts')
                  ->onDelete('restrict');
        });

        // ── floating_ip_resources ─────────────────────────────────────────────
        Schema::create('floating_ip_resources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('floating_ip_pair_id');
            $table->string('resource_type')->comment('ipv4|ipv4_subnet|ipv6_prefix');
            // Store as text; cast to PostgreSQL inet type via raw query where needed
            $table->text('resource_value');
            $table->timestamps();

            $table->foreign('floating_ip_pair_id')
                  ->references('id')->on('floating_ip_pairs')
                  ->onDelete('cascade');
        });

        // ── floating_ip_events ────────────────────────────────────────────────
        Schema::create('floating_ip_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('floating_ip_pair_id');
            $table->string('event_type')
                  ->comment('failover_triggered|recovery_triggered|manual_override');
            $table->string('triggered_by');
            $table->string('previous_status');
            $table->string('new_status');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable(); // no updated_at

            $table->foreign('floating_ip_pair_id')
                  ->references('id')->on('floating_ip_pairs')
                  ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('floating_ip_events');
        Schema::dropIfExists('floating_ip_resources');
        Schema::dropIfExists('floating_ip_pairs');
    }
};
