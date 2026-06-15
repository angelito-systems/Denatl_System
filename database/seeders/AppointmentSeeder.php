<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = Patient::all();
        $dentists = User::role(['Dentista', 'Administrador'])->get();

        if ($patients->count() === 0 || $dentists->count() === 0) {
            return;
        }

        for ($i = 0; $i < 50; $i++) {
            Appointment::factory()->create([
                'patient_id' => $patients->random()->id,
                'dentist_id' => $dentists->random()->id,
            ]);
        }
    }
}
