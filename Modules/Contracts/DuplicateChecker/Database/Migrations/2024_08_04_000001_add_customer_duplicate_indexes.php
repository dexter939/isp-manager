<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        DB::statement("CREATE INDEX IF NOT EXISTS idx_customers_email ON customers (email) WHERE deleted_at IS NULL");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers (phone) WHERE deleted_at IS NULL");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_customers_mobile ON customers (mobile) WHERE deleted_at IS NULL");
    }
    public function down(): void {
        DB::statement("DROP INDEX IF EXISTS idx_customers_email");
        DB::statement("DROP INDEX IF EXISTS idx_customers_phone");
        DB::statement("DROP INDEX IF EXISTS idx_customers_mobile");
    }
};
