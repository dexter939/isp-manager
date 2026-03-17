<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('network_site_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('network_site_id');
            $table->enum('document_type', ['contract','photo','diagram','technical','other'])->default('other');
            $table->string('filename');
            $table->string('file_path');
            $table->uuid('uploaded_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('network_site_id')->references('id')->on('network_sites')->onDelete('cascade');
            $table->index('network_site_id');
        });
    }
    public function down(): void { Schema::dropIfExists('network_site_documents'); }
};
