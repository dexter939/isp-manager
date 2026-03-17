<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('contracts', function (Blueprint $table) {
            $table->boolean('proforma_mode')->default(false)->after('status');
            $table->boolean('invoice_whatsapp_enabled')->default(false)->after('proforma_mode');
            $table->string('invoice_whatsapp_number')->nullable()->after('invoice_whatsapp_enabled');
        });
    }
    public function down(): void {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['proforma_mode','invoice_whatsapp_enabled','invoice_whatsapp_number']);
        });
    }
};
