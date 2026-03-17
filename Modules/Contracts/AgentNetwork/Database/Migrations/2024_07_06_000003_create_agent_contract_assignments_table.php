<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_contract_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['agent_id', 'contract_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('agent_contract_assignments'); }
};
