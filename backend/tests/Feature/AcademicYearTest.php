<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create(['name' => 'Escola Teste']);
        
        $role = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $this->user = User::factory()->create(['school_id' => $this->school->id]);
        $this->user->roles()->attach($role->id, ['school_id' => $this->school->id]);
    }

    public function test_can_list_academic_years()
    {
        AcademicYear::factory()->count(2)->create(['school_id' => $this->school->id]);

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/v1/academic-years');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_create_academic_year()
    {
        Sanctum::actingAs($this->user);
        $response = $this->postJson('/api/v1/academic-years', [
            'name' => '2023/2024',
            'start_date' => '2023-09-01',
            'end_date' => '2024-06-30',
            'is_current' => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('academic_years', [
            'name' => '2023/2024',
            'school_id' => $this->school->id,
            'is_current' => 1,
        ]);
    }

    public function test_creating_current_academic_year_sets_others_to_false()
    {
        $oldYear = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'is_current' => true,
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->postJson('/api/v1/academic-years', [
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
            'is_current' => true,
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('academic_years', [
            'id' => $oldYear->id,
            'is_current' => 0,
        ]);
    }

    public function test_cannot_delete_academic_year_with_classes()
    {
        $year = AcademicYear::factory()->create(['school_id' => $this->school->id]);
        SchoolClass::factory()->create([
            'academic_year_id' => $year->id,
            'school_id' => $this->school->id,
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->deleteJson("/api/v1/academic-years/{$year->id}");

        $response->assertStatus(422);
    }
}
