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
use App\Services\GradeService;
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
        $this->expectExceptionMessage('já está matriculado');

        $service->enroll($this->student, $this->class);
    }

    public function test_enrollment_over_capacity_throws_exception(): void
    {
        $service = new EnrollmentService();

        // Fill the class to capacity (max_students = 3, already have 1)
        for ($i = 0; $i < 2; $i++) {
            $s = Student::factory()->create(['school_id' => $this->school->id]);
            $service->enroll($s, $this->class);
        }

        // This should fail — class is full
        $extraStudent = Student::factory()->create(['school_id' => $this->school->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('número máximo');

        $service->enroll($extraStudent, $this->class);
    }

    // ── GradeService ──

    public function test_grade_above_max_score_throws_exception(): void
    {
        $term = Term::create([
            'academic_year_id' => $this->class->academic_year_id,
            'name' => '1º Trimestre',
            'start_date' => '2026-02-01',
            'end_date' => '2026-04-30',
        ]);

        $assessment = Assessment::create([
            'class_id' => $this->class->id,
            'term_id' => $term->id,
            'name' => 'Prova 1',
            'type' => 'exam',
            'max_score' => 20.00,
            'weight' => 1.00,
        ]);

        $service = new GradeService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('excede o máximo');

        $service->submitGrades($assessment, [
            ['enrollment_id' => $this->enrollment->id, 'score' => 25.00],
        ]);
    }

    public function test_valid_grade_submission(): void
    {
        $term = Term::create([
            'academic_year_id' => $this->class->academic_year_id,
            'name' => '1º Trimestre',
            'start_date' => '2026-02-01',
            'end_date' => '2026-04-30',
        ]);

        $assessment = Assessment::create([
            'class_id' => $this->class->id,
            'term_id' => $term->id,
            'name' => 'Prova 1',
            'type' => 'exam',
            'max_score' => 20.00,
            'weight' => 1.00,
        ]);

        $service = new GradeService();
        $grades = $service->submitGrades($assessment, [
            ['enrollment_id' => $this->enrollment->id, 'score' => 15.50],
        ]);

        $this->assertCount(1, $grades);
        $this->assertEquals(15.50, $grades[0]->score);
        $this->assertDatabaseHas('grades', [
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'score' => 15.50,
        ]);
    }

    // ── InvoiceService ──

    public function test_invoice_creation_with_items(): void
    {
        $service = new InvoiceService();

        $invoice = $service->createInvoice($this->student, [
            ['description' => 'Propina Março', 'quantity' => 1, 'unit_price' => 25000.00],
            ['description' => 'Material Escolar', 'quantity' => 2, 'unit_price' => 5000.00],
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
        ]);

        $paymentService = new PaymentService();
        $payment = $paymentService->registerPayment($invoice, [
            'amount' => 15000.00,
            'payment_method' => 'cash',
        ]);

        $this->assertNotNull($payment->receipt);
        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
    }

    public function test_full_payment_sets_status_paid(): void
    {
        $invoiceService = new InvoiceService();
        $invoice = $invoiceService->createInvoice($this->student, [
            ['description' => 'Propina', 'quantity' => 1, 'unit_price' => 20000.00],
        ]);

        $paymentService = new PaymentService();
        $payment = $paymentService->registerPayment($invoice, [
            'amount' => 20000.00,
            'payment_method' => 'multicaixa',
            'reference' => 'MCX-123456',
        ]);

        $this->assertNotNull($payment->receipt);
        $this->assertNotNull($payment->receipt->receipt_number);
        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_receipt_is_generated_on_payment(): void
    {
        $invoiceService = new InvoiceService();
        $invoice = $invoiceService->createInvoice($this->student, [
            ['description' => 'Propina', 'quantity' => 1, 'unit_price' => 10000.00],
        ]);

        $paymentService = new PaymentService();
        $payment = $paymentService->registerPayment($invoice, [
            'amount' => 10000.00,
            'payment_method' => 'transfer',
        ]);

        $this->assertNotNull($payment->receipt);
        $this->assertStringStartsWith('REC-', $payment->receipt->receipt_number);
        $this->assertDatabaseCount('receipts', 1);
    }
}
