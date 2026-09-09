<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    /**
     * Enroll a student in a class with transaction.
     */
    public function enroll(Student $student, SchoolClass $class): Enrollment
    {
        return DB::transaction(function () use ($student, $class) {
            // Check if already enrolled
            $existing = Enrollment::where('student_id', $student->id)
                ->where('class_id', $class->id)
                ->first();

            if ($existing) {
                throw new \RuntimeException('O aluno já está matriculado nesta turma.');
            }

            // Check class capacity
            $currentCount = Enrollment::where('class_id', $class->id)
                ->where('status', 'active')
                ->count();

            if ($currentCount >= $class->max_students) {
                throw new \RuntimeException('A turma atingiu o número máximo de alunos.');
            }

            return Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
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
