<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('room')->nullable()->after('last_name');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('room')->nullable()->after('status');
            $table->string('projector_status')->nullable()->after('room'); // null, waiting, calling, in_progress, finished
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('room');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['room', 'projector_status']);
        });
    }
};
