<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── parental_control_profiles ─────────────────────────────────────────
        Schema::create('parental_control_profiles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->jsonb('blocked_categories')->default('[]');
            $table->jsonb('custom_blacklist')->default('[]');
            $table->jsonb('custom_whitelist')->default('[]');
            $table->boolean('agcom_compliant')->default(false);
            $table->timestamps();
        });

        // ── parental_control_subscriptions ────────────────────────────────────
        Schema::create('parental_control_subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('customer_id');
            $table->uuid('pppoe_account_id')->nullable();
            $table->uuid('profile_id');
            $table->string('status')->default('pending')
                  ->comment('active|suspended|pending');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->jsonb('customer_custom_blacklist')->default('[]');
            $table->jsonb('customer_custom_whitelist')->default('[]');
            $table->timestamps();

            $table->foreign('customer_id')
                  ->references('id')->on('customers')
                  ->onDelete('restrict');

            $table->foreign('pppoe_account_id')
                  ->references('id')->on('pppoe_accounts')
                  ->onDelete('set null');

            $table->foreign('profile_id')
                  ->references('id')->on('parental_control_profiles')
                  ->onDelete('restrict');
        });

        // ── parental_control_logs ─────────────────────────────────────────────
        // Uses bigint PK (not UUID) for insert performance.
        // NOTE: For very high-volume deployments consider converting this to a
        // PostgreSQL range-partitioned table (PARTITION BY RANGE (queried_at)).
        // This can be done post-migration with a DB::statement if needed.
        Schema::create('parental_control_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->uuid('tenant_id')->index();
            $table->uuid('subscription_id');
            $table->string('queried_domain');
            $table->string('action')->comment('allowed|blocked');
            $table->string('blocked_reason')->nullable();
            $table->string('client_ip');
            $table->timestamp('queried_at')->index();
            // No updated_at — append-only log table

            $table->foreign('subscription_id')
                  ->references('id')->on('parental_control_subscriptions')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parental_control_logs');
        Schema::dropIfExists('parental_control_subscriptions');
        Schema::dropIfExists('parental_control_profiles');
    }
};
