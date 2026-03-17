<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('conversation_id')->nullable()->index(); // ai_conversations

            // Meta WABA
            $table->string('waba_message_id', 100)->nullable()->unique();       // ID ritornato da Meta
            $table->string('direction', 10);                                    // inbound, outbound
            $table->string('from_number', 20);
            $table->string('to_number', 20);

            $table->string('message_type', 30)->default('text');  // text, image, document, audio, template
            $table->text('body')->nullable();
            $table->string('template_name', 100)->nullable();
            $table->json('template_params')->nullable();

            $table->string('status', 30)->default('sent');        // sent, delivered, read, failed
            $table->text('error_message')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'from_number', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
