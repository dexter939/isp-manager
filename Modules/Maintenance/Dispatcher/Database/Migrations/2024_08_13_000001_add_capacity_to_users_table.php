<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('daily_capacity_hours', 4, 2)->default(8.0)->after('email');
            $table->jsonb('working_days')->default('[1,2,3,4,5]')->after('daily_capacity_hours');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['daily_capacity_hours','working_days']);
        });
    }
};
