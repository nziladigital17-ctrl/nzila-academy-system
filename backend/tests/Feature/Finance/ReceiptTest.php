<?php

namespace Tests\Feature\Finance;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
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

    public function test_can_list_receipts()
    {
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id]);
        $payment = Payment::factory()->create(['school_id' => $this->school->id, 'invoice_id' => $invoice->id]);
        Receipt::factory()->count(2)->create(['payment_id' => $payment->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/receipts');

        $response->assertStatus(200);
    }

    public function test_can_view_receipt()
    { $this->withoutExceptionHandling();
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id]);
        $payment = Payment::factory()->create(['school_id' => $this->school->id, 'invoice_id' => $invoice->id]);
        $receipt = Receipt::factory()->create(['payment_id' => $payment->id]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/receipts/{$receipt->id}");

        $response->assertStatus(200);
    }

    public function test_can_void_active_receipt()
    {
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id]);
        $payment = Payment::factory()->create(['school_id' => $this->school->id, 'invoice_id' => $invoice->id]);
        $receipt = Receipt::factory()->create(['payment_id' => $payment->id, 'status' => 'active']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/receipts/{$receipt->id}/void");
        $response = $this->actingAs($this->user)->postJson("/api/v1/receipts/{$receipt->id}/void", [
            'reason' => 'Erro na emissão do recibo'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => 'voided']);
    }

    public function test_cannot_void_already_voided_receipt()
    {
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id]);
        $payment = Payment::factory()->create(['school_id' => $this->school->id, 'invoice_id' => $invoice->id]);
        $receipt = Receipt::factory()->create(['payment_id' => $payment->id, 'status' => 'voided']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/receipts/{$receipt->id}/void");
        $response = $this->actingAs($this->user)->postJson("/api/v1/receipts/{$receipt->id}/void", [
            'reason' => 'Erro na emissão do recibo'
        ]);

        $response->assertStatus(422);
    }

    public function test_receipt_number_format()
    {
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'total' => 1000]);
        
        $response = $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 1000,
            'payment_method' => 'cash',
        ]);
        
        $receipt = Receipt::where('payment_id', $response->json('data.id'))->first();
        $this->assertStringStartsWith('REC-', $receipt->receipt_number);
    }
}

