<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('contract_id')->nullable()->index();
            $table->unsignedBigInteger('ticket_id')->nullable()->index();  // ticket generato da questa conversazione

            $table->string('channel', 30)->default('internal'); // internal, whatsapp, voice, email
            $table->string('purpose', 50)->default('support');  // support, ticket_draft, diagnostics
            $table->string('status', 30)->default('active');    // active, completed, archived

            $table->string('model', 100)->nullable();           // es. claude-sonnet-4-6
            $table->integer('total_input_tokens')->default(0);
            $table->integer('total_output_tokens')->default(0);

            $table->json('metadata')->nullable();               // contesto aggiuntivo per il sistema

            $table->timestamps();
            $table->index(['tenant_id', 'channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
