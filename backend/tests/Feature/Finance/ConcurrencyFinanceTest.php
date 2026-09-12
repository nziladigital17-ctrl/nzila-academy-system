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

class ConcurrencyFinanceTest extends TestCase
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

    public function test_double_payment_does_not_exceed_invoice()
    {
        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'total' => 10000,
            'status' => 'pending',
        ]);

        // First payment
        $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 10000,
            'payment_method' => 'cash',
        ])->assertStatus(201);
        
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);

        // Second payment (overpayment allowed at payment level, but invoice is still paid)
        $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 5000,
            'payment_method' => 'cash',
        ])->assertStatus(201);
        
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
        $this->assertEquals(2, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_unique_invoice_number()
    {
        $data = [
            'student_id' => $this->student->id,
            'due_date' => '2026-05-10',
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'items' => [
                ['description' => 'Tuition', 'quantity' => 1, 'unit_price' => 1000]
            ],
            'subtotal' => 1000,
            'total' => 1000,
            'status' => 'pending',
        ];

        $res1 = $this->actingAs($this->user)->postJson('/api/v1/invoices', $data);
        $res2 = $this->actingAs($this->user)->postJson('/api/v1/invoices', $data);

        $res1->assertStatus(201);
        $res2->assertStatus(201);

        $inv1 = Invoice::find($res1->json('data.id'));
        $inv2 = Invoice::find($res2->json('data.id'));

        $this->assertNotEquals($inv1->invoice_number, $inv2->invoice_number);
    }

    public function test_unique_receipt_number()
    {
        $invoice = Invoice::factory()->create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'total' => 5000]);

        $res1 = $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 2000,
            'payment_method' => 'cash',
        ]);
        
        $res2 = $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 3000,
            'payment_method' => 'cash',
        ]);

        $rec1 = Receipt::where('payment_id', $res1->json('data.id'))->first();
        $rec2 = Receipt::where('payment_id', $res2->json('data.id'))->first();

        $this->assertNotEquals($rec1->receipt_number, $rec2->receipt_number);
    }

    public function test_void_and_repay_flow()
    {
        $invoice = Invoice::factory()->create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'total' => 10000,
            'status' => 'pending',
        ]);

        // Pay
        $resPay = $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 10000,
            'payment_method' => 'cash',
        ]);
        $paymentId = $resPay->json('data.id');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);

        // Void
        $this->actingAs($this->user)->postJson("/api/v1/payments/{$paymentId}/void", ['reason' => 'Error']);
        $this->actingAs($this->user)->postJson("/api/v1/payments/{$paymentId}/void", ['reason' => 'Erro ao processar']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'pending']); // Back to pending as total paid = 0

        // Repay
        $this->actingAs($this->user)->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount' => 10000,
            'payment_method' => 'cash',
        ]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }
}
