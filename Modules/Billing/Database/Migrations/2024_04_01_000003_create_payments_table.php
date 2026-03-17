<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('customer_id');

            $table->string('method', 30);
            // sdd, stripe, bonifico, contanti, nota_credito

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');

            $table->string('status', 30)->default('pending');
            // pending, completed, failed, refunded, disputed

            // Stripe
            $table->string('stripe_payment_intent_id', 100)->nullable()->index();
            $table->string('stripe_charge_id', 100)->nullable();
            $table->text('stripe_error')->nullable();

            // SDD
            $table->string('sepa_mandate_id')->nullable();
            $table->string('sepa_end_to_end_id', 50)->nullable()->unique();
            $table->string('sepa_return_code', 10)->nullable(); // AC04, AM04, MD01...
            $table->unsignedBigInteger('sepa_file_id')->nullable();

            // Generale
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            $table->index(['invoice_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
