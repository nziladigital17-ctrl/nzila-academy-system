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

class ClassTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create(['name' => 'Escola Teste']);
        
        $role = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $this->user = User::factory()->create(['school_id' => $this->school->id]);
        $this->user->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id]);
    }

    public function test_can_list_classes()
    {
        SchoolClass::factory()->count(2)->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/v1/classes');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_create_class()
    {
        Sanctum::actingAs($this->user);
        $response = $this->postJson('/api/v1/classes', [
            'academic_year_id' => $this->academicYear->id,
            'name' => '10ª A',
            'grade_level' => '10ª classe',
            'shift' => 'morning',
            'max_students' => 40,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('classes', [
            'name' => '10ª A',
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);
    }

    public function test_can_delete_class_without_enrollments()
    {
        $class = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->deleteJson("/api/v1/classes/{$class->id}");

        $response->assertOk();
        $this->assertSoftDeleted('classes', ['id' => $class->id]);
    }
}
