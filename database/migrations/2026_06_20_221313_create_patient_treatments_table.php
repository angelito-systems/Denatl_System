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
        Schema::create('patient_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('estimated_end_date')->nullable();
            $table->enum('status', ['Activo', 'Pausado', 'Finalizado', 'Cancelado'])->default('Activo');
            $table->text('objectives')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('dentist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_treatments');
    }
};
