<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('agent_id')->nullable();

            // Identificativi
            $table->string('number', 30)->unique();           // es. 2024/001
            $table->string('sdi_progressive', 20)->nullable(); // progressivo SDI
            $table->enum('type', ['TD01', 'TD04', 'TD07'])->default('TD01');

            // Periodo di competenza
            $table->date('period_from');
            $table->date('period_to');
            $table->date('issue_date');
            $table->date('due_date');

            // Importi (DECIMAL, mai float) — IVA esclusa
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(22.00);    // %
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('stamp_duty', 10, 2)->default(0);     // bollo €2
            $table->decimal('total', 10, 2)->default(0);

            // Stato
            $table->string('status', 30)->default('draft');
            // draft → issued → sent_sdi → paid | overdue | cancelled

            // SDI
            $table->string('sdi_message_id', 100)->nullable();
            $table->string('sdi_filename', 200)->nullable();
            $table->string('sdi_status', 50)->nullable();         // NS, MC, RC, AT, DT...
            $table->timestamp('sdi_sent_at')->nullable();
            $table->timestamp('sdi_acknowledged_at')->nullable();
            $table->text('sdi_raw_response')->nullable();

            // Storage (MinIO bucket: ispmanager-invoices)
            $table->string('pdf_path')->nullable();
            $table->string('xml_path')->nullable();

            // Pagamento
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 30)->nullable();     // sdd, stripe, bonifico, contanti

            // Metadati
            $table->jsonb('notes')->nullable();                   // note extra, split payment, ecc.
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('contract_id')->references('id')->on('contracts')->restrictOnDelete();

            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['due_date', 'status']);
            $table->index('sdi_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
