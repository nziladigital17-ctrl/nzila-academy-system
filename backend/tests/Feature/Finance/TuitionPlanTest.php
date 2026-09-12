<?php

namespace Tests\Feature\Finance;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\TuitionPlan;
use App\Models\StudentFeeAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TuitionPlanTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create();
        $this->user = User::factory()->create(['school_id' => $this->school->id]);
        
        $role = Role::where('name', RoleEnum::FINANCIAL->value)->first();
        $this->user->roles()->attach($role->id, ['school_id' => $this->school->id]);

        $this->academicYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-12-15',
            'is_current' => true,
        ]);
    }

    public function test_can_list_tuition_plans() { $this->withoutExceptionHandling();
        TuitionPlan::factory()->count(3)->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/tuition-plans');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_can_create_tuition_plan()
    { $this->withoutExceptionHandling();
        $data = [
            'academic_year_id' => $this->academicYear->id,
            'code' => 'PLAN-2026',
            'name' => 'Plano Anual 2026',
            'grade_level' => '10',
            'amount' => 50000.00,
            'periodicity' => 'monthly',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/tuition-plans', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tuition_plans', [
            'school_id' => $this->school->id,
            'code' => 'PLAN-2026',
            'amount' => 50000.00,
        ]);
    }

    public function test_can_update_tuition_plan()
    {
        $plan = TuitionPlan::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'amount' => 1000.00,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/tuition-plans/{$plan->id}", [
            'amount' => 1500.00,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tuition_plans', [
            'id' => $plan->id,
            'amount' => 1500.00,
        ]);
    }

    public function test_can_delete_tuition_plan_without_assignments()
    { $this->withoutExceptionHandling();
        $plan = TuitionPlan::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/tuition-plans/{$plan->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tuition_plans', ['id' => $plan->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_tuition_plan_with_assignments()
    {
        $plan = TuitionPlan::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);
        
        $student = Student::factory()->create(['school_id' => $this->school->id]);
        
        StudentFeeAssignment::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'tuition_plan_id' => $plan->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/tuition-plans/{$plan->id}");

        $response->assertStatus(422);
    }

    public function test_can_filter_by_academic_year()
    {
        $otherYear = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2027',
            'start_date' => '2027-02-01',
            'end_date' => '2027-12-15',
            'is_current' => false,
        ]);

        TuitionPlan::factory()->count(2)->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);
        
        TuitionPlan::factory()->count(3)->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $otherYear->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/tuition-plans?academic_year_id={$otherYear->id}");

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }
}




