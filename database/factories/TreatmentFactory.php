<?php

namespace Database\Factories;

use App\Models\Treatment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Treatment>
 */
class TreatmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement(['General', 'Ortodoncia', 'Endodoncia', 'Cirugía', 'Estética']),
            'base_price' => $this->faker->randomFloat(2, 50, 1000),
            'estimated_duration_minutes' => $this->faker->randomElement([15, 30, 45, 60, 90, 120]),
        ];
    }
}
