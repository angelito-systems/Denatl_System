<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Patient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dni' => $this->faker->unique()->numerify('########'), // 8 digits in Peru
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName().' '.$this->faker->lastName(),
            'phone' => $this->faker->numerify('9########'),
            'email' => $this->faker->unique()->safeEmail(),
            'date_of_birth' => $this->faker->dateTimeBetween('-80 years', '-5 years')->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['Masculino', 'Femenino']),
            'address' => $this->faker->address(),
        ];
    }
}
