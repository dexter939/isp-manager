<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tr069_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('cpe_device_id');

            $table->string('parameter_path', 255)->index();
            // es. Device.DeviceInfo.SoftwareVersion
            //     InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ConnectionStatus

            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');     // string, int, boolean, datetime
            $table->boolean('is_writable')->default(false);
            $table->boolean('is_notification')->default(false); // notifica cambio valore

            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->foreign('cpe_device_id')
                ->references('id')->on('cpe_devices')->cascadeOnDelete();

            $table->unique(['cpe_device_id', 'parameter_path']);
            $table->index(['tenant_id', 'parameter_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tr069_parameters');
    }
};
