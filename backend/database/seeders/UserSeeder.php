<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('code', 'DEMO-001')->first();

        // Super Admin (no school)
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@nzila-academy.ao'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('password'),
                'phone' => '+244 923 000 000',
                'is_active' => true,
            ]
        );
        $superAdmin->roles()->syncWithoutDetaching([
            Role::where('name', RoleEnum::SUPER_ADMIN->value)->first()->id => ['school_id' => null],
        ]);

        // One user per role for the demo school
        $demoUsers = [
            ['name' => 'Admin da Escola', 'email' => 'admin@demo.nzila.ao', 'role' => RoleEnum::SCHOOL_ADMIN],
            ['name' => 'Director Demo', 'email' => 'director@demo.nzila.ao', 'role' => RoleEnum::DIRECTOR],
            ['name' => 'Coordenador Demo', 'email' => 'coordenador@demo.nzila.ao', 'role' => RoleEnum::PEDAGOGIC_COORDINATOR],
            ['name' => 'Financeiro Demo', 'email' => 'financeiro@demo.nzila.ao', 'role' => RoleEnum::FINANCIAL],
            ['name' => 'Secretária Demo', 'email' => 'secretaria@demo.nzila.ao', 'role' => RoleEnum::SECRETARY],
            ['name' => 'Professor Demo', 'email' => 'professor@demo.nzila.ao', 'role' => RoleEnum::TEACHER],
            ['name' => 'Aluno Demo', 'email' => 'aluno@demo.nzila.ao', 'role' => RoleEnum::STUDENT],
            ['name' => 'Encarregado Demo', 'email' => 'encarregado@demo.nzila.ao', 'role' => RoleEnum::GUARDIAN],
        ];

        foreach ($demoUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'school_id' => $school->id,
                    'password' => Hash::make('password'),
                    'phone' => '+244 923 000 ' . str_pad(rand(100, 999), 3, '0'),
                    'is_active' => true,
                ]
            );

            $role = Role::where('name', $userData['role']->value)->first();
            $user->roles()->syncWithoutDetaching([
                $role->id => ['school_id' => $school->id],
            ]);
        }
    }
}
