<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('code', 'DEMO-001')->first();
        $teacherUser = User::where('email', 'professor@demo.nzila.ao')->first();

        // Academic Year
        $year = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'name' => '2026'],
            [
                'start_date' => '2026-02-01',
                'end_date' => '2026-12-15',
                'is_current' => true,
            ]
        );

        // Terms
        $terms = [
            ['name' => '1º Trimestre', 'start_date' => '2026-02-01', 'end_date' => '2026-04-30'],
            ['name' => '2º Trimestre', 'start_date' => '2026-05-01', 'end_date' => '2026-08-31'],
            ['name' => '3º Trimestre', 'start_date' => '2026-09-01', 'end_date' => '2026-12-15'],
        ];
        foreach ($terms as $t) {
            Term::firstOrCreate(
                ['academic_year_id' => $year->id, 'name' => $t['name']],
                $t
            );
        }

        // Subjects
        $subjectData = [
            ['name' => 'Matemática', 'code' => 'MAT'],
            ['name' => 'Língua Portuguesa', 'code' => 'POR'],
            ['name' => 'Física', 'code' => 'FIS'],
            ['name' => 'Química', 'code' => 'QUI'],
            ['name' => 'Biologia', 'code' => 'BIO'],
            ['name' => 'História', 'code' => 'HIS'],
            ['name' => 'Geografia', 'code' => 'GEO'],
            ['name' => 'Inglês', 'code' => 'ING'],
        ];
        foreach ($subjectData as $s) {
            Subject::firstOrCreate(
                ['school_id' => $school->id, 'code' => $s['code']],
                array_merge($s, ['school_id' => $school->id])
            );
        }

        // Rooms
        for ($i = 1; $i <= 5; $i++) {
            Room::firstOrCreate(
                ['school_id' => $school->id, 'name' => "Sala {$i}"],
                ['school_id' => $school->id, 'name' => "Sala {$i}", 'capacity' => 40, 'building' => 'Bloco A']
            );
        }

        // Teacher
        $teacher = Teacher::firstOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'school_id' => $school->id,
                'user_id' => $teacherUser->id,
                'employee_number' => 'PROF-2026-001',
                'full_name' => 'Professor Demo',
                'specialization' => 'Matemática',
                'academic_degree' => 'Licenciatura',
                'hire_date' => '2020-03-01',
            ]
        );

        // Create a class
        $room = Room::where('school_id', $school->id)->first();
        $subject = Subject::where('school_id', $school->id)->where('code', 'MAT')->first();

        $class = SchoolClass::firstOrCreate(
            ['school_id' => $school->id, 'name' => '10ª A', 'academic_year_id' => $year->id],
            [
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'subject_id' => $subject->id,
                'room_id' => $room->id,
                'name' => '10ª A',
                'grade_level' => '10ª classe',
                'shift' => 'morning',
                'max_students' => 40,
            ]
        );

        // Assign teacher
        $class->teachers()->syncWithoutDetaching([
            $teacher->id => ['role' => 'titular'],
        ]);

        // Demo students
        $studentNames = [
            'Ana Silva', 'Bruno Santos', 'Carlos Mendes', 'Diana Ferreira',
            'Eduardo Costa', 'Fernanda Gomes', 'Gabriel Sousa', 'Helena Pinto',
            'Igor Teixeira', 'Joana Oliveira',
        ];

        foreach ($studentNames as $i => $name) {
            $student = Student::firstOrCreate(
                ['school_id' => $school->id, 'student_number' => sprintf('ALU-2026-%05d', $i + 1)],
                [
                    'school_id' => $school->id,
                    'full_name' => $name,
                    'student_number' => sprintf('ALU-2026-%05d', $i + 1),
                    'gender' => $i % 2 === 0 ? 'F' : 'M',
                    'birth_date' => now()->subYears(15)->subDays($i * 30),
                    'nationality' => 'Angolana',
                ]
            );

            // Enroll in class
            Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'class_id' => $class->id],
                [
                    'enrolled_at' => '2026-02-01',
                    'status' => 'active',
                ]
            );
        }

        // Demo guardian
        Guardian::firstOrCreate(
            ['school_id' => $school->id, 'phone' => '+244 923 100 001'],
            [
                'school_id' => $school->id,
                'full_name' => 'Encarregado Demo',
                'relationship' => 'Pai',
                'phone' => '+244 923 100 001',
                'email' => 'encarregado@demo.nzila.ao',
            ]
        );
    }
}
