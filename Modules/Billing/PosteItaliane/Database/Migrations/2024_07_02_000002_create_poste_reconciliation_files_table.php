<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poste_reconciliation_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->timestamp('imported_at');
            $table->unsignedInteger('records_total')->default(0);
            $table->unsignedInteger('records_matched')->default(0);
            $table->unsignedInteger('records_unmatched')->default(0);
            $table->text('raw_content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poste_reconciliation_files');
    }
};
