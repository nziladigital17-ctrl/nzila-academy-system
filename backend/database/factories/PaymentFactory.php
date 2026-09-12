<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'invoice_id' => Invoice::factory(),
            'amount' => fake()->randomFloat(2, 5000, 50000),
            'payment_method' => 'cash',
            'reference' => null,
            'paid_at' => now(),
            'received_by' => null,
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => null,
        ];
    }
}
