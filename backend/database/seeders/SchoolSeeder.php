<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        School::firstOrCreate(
            ['code' => 'DEMO-001'],
            [
                'name' => 'Escola Demonstração Nzila',
                'code' => 'DEMO-001',
                'nif' => '5000000001',
                'address' => 'Rua da Demonstração, nº 1, Luanda',
                'province' => 'Luanda',
                'phone' => '+244 923 000 001',
                'email' => 'demo@nzila-academy.ao',
                'is_active' => true,
            ]
        );
    }
}
