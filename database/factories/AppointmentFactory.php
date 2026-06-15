<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Appointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('now', '+2 months');
        $start_time = $this->faker->dateTimeBetween($date->format('Y-m-d 08:00:00'), $date->format('Y-m-d 18:00:00'));

        return [
            'patient_id' => Patient::factory(),
            'dentist_id' => User::factory(), // Assuming User factory can create a dentist
            'date' => $date->format('Y-m-d'),
            'start_time' => $start_time->format('H:i'),
            'duration' => $this->faker->randomElement([30, 45, 60]),
            'treatment' => $this->faker->randomElement(['Limpieza', 'Extracción', 'Endodoncia', 'Consulta General', 'Ortodoncia']),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'cancelled', 'completed']),
            'notes' => $this->faker->optional(0.7)->sentence(),
        ];
    }
}
