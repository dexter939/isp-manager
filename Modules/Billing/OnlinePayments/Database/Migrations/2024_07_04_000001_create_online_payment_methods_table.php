<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('gateway', 20); // stripe|nexi
            $table->string('external_customer_id')->nullable();
            $table->string('external_method_id');
            $table->string('card_brand', 20)->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('card_expiry', 7)->nullable(); // MM/YY
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['customer_id', 'gateway', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payment_methods');
    }
};
