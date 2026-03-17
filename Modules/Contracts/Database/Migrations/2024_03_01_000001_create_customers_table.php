<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Tipo soggetto
            $table->string('type', 10)->comment('privato, azienda');

            // Anagrafica
            $table->string('ragione_sociale')->nullable()->comment('Per aziende');
            $table->string('nome', 100)->nullable()->comment('Per privati');
            $table->string('cognome', 100)->nullable()->comment('Per privati');

            // Dati fiscali — CIFRATI a riposo
            $table->text('codice_fiscale')->nullable()->comment('[encrypted] CF persona fisica o giuridica');
            $table->text('piva')->nullable()->comment('[encrypted] Partita IVA (solo aziende)');

            // Contatti
            $table->string('email')->nullable();
            $table->string('pec')->nullable()->comment('PEC obbligatoria per aziende PA');
            $table->string('telefono', 20)->nullable();
            $table->string('cellulare', 20)->nullable();

            // Indirizzo fatturazione
            $table->jsonb('indirizzo_fatturazione')->nullable()
                ->comment('{"via","civico","comune","provincia","cap","paese"}');

            // Pagamento
            $table->string('payment_method', 20)->default('bonifico')
                ->comment('sdd, carta, bonifico, contanti');
            $table->text('iban')->nullable()->comment('[encrypted] IBAN per SDD');
            $table->string('stripe_customer_id', 100)->nullable()->index();
            $table->string('sepa_mandate_id', 100)->nullable()->index();

            // Stato
            $table->string('status', 20)->default('prospect')
                ->comment('prospect, active, suspended, terminated');

            // Note interne
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status'], 'customers_tenant_status_idx');
            $table->index(['tenant_id', 'type'], 'customers_tenant_type_idx');
            $table->index('email', 'customers_email_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
