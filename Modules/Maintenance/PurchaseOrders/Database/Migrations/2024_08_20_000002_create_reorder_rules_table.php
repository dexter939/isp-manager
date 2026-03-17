<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reorder_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_model_id');
            $table->uuid('supplier_id');
            $table->integer('min_stock_quantity');
            $table->integer('reorder_quantity');
            $table->boolean('auto_order')->default(false);
            $table->timestamps();

            $table->unique('inventory_model_id');
            $table->foreign('supplier_id')->references('id')->on('suppliers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_rules');
    }
};
