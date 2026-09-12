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

class PaymentTest extends TestCase
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

    public function test_can_list_payments()
    {
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id]);
        Payment::factory()->count(2)->create(['school_id' => $this->school->id, 'invoice_id' => $invoice->id]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/payments');

        $response->assertStatus(200);
    }

    public function test_can_create_payment()
    {
        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'total' => 10000,
            'status' => 'pending',
        ]);

        $data = [
            'invoice_id' => $invoice->id,
            'amount' => 10000,
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/payments', $data);

        $response->assertStatus(201);
$response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => 10000,
        ]);
        $this->assertDatabaseHas('receipts', [
            'payment_id' => $response->json('data.id'),
        ]);
    }

    public function test_partial_payment_sets_status_partial()
    {
        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'total' => 10000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 5000,
            'payment_method' => 'cash',
        ])->assertStatus(201);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'partial',
        ]);
    }

    public function test_full_payment_sets_status_paid()
    {
        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'total' => 10000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 10000,
            'payment_method' => 'cash',
        ])->assertStatus(201);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
    }

    public function test_can_void_confirmed_payment()
    {
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'total' => 10000, 'status' => 'paid']);
        $payment = Payment::factory()->create(['school_id' => $this->school->id, 'invoice_id' => $invoice->id, 'amount' => 10000, 'status' => 'confirmed']);
        $receipt = Receipt::factory()->create(['payment_id' => $payment->id, 'status' => 'active']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/payments/{$payment->id}/void", [
            'reason' => 'Error',
            'reason' => 'Erro no processamento do pagamento',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'voided']);
        $this->assertDatabaseHas('receipts', ['id' => $receipt->id, 'status' => 'voided']);
    }

    public function test_cannot_void_non_confirmed_payment()
    {
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id]);
        $payment = Payment::factory()->create(['school_id' => $this->school->id, 'invoice_id' => $invoice->id, 'status' => 'voided']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/payments/{$payment->id}/void");
        
        $response->assertStatus(422);
        $response = $this->actingAs($this->user)->postJson("/api/v1/payments/{$payment->id}/void", [
            'reason' => 'Erro no processamento',
        ]);

        $response->assertStatus(422); // Logic exception mapped or returned
    }

    public function test_can_refund_payment()
    {
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id]);
        $payment = Payment::factory()->create(['school_id' => $this->school->id, 'invoice_id' => $invoice->id, 'status' => 'confirmed']);

        $response = $this->actingAs($this->user)->postJson("/api/v1/payments/{$payment->id}/refund");
        $response = $this->actingAs($this->user)->postJson("/api/v1/payments/{$payment->id}/refund", [
            'reason' => 'Reembolso autorizado pelo diretor',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'refunded']);
    }

    public function test_payment_with_allocations()
    {
        $this->withoutExceptionHandling();
        $invoice1 = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'total' => 5000]);
        $invoice2 = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'total' => 5000]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'amount' => 10000,
            'payment_method' => 'bank_transfer',
            'payment_method' => 'transfer',
            'allocations' => [
                ['invoice_id' => $invoice1->id, 'amount' => 5000],
                ['invoice_id' => $invoice2->id, 'amount' => 5000],
            ]
        ]);

        $response->assertStatus(201);
        $paymentId = $response->json('data.id');
        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $paymentId, 'invoice_id' => $invoice1->id]);
        $this->assertDatabaseHas('payment_allocations', ['payment_id' => $paymentId, 'invoice_id' => $invoice2->id]);
    }
}


