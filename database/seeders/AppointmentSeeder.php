<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Appointment;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = \App\Models\Patient::all();
        $dentists = \App\Models\User::role(['Dentista', 'Administrador'])->get();

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
