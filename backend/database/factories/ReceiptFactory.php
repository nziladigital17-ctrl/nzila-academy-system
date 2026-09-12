<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'receipt_number' => 'REC-1-' . now()->year . '-' . fake()->unique()->numerify('####'),
            'issued_at' => now(),
            'status' => 'active',
        ];
    }
}
