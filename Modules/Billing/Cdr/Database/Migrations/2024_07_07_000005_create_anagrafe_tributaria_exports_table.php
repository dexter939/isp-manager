<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('anagrafe_tributaria_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->string('export_type', 20)->default('full');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedBigInteger('total_amount_cents')->default(0);
            $table->string('xml_path');
            $table->timestamp('generated_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('anagrafe_tributaria_exports'); }
};
