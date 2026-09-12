<?php

namespace Tests\Feature\Finance;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;
    private AcademicYear $academicYear;
    private Student $student;

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
        
        $this->student = Student::factory()->create(['school_id' => $this->school->id]);
    }

    public function test_can_apply_discount()
    {
        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'total' => 10000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/invoices/{$invoice->id}/adjustments", [
            'type' => 'discount',
            'amount' => 1000,
            'description' => 'Test discount',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('financial_adjustments', [
            'invoice_id' => $invoice->id,
            'type' => 'discount',
            'amount' => 1000,
        ]);
    }

    public function test_can_apply_penalty()
    {
        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'total' => 10000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/invoices/{$invoice->id}/adjustments", [
            'type' => 'penalty',
            'amount' => 500,
            'description' => 'Late fee',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('financial_adjustments', [
            'invoice_id' => $invoice->id,
            'type' => 'penalty',
            'amount' => 500,
        ]);
    }

    public function test_cannot_adjust_voided_invoice()
    {
        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'total' => 10000,
            'status' => 'voided',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/invoices/{$invoice->id}/adjustments", [
            'type' => 'discount',
            'amount' => 1000,
        ]);

        $response->assertStatus(422);
    }

    public function test_adjustment_recalculates_invoice_total()
    {
        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'subtotal' => 10000,
            'total' => 10000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)->postJson("/api/v1/invoices/{$invoice->id}/adjustments", [
            'type' => 'discount',
            'amount' => 2000,
            'description' => 'Discount',
        ])->assertStatus(201);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'total' => 8000,
        ]);
    }
}
