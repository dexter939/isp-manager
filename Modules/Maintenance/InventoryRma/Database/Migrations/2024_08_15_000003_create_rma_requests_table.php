<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('rma_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('supplier_id')->nullable();
            $table->enum('reason', ['defective','warranty','wrong_item','other'])->default('defective');
            $table->text('description');
            $table->string('rma_reference')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->enum('resolution', ['replaced','repaired','credit','rejected'])->nullable();
            $table->uuid('replacement_item_id')->nullable();
            $table->timestamps();
            $table->index(['item_id','resolved_at']);
            $table->index(['supplier_id','resolved_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('rma_requests'); }
};
