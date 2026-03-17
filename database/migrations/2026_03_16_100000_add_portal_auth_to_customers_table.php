<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('portal_password')->nullable()->after('notes')
                ->comment('Hash bcrypt per accesso portale clienti (null = accesso non abilitato)');
            $table->string('portal_remember_token', 100)->nullable()->after('portal_password');
            $table->timestamp('portal_email_verified_at')->nullable()->after('portal_remember_token');
            $table->timestamp('portal_last_login_at')->nullable()->after('portal_email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'portal_password',
                'portal_remember_token',
                'portal_email_verified_at',
                'portal_last_login_at',
            ]);
        });
    }
};
