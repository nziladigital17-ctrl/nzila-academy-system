<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Enroll a student in a class with transaction and all business rules.
     */
    public function enroll(int $studentId, int $classId, int $schoolId): Enrollment
    {
        return DB::transaction(function () use ($studentId, $classId, $schoolId) {
            // Lock the class row to prevent race conditions on capacity check
            $class = SchoolClass::withoutGlobalScopes()->lockForUpdate()->findOrFail($classId);
            $student = Student::withoutGlobalScopes()->findOrFail($studentId);

            // Validate same school
            if ($student->school_id !== $class->school_id) {
                throw new \RuntimeException('O aluno e a turma não pertencem à mesma escola.');
            }

            // Validate academic year is open
            $academicYear = AcademicYear::withoutGlobalScopes()->find($class->academic_year_id);
            if (!$academicYear || !$academicYear->is_current) {
                throw new \RuntimeException('Não é possível matricular num ano lectivo fechado.');
            }

            // Check for duplicate enrollment: same student + same academic year
            $duplicateInYear = Enrollment::withoutGlobalScopes()
                ->where('student_id', $studentId)
                ->whereHas('schoolClass', function ($q) use ($class) {
                    $q->withoutGlobalScopes()->where('academic_year_id', $class->academic_year_id);
                })
                ->where('status', 'active')
                ->exists();

            if ($duplicateInYear) {
                throw new \RuntimeException('O aluno já está matriculado neste ano lectivo.');
            }

            // Check class capacity
            $currentCount = Enrollment::withoutGlobalScopes()
                ->where('class_id', $classId)
                ->where('status', 'active')
                ->count();

            if ($currentCount >= $class->max_students) {
                throw new \RuntimeException('A turma atingiu o número máximo de alunos.');
            }

            return Enrollment::create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'class_id' => $classId,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);
        });
    }

    /**
     * Cancel an enrollment with transaction.
     */
    public function cancel(Enrollment $enrollment): Enrollment
    {
        return DB::transaction(function () use ($enrollment) {
            $enrollment->update(['status' => 'cancelled']);
            return $enrollment->fresh();
        });
    }
}
