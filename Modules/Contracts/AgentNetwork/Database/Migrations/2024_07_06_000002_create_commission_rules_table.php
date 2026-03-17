<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->cascadeOnDelete();
            $table->string('offer_type', 20)->nullable();
            $table->string('contract_type', 30)->nullable();
            $table->string('rate_type', 20)->default('percentage'); // percentage|fixed
            $table->unsignedInteger('rate_value_cents')->nullable();
            $table->decimal('rate_percentage', 5, 2)->nullable();
            $table->unsignedSmallInteger('priority')->default(0);
            $table->boolean('active')->default(true);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'active', 'priority']);
        });
    }

    public function down(): void { Schema::dropIfExists('commission_rules'); }
};
