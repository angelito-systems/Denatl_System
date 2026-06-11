<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // ortodoncia, implantes, consentimiento, externo
            $table->string('name');
            $table->string('status')->default('Borrador'); // Borrador, Firmado, Subido
            $table->timestamp('signed_at')->nullable();
            $table->longText('signature')->nullable(); // Store base64 signature image
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
