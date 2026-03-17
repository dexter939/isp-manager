<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete()
                ->comment('NULL = template globale di default');
            $table->string('slug', 100)->comment('Identificativo evento: invoice_generated, ticket_opened, ...');
            $table->string('name', 255);
            $table->string('subject', 500);
            $table->text('body_html');
            $table->text('body_text');
            $table->boolean('is_active')->default(true);
            $table->jsonb('variables')->default('[]')->comment('Lista variabili disponibili per il template');
            $table->timestamps();

            // Tenant-specific template OR global default (one per slug per tenant)
            $table->unique(['tenant_id', 'slug']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
