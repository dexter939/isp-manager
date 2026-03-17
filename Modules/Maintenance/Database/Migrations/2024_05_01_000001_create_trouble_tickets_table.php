<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trouble_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('contract_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index(); // user_id

            // Identificativi
            $table->string('ticket_number', 20)->unique();
            $table->string('title');
            $table->text('description');

            // Classificazione
            $table->string('status', 30)->default('open');         // TicketStatus
            $table->string('priority', 20)->default('medium');     // TicketPriority
            $table->string('type', 50)->nullable();                // assurance, billing, provisioning, other
            $table->string('source', 30)->default('manual');       // manual, whatsapp, email, phone, ai

            // Carrier / infrastruttura
            $table->string('carrier', 30)->nullable();             // openfiber, fibercop, internal
            $table->string('carrier_ticket_id', 100)->nullable();  // ID ticket lato carrier

            // Tempistiche SLA
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('due_at')->nullable();               // SLA deadline

            $table->text('resolution_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'priority']);
            $table->index(['tenant_id', 'customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trouble_tickets');
    }
};
