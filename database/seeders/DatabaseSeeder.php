<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ImageCategorySeeder::class,
            // Las siguientes clases generan datos falsos usando Faker.
            // En el entorno de produccion no queremos pacientes ni pagos falsos.
            // PatientSeeder::class,
            // AppointmentSeeder::class,
            // TreatmentSeeder::class,
            // PaymentSeeder::class,
        ]);
    }
}
