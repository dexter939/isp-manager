<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dunning_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('dunning_cases')->cascadeOnDelete();
            $table->integer('step_index');
            $table->string('action'); // email|sms|whatsapp|suspend|terminate
            $table->timestamp('executed_at');
            $table->string('result')->nullable(); // success|failed|skipped
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_steps');
    }
};
