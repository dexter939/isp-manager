<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('customer_traffic_usage', function (Blueprint $table) {
            $table->uuid('pppoe_account_id');
            $table->smallInteger('period_year');
            $table->smallInteger('period_month');
            $table->bigInteger('bytes_download')->default(0);
            $table->bigInteger('bytes_upload')->default(0);
            $table->bigInteger('bytes_total')->default(0);
            $table->integer('cap_gb')->nullable();
            $table->boolean('fup_triggered')->default(false);
            $table->timestamp('fup_triggered_at')->nullable();
            $table->integer('topup_gb_added')->default(0);
            $table->timestamp('last_updated')->useCurrent();
            $table->primary(['pppoe_account_id','period_year','period_month']);
            $table->index(['pppoe_account_id','period_year','period_month']);
        });
    }
    public function down(): void { Schema::dropIfExists('customer_traffic_usage'); }
};
