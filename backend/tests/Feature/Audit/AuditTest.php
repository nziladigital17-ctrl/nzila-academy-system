<?php

namespace Tests\Feature\Audit;

use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create();
        $this->user = User::factory()->create(['school_id' => $this->school->id]);

        $secretaryRole = Role::where('name', RoleEnum::SECRETARY->value)->first();
        $this->user->roles()->attach($secretaryRole->id, ['school_id' => $this->school->id]);
    }

    public function test_student_creation_generates_audit_log(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/students', [
            'full_name' => 'Aluno Auditado',
            'gender' => 'M',
            'birth_date' => '2010-01-01',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Student::class,
            'user_id' => $this->user->id,
            'school_id' => $this->school->id,
        ]);
    }

    public function test_student_update_generates_audit_log(): void
    {
        Sanctum::actingAs($this->user);

        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'full_name' => 'Nome Original',
        ]);

        $this->putJson("/api/v1/students/{$student->id}", [
            'full_name' => 'Nome Alterado',
        ]);

        $log = AuditLog::where('action', 'updated')
            ->where('auditable_type', Student::class)
            ->where('auditable_id', $student->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Nome Original', $log->old_values['full_name']);
        $this->assertEquals('Nome Alterado', $log->new_values['full_name']);
    }

    public function test_passwords_are_never_logged(): void
    {
        // Create a user — the Auditable trait should strip the password
        Sanctum::actingAs($this->user);

        $adminRole = Role::where('name', RoleEnum::SCHOOL_ADMIN->value)->first();
        $this->user->roles()->attach($adminRole->id, ['school_id' => $this->school->id]);

        $this->postJson('/api/v1/users', [
            'name' => 'User Teste',
            'email' => 'teste@audit.ao',
            'password' => 'secret123',
        ]);

        $logs = AuditLog::where('auditable_type', User::class)
            ->where('action', 'created')
            ->get();

        foreach ($logs as $log) {
            if ($log->new_values) {
                $this->assertArrayNotHasKey('password', $log->new_values);
                $this->assertArrayNotHasKey('remember_token', $log->new_values);
            }
        }
    }

    public function test_login_generates_audit_log(): void
    {
        $loginUser = User::factory()->create([
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $loginUser->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login',
            'user_id' => $loginUser->id,
        ]);
    }

    public function test_logout_generates_audit_log(): void
    {
        $logoutUser = User::factory()->create();
        Sanctum::actingAs($logoutUser);

        $this->postJson('/api/v1/auth/logout');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'logout',
            'user_id' => $logoutUser->id,
        ]);
    }
}
