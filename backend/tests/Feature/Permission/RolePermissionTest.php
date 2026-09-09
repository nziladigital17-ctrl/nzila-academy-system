<?php

namespace Tests\Feature\Permission;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_can_access_schools(): void
    {
        $user = User::factory()->create();
        $role = Role::where('name', RoleEnum::SUPER_ADMIN->value)->first();
        $user->roles()->attach($role->id);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/schools');

        $response->assertOk();
    }

    public function test_student_cannot_access_schools(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $role = Role::where('name', RoleEnum::STUDENT->value)->first();
        $user->roles()->attach($role->id, ['school_id' => $school->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/schools');

        $response->assertStatus(403);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $role = Role::where('name', RoleEnum::STUDENT->value)->first();
        $user->roles()->attach($role->id, ['school_id' => $school->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/students', [
            'full_name' => 'Test Student',
            'gender' => 'M',
            'birth_date' => '2010-01-01',
        ]);

        $response->assertStatus(403);
    }

    public function test_secretary_can_create_students(): void
    {
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $role = Role::where('name', RoleEnum::SECRETARY->value)->first();
        $user->roles()->attach($role->id, ['school_id' => $school->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/students', [
            'full_name' => 'Novo Aluno',
            'gender' => 'M',
            'birth_date' => '2010-05-15',
        ]);

        $response->assertStatus(201);
    }
}
