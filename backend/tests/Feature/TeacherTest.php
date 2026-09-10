<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\School;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private School $schoolB;
    private User $admin;
    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create(['name' => 'Escola Teste']);
        $this->schoolB = School::factory()->create(['name' => 'Escola B']);
        
        $role = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $this->admin = User::factory()->create(['school_id' => $this->school->id]);
        $this->admin->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $this->unauthorizedUser = User::factory()->create(['school_id' => $this->school->id]);
    }

    public function test_can_list_teachers()
    {
        Teacher::factory()->count(3)->create(['school_id' => $this->school->id]);
        Teacher::factory()->count(2)->create(['school_id' => $this->schoolB->id]);

        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/v1/teachers');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_create_teacher()
    {
        Sanctum::actingAs($this->admin);
        
        $teacherUser = User::factory()->create(['school_id' => $this->school->id]);

        $response = $this->postJson('/api/v1/teachers', [
            'user_id' => $teacherUser->id,
            'employee_number' => 'EMP-001',
            'full_name' => 'John Doe',
            'hire_date' => '2020-01-01',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('teachers', [
            'user_id' => $teacherUser->id,
            'employee_number' => 'EMP-001',
            'full_name' => 'John Doe',
            'school_id' => $this->school->id,
        ]);
    }

    public function test_unauthorized_user_cannot_create_teacher()
    {
        Sanctum::actingAs($this->unauthorizedUser);
        
        $response = $this->postJson('/api/v1/teachers', [
            'employee_number' => 'EMP-001',
            'full_name' => 'John Doe',
            'hire_date' => '2020-01-01',
        ]);

        $response->assertStatus(403);
    }

    public function test_two_schools_can_have_same_employee_number()
    {
        Teacher::factory()->create(['school_id' => $this->school->id, 'employee_number' => 'EMP-001']);

        Sanctum::actingAs($this->admin);

        // admin is in schoolB, oops let's make an admin for schoolB
        $adminB = User::factory()->create(['school_id' => $this->schoolB->id]);
        $role = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $adminB->roles()->attach($role->id, ['school_id' => $this->schoolB->id]);

        Sanctum::actingAs($adminB);
        $teacherUserB = User::factory()->create(['school_id' => $this->schoolB->id]);

        $response = $this->postJson('/api/v1/teachers', [
            'user_id' => $teacherUserB->id,
            'employee_number' => 'EMP-001',
            'full_name' => 'Jane Doe',
            'hire_date' => '2020-01-01',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('teachers', [
            'school_id' => $this->schoolB->id,
            'employee_number' => 'EMP-001',
            'full_name' => 'Jane Doe',
        ]);
    }

    public function test_cannot_delete_teacher_with_active_assignments()
    {
        $teacher = Teacher::factory()->create(['school_id' => $this->school->id]);
        TeacherAssignment::factory()->create(['teacher_id' => $teacher->id, 'school_id' => $this->school->id]);

        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson("/api/v1/teachers/{$teacher->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id]);
    }

    public function test_can_delete_teacher_without_assignments()
    {
        $teacher = Teacher::factory()->create(['school_id' => $this->school->id]);

        Sanctum::actingAs($this->admin);
        $response = $this->deleteJson("/api/v1/teachers/{$teacher->id}");

        $response->assertOk();
        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
    }
}
