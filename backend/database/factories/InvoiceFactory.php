<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 10000, 50000);
        return [
            'school_id' => School::factory(),
            'student_id' => Student::factory(),
            'invoice_number' => 'FAT-1-' . now()->year . '-' . fake()->unique()->numerify('####'),
            'due_date' => now()->addDays(30),
            'subtotal' => $amount,
            'discount' => 0,
            'total' => $amount,
            'status' => 'pending',
            'issued_by' => null,
        ];
    }
}
