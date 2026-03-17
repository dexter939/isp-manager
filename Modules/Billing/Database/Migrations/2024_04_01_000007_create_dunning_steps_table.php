<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dunning_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('contract_id');

            $table->unsignedSmallInteger('step');
            // 1=reminder1(D+10), 2=reminder2(D+15), 3=reminder3(D+20),
            // 4=sospensione(D+25), 5=retry_sdd(D+30), 6=cessazione(D+45)

            $table->string('action', 50);
            // email_reminder, sms_reminder, whatsapp_reminder, suspension, retry_sdd, termination

            $table->string('status', 20)->default('pending');
            // pending, executed, skipped, failed

            $table->timestamp('scheduled_at');
            $table->timestamp('executed_at')->nullable();
            $table->text('result_log')->nullable();

            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->restrictOnDelete();
            $table->index(['tenant_id', 'status', 'scheduled_at']);
            $table->index(['invoice_id', 'step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_steps');
    }
};
