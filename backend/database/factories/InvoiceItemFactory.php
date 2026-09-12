<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 10000, 50000);
        return [
            'invoice_id' => Invoice::factory(),
            'description' => 'Propina Mensal',
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'total' => $unitPrice,
        ];
    }
}
