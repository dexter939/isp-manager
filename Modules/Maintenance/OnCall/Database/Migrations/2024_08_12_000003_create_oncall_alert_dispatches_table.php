<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('oncall_alert_dispatches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('monitoring_alert_id');
            $table->uuid('slot_id');
            $table->uuid('user_id');
            $table->smallInteger('level')->default(1);
            $table->timestamp('notified_at')->useCurrent();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->enum('channel', ['sms','email','whatsapp','push'])->default('email');
            $table->enum('status', ['pending','acknowledged','escalated','expired'])->default('pending');
            $table->index(['monitoring_alert_id','status']);
            $table->index(['user_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('oncall_alert_dispatches'); }
};
