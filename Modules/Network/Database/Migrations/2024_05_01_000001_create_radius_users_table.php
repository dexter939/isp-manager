<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Utenti FreeRADIUS.
 * Backend: rlm_sql su PostgreSQL condiviso (stessa istanza).
 * Conforme a Decreto Pisanu (D.Lgs 196/2003) — data retention 6 anni.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radius_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('contract_id');

            // Credenziali PPPoE / IPoE
            $table->string('username', 64)->unique();          // es. cliente@isp.it o MAC
            $table->text('password_hash');                     // bcrypt — FreeRADIUS rlm_sql verifica via Cleartext-Password o CHAP
            $table->string('auth_type', 10)->default('pap');   // pap, chap, mschapv2

            // Profilo di banda applicato
            $table->unsignedBigInteger('radius_profile_id')->nullable();

            $table->string('status', 20)->default('active');
            // active, suspended (walled garden), disabled

            // Walled Garden — quando sospeso
            $table->boolean('walled_garden')->default(false);
            $table->string('walled_garden_token', 64)->nullable()->unique();

            // NAS info (popolato al login via accounting)
            $table->string('nas_ip', 45)->nullable();          // IPv4/IPv6
            $table->string('framed_ip', 45)->nullable();       // IP assegnato
            $table->string('acct_session_id', 64)->nullable(); // per CoA

            $table->timestamp('last_auth_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('contract_id')->references('id')->on('contracts')->restrictOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radius_users');
    }
};
