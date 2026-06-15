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
        Schema::create('raffle_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('phone_number');
            $table->boolean('is_winner')->default(false);
            $table->foreignId('raffle_prize_id')->nullable()->constrained('raffle_prizes')->onDelete('set null');
            $table->timestamps();

            // Un teléfono solo puede participar una vez por sorteo
            $table->unique(['raffle_id', 'phone_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raffle_participants');
    }
};
