<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => \App\Models\Patient::factory(),
            'amount' => $this->faker->randomFloat(2, 50, 1500),
            'payment_method' => $this->faker->randomElement(['Efectivo', 'Tarjeta', 'Yape', 'Transferencia']),
            'receipt_type' => $this->faker->randomElement(['Boleta', 'Factura', 'Recibo']),
            'status' => $this->faker->randomElement(['Pagado', 'Pagado', 'Pendiente']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
