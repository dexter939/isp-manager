<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessioni RADIUS (accounting).
 * Decreto Pisanu (D.Lgs 196/2003): conservare per 6 anni.
 * Campi obbligatori: username, NAS-IP, Framed-IP, Session-Id, Start, Stop, Octets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radius_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('radius_user_id')->nullable();

            // Campi RADIUS Accounting (Decreto Pisanu)
            $table->string('username', 64)->index();
            $table->string('nas_ip', 45);                      // NAS-IP-Address
            $table->string('nas_port_id', 64)->nullable();     // NAS-Port-Id
            $table->string('framed_ip', 45)->nullable();       // Framed-IP-Address
            $table->string('framed_ipv6', 45)->nullable();     // Framed-IPv6-Prefix
            $table->string('acct_session_id', 64)->index();    // Acct-Session-Id
            $table->string('acct_unique_id', 64)->nullable();

            $table->timestamp('acct_start')->nullable()->index();   // Acct-Start-Time
            $table->timestamp('acct_stop')->nullable();             // Acct-Stop-Time
            $table->unsignedInteger('acct_session_time')->default(0); // secondi

            // Traffico (Decreto Pisanu)
            $table->unsignedBigInteger('acct_input_octets')->default(0);  // download bytes
            $table->unsignedBigInteger('acct_output_octets')->default(0); // upload bytes

            $table->string('acct_terminate_cause', 32)->nullable();
            $table->string('service_type', 32)->nullable();
            $table->string('calling_station_id', 64)->nullable();   // MAC del CPE
            $table->string('called_station_id', 64)->nullable();

            // Retention marker (non cancellare prima di 6 anni)
            $table->date('retention_until')->nullable()->index();

            $table->timestamps();

            $table->index(['tenant_id', 'acct_start']);
            $table->index(['username', 'acct_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radius_sessions');
    }
};
