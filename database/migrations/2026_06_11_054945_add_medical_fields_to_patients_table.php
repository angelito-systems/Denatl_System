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
        Schema::table('patients', function (Blueprint $table) {
            $table->string('occupation')->nullable()->after('email');
            $table->string('emergency_name')->nullable()->after('occupation');
            $table->string('emergency_phone')->nullable()->after('emergency_name');
            $table->string('blood_type')->nullable()->after('emergency_phone');
            $table->text('allergies')->nullable()->after('blood_type');
            $table->text('medical_notes')->nullable()->after('allergies');
            $table->text('family_history')->nullable()->after('medical_notes');
            $table->text('current_medication')->nullable()->after('family_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'occupation',
                'emergency_name',
                'emergency_phone',
                'blood_type',
                'allergies',
                'medical_notes',
                'family_history',
                'current_medication',
            ]);
        });
    }
};
