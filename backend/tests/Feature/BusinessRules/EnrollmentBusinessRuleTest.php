<?php

namespace Tests\Feature\BusinessRules;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\School;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentBusinessRuleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private Student $student;
    private AcademicYear $academicYear;
    private SchoolClass $schoolClass;
    private EnrollmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->school = School::factory()->create();
        $this->student = Student::factory()->create(['school_id' => $this->school->id]);
        $this->academicYear = AcademicYear::factory()->create(['school_id' => $this->school->id, 'is_current' => true]);
        $this->schoolClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'max_students' => 2,
        ]);
        
        $this->service = new EnrollmentService();
    }

    public function test_cannot_enroll_student_from_different_school(): void
    {
        $schoolB = School::factory()->create();
        $studentB = Student::factory()->create(['school_id' => $schoolB->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('O aluno e a turma não pertencem à mesma escola.');

        $this->service->enroll($studentB->id, $this->schoolClass->id, $schoolB->id);
    }

    public function test_cannot_enroll_in_closed_academic_year(): void
    {
        $closedYear = AcademicYear::factory()->create(['school_id' => $this->school->id, 'is_current' => false]);
        $closedClass = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $closedYear->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Não é possível matricular num ano lectivo fechado.');

        $this->service->enroll($this->student->id, $closedClass->id, $this->school->id);
    }

    public function test_cannot_enroll_duplicate_in_same_academic_year(): void
    {
        // Enroll in class A
        $this->service->enroll($this->student->id, $this->schoolClass->id, $this->school->id);

        $classB = SchoolClass::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('O aluno já está matriculado neste ano lectivo.');

        // Try to enroll in class B (same academic year)
        $this->service->enroll($this->student->id, $classB->id, $this->school->id);
    }

    public function test_cannot_enroll_if_class_capacity_reached(): void
    {
        $student1 = Student::factory()->create(['school_id' => $this->school->id]);
        $student2 = Student::factory()->create(['school_id' => $this->school->id]);

        // Max students is 2
        $this->service->enroll($student1->id, $this->schoolClass->id, $this->school->id);
        $this->service->enroll($student2->id, $this->schoolClass->id, $this->school->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A turma atingiu o número máximo de alunos.');

        $this->service->enroll($this->student->id, $this->schoolClass->id, $this->school->id);
    }
}
