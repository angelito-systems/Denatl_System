<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = \App\Models\Patient::all();

        if ($patients->count() > 0) {
            foreach ($patients as $patient) {
                // Generate 1 to 3 payments per patient
                \App\Models\Payment::factory(rand(1, 3))->create([
                    'patient_id' => $patient->id
                ]);
            }
        }
    }
}
