<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('cdr_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('import_file_id')->nullable()->constrained('cdr_import_files')->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('caller_number', 20);
            $table->string('called_number', 30);
            $table->string('called_prefix', 20)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->string('category', 20)->nullable();
            $table->unsignedInteger('rate_per_minute_cents')->nullable();
            $table->unsignedInteger('connection_fee_cents')->default(0);
            $table->unsignedInteger('total_cost_cents')->nullable();
            $table->boolean('billed')->default(false);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamps();
            $table->index(['customer_id', 'billed', 'start_time']);
            $table->index(['billed', 'start_time']);
        });
    }
    public function down(): void { Schema::dropIfExists('cdr_records'); }
};
