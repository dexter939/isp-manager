<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radius_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->string('name', 60)->unique();              // es. FTTH_1G, FTTC_200M
            $table->string('vendor', 20)->default('mikrotik'); // mikrotik, cisco, generic

            // Banda normale
            $table->unsignedInteger('rate_dl_kbps');           // download kbps
            $table->unsignedInteger('rate_ul_kbps');           // upload kbps

            // Banda walled garden (sospensione morosità)
            $table->unsignedInteger('walled_dl_kbps')->default(128);
            $table->unsignedInteger('walled_ul_kbps')->default(128);

            // Attributi Mikrotik
            $table->string('mikrotik_rate_limit', 30)->nullable();
            // es. "1G/1G" o "200M/20M 300M/30M 8/8 20 30/30"

            // Attributi Cisco
            $table->string('cisco_qos_policy_in', 60)->nullable();
            $table->string('cisco_qos_policy_out', 60)->nullable();

            // Pool IP
            $table->string('address_pool', 60)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radius_profiles');
    }
};
