<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubjectTest extends TestCase
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

    public function test_can_list_subjects()
    {
        Subject::factory()->count(3)->create(['school_id' => $this->school->id]);

        Sanctum::actingAs($this->user);
        $response = $this->getJson('/api/v1/subjects');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_create_subject()
    {
        Sanctum::actingAs($this->user);
        $response = $this->postJson('/api/v1/subjects', [
            'name' => 'Matemática',
            'code' => 'MAT101',
            'weekly_hours' => 5,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('subjects', [
            'name' => 'Matemática',
            'code' => 'MAT101',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_cannot_delete_subject_with_active_assignments()
    {
        $subject = Subject::factory()->create(['school_id' => $this->school->id]);
        TeacherAssignment::factory()->create(['subject_id' => $subject->id, 'school_id' => $this->school->id]);

        Sanctum::actingAs($this->user);
        $response = $this->deleteJson("/api/v1/subjects/{$subject->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_can_delete_subject_without_assignments()
    {
        $subject = Subject::factory()->create(['school_id' => $this->school->id]);

        Sanctum::actingAs($this->user);
        $response = $this->deleteJson("/api/v1/subjects/{$subject->id}");

        $response->assertOk();
        $this->assertSoftDeleted('subjects', ['id' => $subject->id]);
    }
}
