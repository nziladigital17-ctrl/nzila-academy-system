<?php

namespace Tests\Feature\Finance;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;
    private AcademicYear $academicYear;
    private ExpenseCategory $category;

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
        
        $this->category = ExpenseCategory::factory()->create(['school_id' => $this->school->id]);
    }

    public function test_can_list_expenses()
    {
        Expense::factory()->count(3)->create([
            'school_id' => $this->school->id,
            'category' => $this->category->name,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/expenses');

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_can_create_expense()
    {
        $data = [
            'category' => $this->category->name,
            'description' => 'Supplies',
            'amount' => 500,
            'date' => '2026-03-01',
            'status' => 'draft',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/expenses', $data);

        $response->assertStatus(201);
$response->assertStatus(201);
        $this->assertDatabaseHas('expenses', [
            'school_id' => $this->school->id,
            'amount' => 500,
            'status' => 'draft',
        ]);
    }

    public function test_can_update_draft_expense()
    {
        $expense = Expense::factory()->create([
            'school_id' => $this->school->id,
            'category' => $this->category->name,
            'status' => 'draft',
            'amount' => 100,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/expenses/{$expense->id}", [
            'amount' => 200,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 200]);
    }

    public function test_cannot_update_confirmed_expense()
    {
        $expense = Expense::factory()->create([
            'school_id' => $this->school->id,
            'category' => $this->category->name,
            'status' => 'confirmed',
            'amount' => 100,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/v1/expenses/{$expense->id}", [
            'amount' => 200,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_confirm_expense()
    {
        $expense = Expense::factory()->create([
            'school_id' => $this->school->id,
            'category' => $this->category->name,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/expenses/{$expense->id}/confirm");

        $response->assertStatus(200);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'status' => 'confirmed']);
    }

    public function test_can_void_expense()
    {
        $expense = Expense::factory()->create([
            'school_id' => $this->school->id,
            'category' => $this->category->name,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/expenses/{$expense->id}/void");
        $response = $this->actingAs($this->user)->postJson("/api/v1/expenses/{$expense->id}/void", [
            'reason' => 'Erro na introdução da despesa',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'status' => 'voided']);
    }
}

