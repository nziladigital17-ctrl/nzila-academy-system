<?php

namespace Tests\Feature\Service;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\EnrollmentService;

use App\Services\InvoiceService;
use App\Services\PaymentService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $user;
    private SchoolClass $class;
    private Student $student;
    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create();
        $this->user = User::factory()->create(['school_id' => $this->school->id]);
        Auth::login($this->user);

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-02-01',
            'end_date' => '2026-12-15',
            'is_current' => true,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Matemática',
            'code' => 'MAT',
        ]);

        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'name' => '10ª A',
            'grade_level' => '10ª classe',
            'shift' => 'morning',
            'max_students' => 3, // Small limit for testing
        ]);

        $this->student = Student::factory()->create(['school_id' => $this->school->id]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);
    }

    // ── EnrollmentService ──

    public function test_duplicate_enrollment_throws_exception(): void
    {
        $service = new EnrollmentService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('O aluno já está matriculado neste ano lectivo.');

        // This will throw because $this->student was enrolled in setUp()
        $service->enroll($this->student->id, $this->class->id, $this->school->id);
    }

    public function test_enrollment_over_capacity_throws_exception(): void
    {
        $service = new EnrollmentService();

        // Fill the class to capacity (max_students = 3, already have 1)
        for ($i = 0; $i < 2; $i++) {
            $s = Student::factory()->create(['school_id' => $this->school->id]);
            $service->enroll($s->id, $this->class->id, $this->school->id);
        }

        // This should fail — class is full
        $student = Student::factory()->create(['school_id' => $this->school->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A turma atingiu o número máximo de alunos.');

        $service->enroll($student->id, $this->class->id, $this->school->id);
    }



    // ── InvoiceService ──

    public function test_invoice_creation_with_items(): void
    {
        $service = new InvoiceService();

        $invoice = $service->createInvoice($this->student, [
            ['description' => 'Propina Março', 'quantity' => 1, 'unit_price' => 25000.00],
            ['description' => 'Material Escolar', 'quantity' => 2, 'unit_price' => 5000.00],
            'items' => [
                ['description' => 'Propina Março', 'quantity' => 1, 'unit_price' => 25000.00],
                ['description' => 'Material Escolar', 'quantity' => 2, 'unit_price' => 5000.00],
            ]
        ]);

        $this->assertNotNull($invoice->invoice_number);
        $this->assertEquals(35000.00, $invoice->total);
        $this->assertCount(2, $invoice->items);
        $this->assertEquals('pending', $invoice->status);
    }

    // ── PaymentService ──

    public function test_partial_payment_sets_status_partial(): void
    {
        $invoiceService = new InvoiceService();
        $invoice = $invoiceService->createInvoice($this->student, [
            ['description' => 'Propina', 'quantity' => 1, 'unit_price' => 30000.00],
            'items' => [
                ['description' => 'Propina', 'quantity' => 1, 'unit_price' => 30000.00],
            ]
        ]);

        $paymentService = app(PaymentService::class);
        $payment = $paymentService->registerPayment([
            'invoice_id' => $invoice->id,
            'amount' => 15000.00,
            'payment_method' => 'cash',
        ], $this->school->id);
        $payment = $paymentService->confirmPayment($payment);

        $this->assertNotNull($payment->receipt);
        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
    }

    public function test_full_payment_sets_status_paid(): void
    {
        $invoiceService = new InvoiceService();
        $invoice = $invoiceService->createInvoice($this->student, [
            ['description' => 'Propina', 'quantity' => 1, 'unit_price' => 20000.00],
            'items' => [
                ['description' => 'Propina', 'quantity' => 1, 'unit_price' => 20000.00],
            ]
        ]);

        $paymentService = app(PaymentService::class);
        $payment = $paymentService->registerPayment([
            'invoice_id' => $invoice->id,
            'amount' => 20000.00,
            'payment_method' => 'multicaixa',
            'reference' => 'MCX-123456',
        ], $this->school->id);
        $payment = $paymentService->confirmPayment($payment);

        $this->assertNotNull($payment->receipt);
        $this->assertNotNull($payment->receipt->receipt_number);
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_receipt_is_generated_on_payment(): void
    {
        $invoiceService = new InvoiceService();
        $invoice = $invoiceService->createInvoice($this->student, [
            'items' => [
                ['description' => 'Propina', 'quantity' => 1, 'unit_price' => 10000.00],
            ]
        ]);

        $paymentService = app(PaymentService::class);
        $payment = $paymentService->registerPayment([
            'invoice_id' => $invoice->id,
            'amount' => 10000.00,
            'payment_method' => 'transfer',
        ], $this->school->id);
        $payment = $paymentService->confirmPayment($payment);

        $this->assertNotNull($payment->receipt);
        $this->assertNotNull($payment->receipt->receipt_number);
        $this->assertDatabaseCount('receipts', 1);
    }
}
