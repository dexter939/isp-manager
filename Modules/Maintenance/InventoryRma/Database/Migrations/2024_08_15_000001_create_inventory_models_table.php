<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('brand');
            $table->string('model');
            $table->enum('type', ['router','ont','switch','antenna','cable','other'])->default('router');
            $table->smallInteger('default_warranty_months')->default(24);
            $table->uuid('supplier_id')->nullable();
            $table->timestamps();
            $table->index(['brand','model']);
        });
    }
    public function down(): void { Schema::dropIfExists('inventory_models'); }
};
