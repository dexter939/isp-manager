<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('payment_matching_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('priority')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->jsonb('criteria');
            $table->enum('action', ['match_oldest','match_newest','add_to_credit','skip'])->default('match_oldest');
            $table->string('action_note')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'priority']);
        });
    }
    public function down(): void { Schema::dropIfExists('payment_matching_rules'); }
};
