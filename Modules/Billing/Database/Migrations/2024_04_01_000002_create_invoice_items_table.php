<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');

            $table->string('description', 255);
            $table->string('type', 30)->default('canone');
            // canone, attivazione, modem, nota_credito, bollo, altro

            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(22.00);
            $table->decimal('total_net', 10, 2);              // qty * unit_price
            $table->decimal('total_tax', 10, 2);
            $table->decimal('total_gross', 10, 2);

            // Codici SDI / FatturaPA
            $table->string('natura_iva', 5)->nullable();      // N1, N2, N4 ecc.
            $table->string('codice_articolo', 30)->nullable(); // opzionale
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
