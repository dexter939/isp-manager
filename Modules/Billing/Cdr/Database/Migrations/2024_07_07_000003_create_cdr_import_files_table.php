<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('cdr_import_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('format', 20)->default('generic');
            $table->timestamp('imported_at')->nullable();
            $table->unsignedInteger('records_imported')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cdr_import_files'); }
};
