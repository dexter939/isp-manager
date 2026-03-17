<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('fup_topup_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('pppoe_account_id');
            $table->uuid('product_id');
            $table->smallInteger('period_year');
            $table->smallInteger('period_month');
            $table->integer('gb_added');
            $table->integer('price_amount');
            $table->char('price_currency', 3)->default('EUR');
            $table->string('payment_method');
            $table->uuid('invoice_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['pppoe_account_id','period_year','period_month']);
        });
    }
    public function down(): void { Schema::dropIfExists('fup_topup_purchases'); }
};
