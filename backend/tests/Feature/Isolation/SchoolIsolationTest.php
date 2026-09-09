<?php

namespace Tests\Feature\Isolation;

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolIsolationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $userA;
    private User $userB;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->schoolA = School::factory()->create(['name' => 'Escola A']);
        $this->schoolB = School::factory()->create(['name' => 'Escola B']);

        $secretaryRole = Role::where('name', RoleEnum::SECRETARY->value)->first();
        $superAdminRole = Role::where('name', RoleEnum::SUPER_ADMIN->value)->first();

        $this->userA = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->userA->roles()->attach($secretaryRole->id, ['school_id' => $this->schoolA->id]);

        $this->userB = User::factory()->create(['school_id' => $this->schoolB->id]);
        $this->userB->roles()->attach($secretaryRole->id, ['school_id' => $this->schoolB->id]);

        $this->superAdmin = User::factory()->create(['school_id' => null]);
        $this->superAdmin->roles()->attach($superAdminRole->id);
    }

    // ── Student Isolation ──

    public function test_user_can_only_see_students_from_own_school(): void
    {
        Student::factory()->create(['school_id' => $this->schoolA->id, 'full_name' => 'Aluno A']);
        Student::factory()->create(['school_id' => $this->schoolB->id, 'full_name' => 'Aluno B']);

        Sanctum::actingAs($this->userA);
        $response = $this->getJson('/api/v1/students');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('full_name');
        $this->assertTrue($names->contains('Aluno A'));
        $this->assertFalse($names->contains('Aluno B'));
    }

    public function test_user_cannot_view_student_from_another_school(): void
    {
        $studentB = Student::factory()->create(['school_id' => $this->schoolB->id]);

        Sanctum::actingAs($this->userA);
        $response = $this->getJson("/api/v1/students/{$studentB->id}");

        // Should get 404 (global scope hides it) or 403
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_user_cannot_update_student_from_another_school(): void
    {
        $studentB = Student::factory()->create(['school_id' => $this->schoolB->id]);

        Sanctum::actingAs($this->userA);
        $response = $this->putJson("/api/v1/students/{$studentB->id}", [
            'full_name' => 'Hackeado',
        ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));
        $this->assertDatabaseMissing('students', ['full_name' => 'Hackeado']);
    }

    public function test_user_cannot_delete_student_from_another_school(): void
    {
        $studentB = Student::factory()->create(['school_id' => $this->schoolB->id]);

        Sanctum::actingAs($this->userA);
        $response = $this->deleteJson("/api/v1/students/{$studentB->id}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
        $this->assertDatabaseHas('students', ['id' => $studentB->id, 'deleted_at' => null]);
    }

    public function test_student_created_inherits_school_from_auth_user(): void
    {
        Sanctum::actingAs($this->userA);

        $response = $this->postJson('/api/v1/students', [
            'full_name' => 'Novo Aluno',
            'gender' => 'F',
            'birth_date' => '2010-03-20',
        ]);

        $response->assertStatus(201);
        $this->assertEquals($this->schoolA->id, $response->json('data.school_id'));
    }

    // ── User Isolation ──

    public function test_user_can_only_see_users_from_own_school(): void
    {
        Sanctum::actingAs($this->userA);

        // Give userA permission to view users
        $adminRole = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $this->userA->roles()->attach($adminRole->id, ['school_id' => $this->schoolA->id]);

        $response = $this->getJson('/api/v1/users');
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($this->userA->id));
        $this->assertFalse($ids->contains($this->userB->id));
    }

    public function test_user_cannot_view_user_from_another_school(): void
    {
        Sanctum::actingAs($this->userA);

        $adminRole = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $this->userA->roles()->attach($adminRole->id, ['school_id' => $this->schoolA->id]);

        $response = $this->getJson("/api/v1/users/{$this->userB->id}");
        $response->assertStatus(403);
    }

    // ── Super Admin can see everything ──

    public function test_super_admin_can_see_all_students(): void
    {
        Student::factory()->create(['school_id' => $this->schoolA->id, 'full_name' => 'Aluno A']);
        Student::factory()->create(['school_id' => $this->schoolB->id, 'full_name' => 'Aluno B']);

        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/students');
        $response->assertOk();

        $names = collect($response->json('data'))->pluck('full_name');
        $this->assertTrue($names->contains('Aluno A'));
        $this->assertTrue($names->contains('Aluno B'));
    }

    public function test_super_admin_can_see_all_users(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/users');
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($this->userA->id));
        $this->assertTrue($ids->contains($this->userB->id));
    }

    public function test_super_admin_can_see_all_schools(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/v1/schools');
        $response->assertOk();
    }
}
