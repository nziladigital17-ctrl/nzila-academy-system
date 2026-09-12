<?php

namespace Tests\Feature\Finance;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\TuitionPlan;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolIsolationFinanceTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $userA;
    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->schoolA = School::factory()->create();
        $this->schoolB = School::factory()->create();
        
        $this->userA = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->userB = User::factory()->create(['school_id' => $this->schoolB->id]);
        
        $role = Role::where('name', RoleEnum::FINANCIAL->value)->first();
        
        $this->userA->roles()->attach($role->id, ['school_id' => $this->schoolA->id]);
        $this->userB->roles()->attach($role->id, ['school_id' => $this->schoolB->id]);
    }

    public function test_user_can_only_see_own_school_invoices()
    {
        $studentA = Student::factory()->create(['school_id' => $this->schoolA->id]);
        $studentB = Student::factory()->create(['school_id' => $this->schoolB->id]);

        Invoice::factory()->count(2)->create(['school_id' => $this->schoolA->id, 'student_id' => $studentA->id]);
        Invoice::factory()->count(3)->create(['school_id' => $this->schoolB->id, 'student_id' => $studentB->id]);

        $response = $this->actingAs($this->userA)->getJson('/api/v1/invoices');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_view_other_school_invoice()
    {
        $studentB = Student::factory()->create(['school_id' => $this->schoolB->id]);
        $invoiceB = Invoice::factory()->create(['school_id' => $this->schoolB->id, 'student_id' => $studentB->id]);

        $response = $this->actingAs($this->userA)->getJson("/api/v1/invoices/{$invoiceB->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_only_see_own_school_expenses()
    {
        $catA = ExpenseCategory::factory()->create(['school_id' => $this->schoolA->id]);
        $catB = ExpenseCategory::factory()->create(['school_id' => $this->schoolB->id]);

        Expense::factory()->count(1)->create(['school_id' => $this->schoolA->id, 'category' => $catA->name]);
        Expense::factory()->count(4)->create(['school_id' => $this->schoolB->id, 'category' => $catB->name]);

        $response = $this->actingAs($this->userA)->getJson('/api/v1/expenses');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_user_can_only_see_own_school_tuition_plans()
    {
        TuitionPlan::factory()->count(3)->create(['school_id' => $this->schoolA->id]);
        TuitionPlan::factory()->count(2)->create(['school_id' => $this->schoolB->id]);

        $response = $this->actingAs($this->userA)->getJson('/api/v1/tuition-plans');

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }
}
