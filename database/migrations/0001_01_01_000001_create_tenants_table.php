<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique()->comment('Identificativo URL-safe del tenant');
            $table->string('domain')->nullable()->unique()->comment('Dominio personalizzato (es: portale.isp.it)');
            $table->jsonb('settings')->default('{}')->comment('Configurazioni ISP: logo, colori, contatti, ...');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // Aggiungi tenant_id alla tabella users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            $table->index('tenant_id');
        });

        // Tenant di default per il progetto
        DB::table('tenants')->insert([
            'name'       => 'Default ISP',
            'slug'       => 'default',
            'settings'   => json_encode([]),
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::dropIfExists('tenants');
    }
};
