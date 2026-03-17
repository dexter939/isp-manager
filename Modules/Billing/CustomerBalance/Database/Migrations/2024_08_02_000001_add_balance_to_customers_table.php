<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('balance_amount')->default(0)->after('email');
            $table->char('balance_currency', 3)->default('EUR')->after('balance_amount');
            $table->integer('opening_balance_amount')->default(0)->after('balance_currency');
            $table->date('opening_balance_date')->nullable()->after('opening_balance_amount');
            $table->string('opening_balance_note')->nullable()->after('opening_balance_date');
        });
    }
    public function down(): void {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['balance_amount','balance_currency','opening_balance_amount','opening_balance_date','opening_balance_note']);
        });
    }
};
