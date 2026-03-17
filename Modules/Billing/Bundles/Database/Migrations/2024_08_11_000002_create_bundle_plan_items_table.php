<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('bundle_plan_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bundle_plan_id');
            $table->enum('service_type', ['internet','voip','static_ip','iptv','other'])->default('internet');
            $table->uuid('service_id')->nullable();
            $table->string('description');
            $table->integer('list_price_amount');
            $table->integer('sort_order')->default(0);
            $table->foreign('bundle_plan_id')->references('id')->on('bundle_plans')->onDelete('cascade');
            $table->index(['bundle_plan_id','sort_order']);
        });
    }
    public function down(): void { Schema::dropIfExists('bundle_plan_items'); }
};
