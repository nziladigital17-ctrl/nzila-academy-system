<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;
    private AcademicYear $academicYear;
    private SchoolClass $schoolClass;
    private Teacher $teacher;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create(['name' => 'Escola Teste']);
        
        $role = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $this->user = User::factory()->create(['school_id' => $this->school->id]);
        $this->user->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id]);
        
        $this->schoolClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->teacher = Teacher::factory()->create(['school_id' => $this->school->id]);
        $this->subject = Subject::factory()->create(['school_id' => $this->school->id]);
    }

    public function test_can_assign_teacher_to_class()
    {
        Sanctum::actingAs($this->user);
        $response = $this->postJson("/api/v1/teaching-assignments", [
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'class_id' => $this->schoolClass->id,
            'academic_year_id' => $this->academicYear->id,
            'role' => 'titular',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('teacher_assignments', [
            'teacher_id' => $this->teacher->id,
            'class_id' => $this->schoolClass->id,
            'subject_id' => $this->subject->id,
            'role' => 'titular',
        ]);
    }

    public function test_cannot_assign_same_teacher_twice()
    {
        TeacherAssignment::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'class_id' => $this->schoolClass->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->postJson("/api/v1/teaching-assignments", [
            'teacher_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'class_id' => $this->schoolClass->id,
            'academic_year_id' => $this->academicYear->id,
            'role' => 'auxiliary',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['teacher_id']);
    }
}
