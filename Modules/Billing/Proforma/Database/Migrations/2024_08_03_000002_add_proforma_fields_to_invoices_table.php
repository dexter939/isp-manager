<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('invoice_type', ['proforma','invoice','credit_note'])->default('invoice')->after('status');
            $table->uuid('proforma_id')->nullable()->after('invoice_type');
            $table->timestamp('converted_at')->nullable()->after('proforma_id');
            $table->index('invoice_type');
            $table->index('proforma_id');
        });
    }
    public function down(): void {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['invoice_type','proforma_id','converted_at']);
        });
    }
};
