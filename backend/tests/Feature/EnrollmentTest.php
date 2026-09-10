<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $secretary;
    private Student $student;
    private SchoolClass $schoolClass;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create();

        $this->secretary = User::factory()->create(['school_id' => $this->school->id]);
        $role = Role::where('name', RoleEnum::SECRETARY->value)->first();
        $this->secretary->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $this->student = Student::factory()->create(['school_id' => $this->school->id]);
        $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id, 'is_current' => true]);
        $this->schoolClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'max_students' => 30,
        ]);
    }

    public function test_can_enroll_student(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->postJson('/api/v1/enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $this->schoolClass->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $this->schoolClass->id,
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);
    }

    public function test_can_list_enrollments(): void
    {
        Enrollment::factory()->count(2)->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_id' => $this->schoolClass->id,
        ]);

        Sanctum::actingAs($this->secretary);
        $response = $this->getJson('/api/v1/enrollments');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_can_update_enrollment_status(): void
    {
        $enrollment = Enrollment::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'class_id' => $this->schoolClass->id,
        ]);

        Sanctum::actingAs($this->secretary);
        $response = $this->putJson("/api/v1/enrollments/{$enrollment->id}", [
            'status' => 'cancelled',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'status' => 'cancelled',
        ]);
    }
}
