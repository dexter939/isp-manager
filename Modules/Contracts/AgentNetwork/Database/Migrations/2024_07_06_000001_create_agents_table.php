<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->string('code', 20)->unique();
            $table->string('business_name');
            $table->string('piva', 11)->nullable();
            $table->string('codice_fiscale', 16);
            $table->string('iban', 34);
            $table->string('status', 20)->default('active');
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void { Schema::dropIfExists('agents'); }
};
