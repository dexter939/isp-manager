<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('field_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intervention_id')->constrained('field_interventions')->cascadeOnDelete();
            $table->string('description');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('field_activities'); }
};
