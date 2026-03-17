<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('sku', 100)->unique();
            $table->string('name');
            $table->string('category', 50)->nullable();  // ont, router, cable, splitter, other
            $table->text('description')->nullable();

            $table->string('unit', 20)->default('pcs');    // pcs, mt, kg
            $table->integer('quantity')->default(0);
            $table->integer('quantity_reserved')->default(0);  // allocated to pending installs
            $table->integer('reorder_threshold')->default(0);  // alert when stock <= threshold

            $table->decimal('unit_cost', 10, 2)->nullable();   // costo di acquisto
            $table->string('supplier', 100)->nullable();
            $table->string('location', 100)->nullable();       // magazzino / shelf

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
