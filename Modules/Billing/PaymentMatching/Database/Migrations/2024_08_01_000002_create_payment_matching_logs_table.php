<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('payment_matching_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('payment_id');
            $table->uuid('rule_id')->nullable();
            $table->boolean('matched')->default(false);
            $table->uuid('invoice_id')->nullable();
            $table->string('action_taken');
            $table->jsonb('evaluation_details')->default('[]');
            $table->timestamp('created_at')->useCurrent();
            $table->index('payment_id');
            $table->index('rule_id');
        });
    }
    public function down(): void { Schema::dropIfExists('payment_matching_logs'); }
};
