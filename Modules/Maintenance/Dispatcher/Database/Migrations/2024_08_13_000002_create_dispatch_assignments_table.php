<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('dispatch_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('intervention_id');
            $table->uuid('technician_id');
            $table->timestamp('scheduled_start');
            $table->timestamp('scheduled_end');
            $table->integer('estimated_duration_minutes');
            $table->integer('travel_time_minutes')->default(0);
            $table->enum('status', ['scheduled','in_progress','completed','cancelled'])->default('scheduled');
            $table->uuid('assigned_by')->nullable();
            $table->timestamps();
            $table->index(['technician_id','scheduled_start']);
            $table->index(['intervention_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('dispatch_assignments'); }
};
