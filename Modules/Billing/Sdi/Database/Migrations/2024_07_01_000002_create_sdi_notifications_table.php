<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdi_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transmission_id')->constrained('sdi_transmissions')->cascadeOnDelete();
            $table->string('notification_type', 5); // RC/MC/NS/EC/AT/DT/SF
            $table->timestamp('received_at');
            $table->text('raw_payload');
            $table->boolean('processed')->default(false);
            $table->timestamps();

            $table->index(['transmission_id', 'processed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdi_notifications');
    }
};
