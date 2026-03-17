<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('technician_positions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('accuracy_meters')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['technician_id', 'recorded_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('technician_positions'); }
};
