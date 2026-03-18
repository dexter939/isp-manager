<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('hardware_devices')) {
            Schema::table('hardware_devices', function (Blueprint $table) {
                $table->uuid('parent_device_id')->nullable()->after('id');
                $table->foreign('parent_device_id')->references('id')->on('hardware_devices')->onDelete('set null');
            });
        }
        if (Schema::hasTable('monitoring_alerts')) {
            Schema::table('monitoring_alerts', function (Blueprint $table) {
                $table->boolean('suppressed')->default(false)->after('status');
                $table->uuid('suppressed_by_device_id')->nullable()->after('suppressed');
                $table->string('suppressed_reason')->nullable()->after('suppressed_by_device_id');
            });
        }
    }
    public function down(): void {
        if (Schema::hasTable('hardware_devices')) {
            Schema::table('hardware_devices', function (Blueprint $table) { $table->dropForeign(['parent_device_id']); $table->dropColumn('parent_device_id'); });
        }
        if (Schema::hasTable('monitoring_alerts')) {
            Schema::table('monitoring_alerts', function (Blueprint $table) { $table->dropColumn(['suppressed','suppressed_by_device_id','suppressed_reason']); });
        }
    }
};
