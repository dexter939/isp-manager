<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sepa_mandates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id');

            // Identificativi mandato SDD ISO 20022
            $table->string('mandate_id', 35)->unique();        // es. MND-000001
            $table->date('signed_at');
            $table->string('sequence_type', 4)->default('RCUR');
            // FRST = primo addebito, RCUR = ricorrente, OOFF = una tantum, FNAL = finale

            // IBAN del debitore (cifrato)
            $table->text('iban');                              // encrypted
            $table->text('bic')->nullable();
            $table->text('account_holder');                    // encrypted (nome intestatario)

            // Identificativo creditore SDD
            $table->string('creditor_id', 35)->nullable();     // es. IT47XXX...

            // Stato
            $table->string('status', 20)->default('active');
            // active, suspended, cancelled, revoked

            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason', 10)->nullable(); // MS02, MD01...

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sepa_mandates');
    }
};
