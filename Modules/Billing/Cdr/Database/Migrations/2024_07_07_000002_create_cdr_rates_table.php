<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('cdr_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tariff_plan_id')->constrained('cdr_tariff_plans')->cascadeOnDelete();
            $table->string('prefix', 20);
            $table->string('destination_name', 80);
            $table->string('category', 20)->default('national');
            $table->unsignedInteger('rate_per_minute_cents');
            $table->unsignedInteger('connection_fee_cents')->default(0);
            $table->unsignedSmallInteger('billing_interval_seconds')->default(60);
            $table->boolean('active')->default(true);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamps();
            $table->index(['tariff_plan_id', 'prefix']);
        });
    }
    public function down(): void { Schema::dropIfExists('cdr_rates'); }
};
