<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('route_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('technician_id');
            $table->date('plan_date');
            $table->decimal('start_lat', 10, 8);
            $table->decimal('start_lon', 11, 8);
            $table->string('start_address')->nullable();
            $table->decimal('total_distance_km', 8, 3)->nullable();
            $table->integer('total_duration_minutes')->nullable();
            $table->jsonb('optimized_order')->default('[]');
            $table->enum('status', ['draft','active','completed'])->default('draft');
            $table->timestamps();
            $table->unique(['technician_id','plan_date']);
            $table->index(['technician_id','plan_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('route_plans'); }
};
