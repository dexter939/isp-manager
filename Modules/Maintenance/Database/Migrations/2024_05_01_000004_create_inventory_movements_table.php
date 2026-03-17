<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('inventory_item_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();      // operatore
            $table->unsignedBigInteger('ticket_id')->nullable();    // movimentazione collegata a ticket

            $table->string('type', 30);          // in, out, transfer, adjustment
            $table->integer('quantity');         // positivo = entrata, negativo = uscita
            $table->integer('quantity_before');  // stock prima del movimento
            $table->integer('quantity_after');   // stock dopo il movimento

            $table->string('reference', 100)->nullable();  // DDT, ordine fornitore, ecc.
            $table->text('notes')->nullable();

            $table->timestamp('moved_at')->useCurrent();
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
            $table->index(['tenant_id', 'inventory_item_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
