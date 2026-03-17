<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->index();

            $table->string('role', 20);         // user, assistant, system
            $table->text('content');
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);

            $table->string('stop_reason', 50)->nullable();  // end_turn, max_tokens, tool_use
            $table->json('tool_use')->nullable();           // tool calls / results se abilitati

            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('ai_conversations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
