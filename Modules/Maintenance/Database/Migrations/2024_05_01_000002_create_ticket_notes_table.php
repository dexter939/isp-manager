<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();  // null = system/AI

            $table->text('body');
            $table->string('type', 30)->default('comment');  // comment, status_change, assignment, system
            $table->boolean('is_internal')->default(false);  // not visible to customer
            $table->boolean('is_ai_generated')->default(false);

            $table->json('metadata')->nullable();  // cambio stato, allegati, ecc.

            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('trouble_tickets')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_notes');
    }
};
