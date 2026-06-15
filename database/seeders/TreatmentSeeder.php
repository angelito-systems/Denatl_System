<?php

namespace Database\Seeders;

use App\Models\Treatment;
use Illuminate\Database\Seeder;

class TreatmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $treatments = [
            ['name' => 'Consulta General', 'category' => 'General', 'base_price' => 50.00, 'estimated_duration_minutes' => 30],
            ['name' => 'Limpieza Dental', 'category' => 'General', 'base_price' => 120.00, 'estimated_duration_minutes' => 45],
            ['name' => 'Curación Simple', 'category' => 'General', 'base_price' => 150.00, 'estimated_duration_minutes' => 45],
            ['name' => 'Extracción Simple', 'category' => 'Cirugía', 'base_price' => 200.00, 'estimated_duration_minutes' => 60],
            ['name' => 'Extracción Tercera Molar', 'category' => 'Cirugía', 'base_price' => 450.00, 'estimated_duration_minutes' => 90],
            ['name' => 'Endodoncia Unirradicular', 'category' => 'Endodoncia', 'base_price' => 350.00, 'estimated_duration_minutes' => 90],
            ['name' => 'Ortodoncia Brackets Metálicos', 'category' => 'Ortodoncia', 'base_price' => 1500.00, 'estimated_duration_minutes' => 60],
            ['name' => 'Blanqueamiento Dental', 'category' => 'Estética', 'base_price' => 400.00, 'estimated_duration_minutes' => 60],
        ];

        foreach ($treatments as $treatment) {
            Treatment::create($treatment);
        }
    }
}
