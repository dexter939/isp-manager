<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. prepaid_wallets
        Schema::create('prepaid_wallets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->integer('balance_amount')->default(0);
            $table->char('balance_currency', 3)->default('EUR');
            $table->string('status')->default('active');
            $table->integer('low_balance_threshold_amount')->default(500);
            $table->boolean('auto_suspend_on_zero')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. prepaid_topup_products
        Schema::create('prepaid_topup_products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name');
            $table->integer('amount_amount');
            $table->char('amount_currency', 3)->default('EUR');
            $table->integer('bonus_amount')->default(0);
            $table->integer('validity_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. prepaid_transactions
        Schema::create('prepaid_transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('wallet_id');
            $table->foreign('wallet_id')->references('id')->on('prepaid_wallets')->cascadeOnDelete();
            $table->string('type');
            $table->integer('amount_amount');
            $table->char('amount_currency', 3)->default('EUR');
            $table->string('direction');
            $table->integer('balance_before_amount');
            $table->integer('balance_after_amount');
            $table->string('description');
            $table->uuid('reference_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 4. prepaid_resellers
        Schema::create('prepaid_resellers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->uuid('wallet_id');
            $table->foreign('wallet_id')->references('id')->on('prepaid_wallets')->cascadeOnDelete();
            $table->string('commission_type');
            $table->integer('commission_value_amount');
            $table->char('commission_currency', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. prepaid_topup_orders
        Schema::create('prepaid_topup_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('wallet_id');
            $table->foreign('wallet_id')->references('id')->on('prepaid_wallets')->cascadeOnDelete();
            $table->uuid('product_id');
            $table->foreign('product_id')->references('id')->on('prepaid_topup_products')->cascadeOnDelete();
            $table->uuid('reseller_id')->nullable();
            $table->foreign('reseller_id')->references('id')->on('prepaid_resellers')->nullOnDelete();
            $table->integer('amount_amount');
            $table->char('amount_currency', 3)->default('EUR');
            $table->integer('commission_amount')->nullable();
            $table->string('payment_method');
            $table->string('payment_reference')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prepaid_topup_orders');
        Schema::dropIfExists('prepaid_resellers');
        Schema::dropIfExists('prepaid_transactions');
        Schema::dropIfExists('prepaid_topup_products');
        Schema::dropIfExists('prepaid_wallets');
    }
};
