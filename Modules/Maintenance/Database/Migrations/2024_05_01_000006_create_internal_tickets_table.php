<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ticket interni per helpdesk/operativo aziendale.
 * Distinti dai trouble_tickets (guasti verso carrier) —
 * questi riguardano processi interni: approvvigionamento, formazione, HR, IT interno.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('requested_by')->index();   // user_id richiedente
            $table->unsignedBigInteger('assigned_to')->nullable(); // user_id assegnatario

            $table->string('ticket_number', 20)->unique();
            $table->string('category', 50);  // it_support, hr, procurement, facilities, other
            $table->string('title');
            $table->text('description');

            $table->string('status', 30)->default('open');    // open, in_progress, resolved, closed
            $table->string('priority', 20)->default('medium');

            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_tickets');
    }
};
