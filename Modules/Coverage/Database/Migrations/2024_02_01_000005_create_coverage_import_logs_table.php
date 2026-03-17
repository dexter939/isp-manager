<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coverage_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('carrier', 20)->comment('fibercop, openfiber');
            $table->string('source_file', 200)->comment('Path/nome file importato');
            $table->string('status', 20)->default('running')
                ->comment('running, completed, failed');
            $table->unsignedInteger('rows_processed')->default(0);
            $table->unsignedInteger('rows_inserted')->default(0);
            $table->unsignedInteger('rows_updated')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['carrier', 'status'], 'cov_import_carrier_status_idx');
            $table->index('started_at', 'cov_import_started_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coverage_import_logs');
    }
};
