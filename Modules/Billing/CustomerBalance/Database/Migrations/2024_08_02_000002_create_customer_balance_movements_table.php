<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('customer_balance_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customer_id');
            $table->enum('type', ['payment','invoice','credit_note','adjustment','opening_balance']);
            $table->integer('amount_amount');
            $table->char('amount_currency', 3)->default('EUR');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->uuid('reference_id')->nullable();
            $table->string('description');
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('customer_id');
            $table->index(['customer_id','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('customer_balance_movements'); }
};
