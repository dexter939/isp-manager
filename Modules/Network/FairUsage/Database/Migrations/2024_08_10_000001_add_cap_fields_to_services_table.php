<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->boolean('cap_enabled')->default(false)->after('name');
                $table->integer('cap_gb')->nullable()->after('cap_enabled');
                $table->smallInteger('cap_reset_day')->default(1)->after('cap_gb');
                $table->uuid('fup_service_id')->nullable()->after('cap_reset_day');
                $table->smallInteger('fup_threshold_percent')->default(100)->after('fup_service_id');
            });
        }
    }
    public function down(): void {
        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn(['cap_enabled','cap_gb','cap_reset_day','fup_service_id','fup_threshold_percent']);
            });
        }
    }
};
