<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (Schema::hasColumn('appointments', 'patient_treatment_id')) {
    Schema::table('appointments', function (Blueprint $table) {
        $table->dropColumn('patient_treatment_id');
    });
    echo "Column dropped successfully.\n";
} else {
    echo "Column does not exist.\n";
}
