<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('portal_email')->nullable()->unique()->after('iban')
                ->comment('Email usata per accesso al portale agenti (null = accesso non abilitato)');
            $table->string('portal_password')->nullable()->after('portal_email');
            $table->string('portal_remember_token', 100)->nullable()->after('portal_password');
            $table->timestamp('portal_last_login_at')->nullable()->after('portal_remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn([
                'portal_email',
                'portal_password',
                'portal_remember_token',
                'portal_last_login_at',
            ]);
        });
    }
};
