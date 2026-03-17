<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('field_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intervention_id')->constrained('field_interventions')->cascadeOnDelete();
            $table->string('signer_type', 20)->default('customer');
            $table->string('signer_name');
            $table->string('signature_path');
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_verified_at')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('field_signatures'); }
};
