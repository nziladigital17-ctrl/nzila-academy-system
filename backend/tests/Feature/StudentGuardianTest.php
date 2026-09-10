<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentGuardianTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $secretary;
    private Student $student;
    private Guardian $guardian;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create();

        $this->secretary = User::factory()->create(['school_id' => $this->school->id]);
        $role = Role::where('name', RoleEnum::SECRETARY->value)->first();
        $this->secretary->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $this->student = Student::factory()->create(['school_id' => $this->school->id]);
        $this->guardian = Guardian::factory()->create(['school_id' => $this->school->id]);
    }

    public function test_can_attach_guardian_to_student(): void
    {
        Sanctum::actingAs($this->secretary);

        $response = $this->postJson("/api/v1/students/{$this->student->id}/guardians", [
            'guardian_id' => $this->guardian->id,
            'relationship' => 'pai',
            'is_primary' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $this->student->id,
            'guardian_id' => $this->guardian->id,
            'relationship' => 'pai',
            'is_primary' => true,
        ]);
    }

    public function test_primary_guardian_is_unique_per_student(): void
    {
        $guardian2 = Guardian::factory()->create(['school_id' => $this->school->id]);

        $this->student->guardians()->attach($this->guardian->id, [
            'relationship' => 'mae',
            'is_primary' => true,
        ]);

        Sanctum::actingAs($this->secretary);

        // Attach second guardian as primary
        $response = $this->postJson("/api/v1/students/{$this->student->id}/guardians", [
            'guardian_id' => $guardian2->id,
            'relationship' => 'pai',
            'is_primary' => true,
        ]);

        $response->assertOk();

        // First guardian should no longer be primary
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $this->student->id,
            'guardian_id' => $this->guardian->id,
            'is_primary' => false,
        ]);

        // Second guardian should be primary
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $this->student->id,
            'guardian_id' => $guardian2->id,
            'is_primary' => true,
        ]);
    }

    public function test_cannot_attach_guardian_from_different_school(): void
    {
        $schoolB = School::factory()->create();
        $guardianB = Guardian::factory()->create(['school_id' => $schoolB->id]);

        Sanctum::actingAs($this->secretary);

        $response = $this->postJson("/api/v1/students/{$this->student->id}/guardians", [
            'guardian_id' => $guardianB->id,
            'relationship' => 'tio',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['guardian_id']);
    }

    public function test_can_detach_guardian(): void
    {
        $this->student->guardians()->attach($this->guardian->id, [
            'relationship' => 'mae',
        ]);

        Sanctum::actingAs($this->secretary);

        $response = $this->deleteJson("/api/v1/students/{$this->student->id}/guardians/{$this->guardian->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('student_guardians', [
            'student_id' => $this->student->id,
            'guardian_id' => $this->guardian->id,
        ]);
    }

    public function test_can_list_student_guardians(): void
    {
        $this->student->guardians()->attach($this->guardian->id, [
            'relationship' => 'mae',
        ]);

        Sanctum::actingAs($this->secretary);

        $response = $this->getJson("/api/v1/students/{$this->student->id}/guardians");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->guardian->id);
    }
}
