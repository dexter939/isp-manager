<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_quota_usage', function (Blueprint $table) {
            $table->id();
            $table->string('carrier', 50)->comment('openfiber, fibercop, fastweb, ...');
            $table->string('call_type', 100)->comment('line_testing, order_status, ticket_status, ...');
            $table->date('date')->comment('Data di riferimento quota giornaliera');
            $table->unsignedInteger('count')->default(0)->comment('Numero chiamate effettuate');
            $table->unsignedInteger('daily_limit')->default(0)->comment('Limite giornaliero configurato');
            $table->timestamps();

            // Unique: un record per carrier+call_type+giorno
            $table->unique(['carrier', 'call_type', 'date'], 'api_quota_carrier_type_date_unique');
            $table->index(['carrier', 'date'], 'api_quota_carrier_date_idx');
            $table->index('date', 'api_quota_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_quota_usage');
    }
};
