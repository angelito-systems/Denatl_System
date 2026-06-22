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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['Encendida', 'Apagada'])->default('Apagada');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('message');
            $table->string('target_audience')->nullable();
            $table->time('send_time')->nullable();
            $table->string('frequency')->nullable();
            $table->string('channel')->default('whatsapp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
