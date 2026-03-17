<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('field_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intervention_id')->constrained('field_interventions')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('description');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('unit_cost_cents')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('field_materials'); }
};
