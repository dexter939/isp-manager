<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('oncall_schedule_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('schedule_id');
            $table->uuid('user_id');
            $table->smallInteger('level')->default(1);
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            $table->string('repeat_rule')->nullable();
            $table->timestamps();
            $table->foreign('schedule_id')->references('id')->on('oncall_schedules')->onDelete('cascade');
            $table->index(['schedule_id','level']);
            $table->index(['start_datetime','end_datetime']);
        });
    }
    public function down(): void { Schema::dropIfExists('oncall_schedule_slots'); }
};
