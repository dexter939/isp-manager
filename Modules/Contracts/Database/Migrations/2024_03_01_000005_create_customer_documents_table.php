<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type', 50)
                ->comment('contract, privacy, id_card, mandate_sepa, other');
            $table->string('name', 200)->comment('Nome file originale');
            $table->string('disk', 30)->default('s3')
                ->comment('Disco storage (s3 = MinIO)');
            $table->string('path', 500)->comment('Path su MinIO (es: contracts/2024/...)');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->string('sha256', 64)->nullable()
                ->comment('Hash integrità documento');

            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'type'], 'cust_docs_customer_type_idx');
            $table->index('contract_id', 'cust_docs_contract_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
    }
};
