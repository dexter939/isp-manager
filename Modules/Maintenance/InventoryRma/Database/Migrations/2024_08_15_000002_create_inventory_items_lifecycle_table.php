<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        // Only create if table doesn't already exist or add lifecycle columns
        if (!Schema::hasColumn('inventory_items', 'model_id')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->uuid('model_id')->nullable()->after('id');
                $table->string('serial_number')->nullable()->unique()->after('model_id');
                $table->string('mac_address')->nullable()->after('serial_number');
                $table->enum('lifecycle_status', ['in_stock','assigned_vehicle','deployed','rma_pending','rma_in_transit','rma_approved','replaced','decommissioned'])->default('in_stock')->after('mac_address');
                $table->enum('location_type', ['warehouse','vehicle','customer','supplier','lost'])->default('warehouse')->after('lifecycle_status');
                $table->uuid('customer_id')->nullable()->after('location_type');
                $table->uuid('contract_id')->nullable()->after('customer_id');
                $table->timestamp('deployed_at')->nullable()->after('contract_id');
                $table->timestamp('rma_opened_at')->nullable();
                $table->string('rma_reference')->nullable();
                $table->string('rma_reason')->nullable();
            });
        }
    }
    public function down(): void {}
};
