<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('bundle_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contract_id');
            $table->uuid('bundle_plan_id');
            $table->integer('custom_price_amount')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active','suspended','terminated'])->default('active');
            $table->timestamps();
            $table->foreign('bundle_plan_id')->references('id')->on('bundle_plans')->onDelete('restrict');
            $table->index(['contract_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('bundle_subscriptions'); }
};
