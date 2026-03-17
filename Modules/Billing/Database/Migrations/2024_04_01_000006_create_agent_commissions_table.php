<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('agent_id');
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('invoice_id')->nullable();

            $table->string('type', 30)->default('activation');
            // activation = una tantum su nuovo contratto
            // recurring  = % canone mensile (ogni fattura pagata)
            // bonus      = bonus extra manuale

            $table->decimal('base_amount', 10, 2);             // imponibile su cui calcola
            $table->decimal('rate', 5, 4)->default(0);         // es. 0.1000 = 10%
            $table->decimal('amount', 10, 2);                  // commissione calcolata
            $table->string('currency', 3)->default('EUR');

            $table->string('status', 20)->default('accrued');
            // accrued → approved → paid | cancelled

            $table->date('accrued_on');                        // data maturazione
            $table->date('paid_on')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('contract_id')->references('id')->on('contracts')->restrictOnDelete();
            $table->index(['agent_id', 'status']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commissions');
    }
};
