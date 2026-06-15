<?php

namespace Database\Seeders;

use App\Models\ImageCategory;
use Illuminate\Database\Seeder;

class ImageCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Rayos X',
            'Radiografía Panorámica',
            'Periapical',
            'Fotografía Inicial',
            'Fotografía de Seguimiento',
            'Fotografía Final',
            'Tomografía',
            'Escaneo Dental',
            'Documento Clínico',
            'Otro',
        ];

        foreach ($categories as $category) {
            ImageCategory::firstOrCreate(['name' => $category]);
        }
    }
}
