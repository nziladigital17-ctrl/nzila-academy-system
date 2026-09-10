<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Role;
use App\Models\School;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TermTest extends TestCase
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

    public function test_can_list_terms()
    {
        Term::factory()->count(2)->create(['academic_year_id' => $this->academicYear->id]);

        Sanctum::actingAs($this->user);
        $response = $this->getJson("/api/v1/academic-years/{$this->academicYear->id}/terms");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_create_term()
    {
        Sanctum::actingAs($this->user);
        $response = $this->postJson('/api/v1/terms', [
            'academic_year_id' => $this->academicYear->id,
            'name' => '1º Trimestre',
            'start_date' => '2023-09-01',
            'end_date' => '2023-12-15',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('terms', [
            'name' => '1º Trimestre',
            'academic_year_id' => $this->academicYear->id,
        ]);
    }

    public function test_cannot_delete_term_with_assessments()
    {
        $term = Term::factory()->create(['academic_year_id' => $this->academicYear->id]);
        Assessment::factory()->create([
            'term_id' => $term->id,
            'school_id' => $this->school->id,
        ]);

        Sanctum::actingAs($this->user);
        $response = $this->deleteJson("/api/v1/terms/{$term->id}");

        $response->assertStatus(422);
    }
}
