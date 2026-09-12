<?php

namespace Tests\Feature\Finance;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionFinanceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->school = School::factory()->create();
    }

    public function test_financial_user_can_access_invoices()
    {
        $user = User::factory()->create(['school_id' => $this->school->id]);
        $role = Role::where('name', RoleEnum::FINANCIAL->value)->first();
        $user->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/invoices');

        $response->assertStatus(200);
    }

    public function test_teacher_cannot_access_invoices()
    {
        $user = User::factory()->create(['school_id' => $this->school->id]);
        $role = Role::where('name', 'teacher')->first();
        $user->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/invoices');

        $response->assertStatus(403);
    }

    public function test_guardian_can_view_finance()
    {
        $user = User::factory()->create(['school_id' => $this->school->id]);
        $role = Role::where('name', 'guardian')->first(); // Ensure this role has finance.view in seeder
        $user->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/invoices');

        $response->assertStatus(200);
    }

    public function test_guardian_cannot_create_invoice()
    {
        $user = User::factory()->create(['school_id' => $this->school->id]);
        $role = Role::where('name', 'guardian')->first();
        $user->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $response = $this->actingAs($user)->postJson('/api/v1/invoices', []);

        $response->assertStatus(403);
    }

    public function test_secretary_cannot_access_finance()
    {
        $user = User::factory()->create(['school_id' => $this->school->id]);
        $role = Role::where('name', 'secretary')->first();
        $user->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $response = $this->actingAs($user)->getJson('/api/v1/invoices');

        $response->assertStatus(403);
    }
}
