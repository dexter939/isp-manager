<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdi_transmissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('channel', 20)->default('aruba'); // aruba|pec
            $table->string('status', 30)->default('pending');
            $table->string('filename', 100);
            $table->text('xml_content');
            $table->string('xml_hash', 64); // SHA256
            $table->timestamp('sent_at')->nullable();
            $table->string('notification_code', 5)->nullable(); // RC/MC/NS/EC/AT/DT/SF
            $table->text('last_error')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('conservazione_expires_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'retry_count']);
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdi_transmissions');
    }
};
