<?php

namespace Database\Seeders;

use App\Enums\AssessmentTypeEnum;
use App\Enums\AttendancePolicyModeEnum;
use App\Enums\AttendanceStatusEnum;
use App\Enums\GradeBookStatusEnum;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentSetting;
use App\Models\AttendancePolicySetting;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\GradeBook;
use App\Models\Grade;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class AcademicPhase3Seeder extends Seeder
{
    public function run(GradingService $gradingService): void
    {
        $school  = School::where('code', 'DEMO-001')->first();
        $year    = AcademicYear::where('school_id', $school->id)->where('name', '2026')->first();
        $terms   = Term::where('academic_year_id', $year->id)->orderBy('start_date')->get();
        $term1   = $terms->get(0);
        $term2   = $terms->get(1);
        $term3   = $terms->get(2);

        if (!$school || !$year || !$term1) {
            $this->command->warn('Demo data not found. Run DemoDataSeeder first.');
            return;
        }

        // 1. Assessment Settings
        $settings = AssessmentSetting::firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $year->id],
            [
                'min_grade'         => 0,
                'max_grade'         => 20,
                'passing_grade'     => 10,
                'num_terms'         => 3,
                'active_components' => ['AC', 'PP', 'PT'],
                'nf_formula'        => '(MAC + PP + PT) / 3',
                'mfa_formula'       => '(NF1 + NF2 + NF3) / 3',
                'rounding_mode'     => 'half_up',
                'pp_active'         => true,
            ]
        );

        // 2. Attendance Policy Settings
        AttendancePolicySetting::firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $year->id],
            [
                'mode'            => AttendancePolicyModeEnum::ANGOLA_POR_DISCIPLINA->value,
                'angola_limits'   => ['1' => 3, '2' => 4, '3+' => 5],
                'alert_threshold' => 0.80,
            ]
        );

        // Get class and subject
        $class   = \App\Models\SchoolClass::where('school_id', $school->id)->where('name', '10ª A')->first();
        $subject = Subject::where('school_id', $school->id)->where('code', 'MAT')->first();
        $teacher = Teacher::where('school_id', $school->id)->first();

        if (!$class || !$subject || !$teacher) {
            $this->command->warn('Class/Subject/Teacher not found.');
            return;
        }

        // Get or create teacher assignment
        $assignment = TeacherAssignment::firstOrCreate(
            [
                'school_id'       => $school->id,
                'academic_year_id'=> $year->id,
                'teacher_id'      => $teacher->id,
                'class_id'        => $class->id,
                'subject_id'      => $subject->id,
            ],
            ['role' => 'titular']
        );

        $enrollments = Enrollment::where('class_id', $class->id)
            ->where('status', 'active')
            ->get();

        // Create for Term 1 only (demo)
        $this->seedTerm($settings, $school, $year, $term1, $class, $subject, $assignment, $enrollments, $gradingService, $teacher);

        $this->command->info('Phase 3 academic seed completed.');
    }

    private function seedTerm($settings, $school, $year, $term, $class, $subject, $assignment, $enrollments, $gradingService, $teacher): void
    {
        // Create Grade Book
        $gradeBook = GradeBook::firstOrCreate(
            [
                'school_id'       => $school->id,
                'academic_year_id'=> $year->id,
                'term_id'         => $term->id,
                'class_id'        => $class->id,
                'subject_id'      => $subject->id,
            ],
            [
                'teacher_assignment_id' => $assignment->id,
                'status'                => GradeBookStatusEnum::DRAFT->value,
            ]
        );

        // Create assessments for this term
        $ac1 = Assessment::firstOrCreate(
            ['school_id' => $school->id, 'term_id' => $term->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'type' => 'AC', 'label' => 'AC 1'],
            ['academic_year_id' => $year->id, 'teacher_assignment_id' => $assignment->id, 'date' => $term->start_date->addWeeks(2), 'is_active' => true]
        );

        $ac2 = Assessment::firstOrCreate(
            ['school_id' => $school->id, 'term_id' => $term->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'type' => 'AC', 'label' => 'AC 2'],
            ['academic_year_id' => $year->id, 'teacher_assignment_id' => $assignment->id, 'date' => $term->start_date->addWeeks(4), 'is_active' => true]
        );

        $pp = Assessment::firstOrCreate(
            ['school_id' => $school->id, 'term_id' => $term->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'type' => 'PP'],
            ['academic_year_id' => $year->id, 'teacher_assignment_id' => $assignment->id, 'date' => $term->start_date->addWeeks(6), 'is_active' => true]
        );

        $pt = Assessment::firstOrCreate(
            ['school_id' => $school->id, 'term_id' => $term->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'type' => 'PT'],
            ['academic_year_id' => $year->id, 'teacher_assignment_id' => $assignment->id, 'date' => $term->end_date->subWeeks(1), 'is_active' => true]
        );

        // Demo grades per student
        $sampleScores = [
            ['ac1' => 14, 'ac2' => 16, 'pp' => 13, 'pt' => 15],
            ['ac1' => 8,  'ac2' => 9,  'pp' => 9,  'pt' => 10],
            ['ac1' => 18, 'ac2' => 17, 'pp' => 16, 'pt' => 19],
            ['ac1' => 11, 'ac2' => 12, 'pp' => 10, 'pt' => 13],
            ['ac1' => 7,  'ac2' => 6,  'pp' => 8,  'pt' => 7],
            ['ac1' => 15, 'ac2' => 14, 'pp' => 14, 'pt' => 16],
            ['ac1' => 9,  'ac2' => 11, 'pp' => 10, 'pt' => 12],
            ['ac1' => 20, 'ac2' => 18, 'pp' => 17, 'pt' => 19],
            ['ac1' => 13, 'ac2' => 12, 'pp' => 11, 'pt' => 14],
            ['ac1' => 10, 'ac2' => 9,  'pp' => 9,  'pt' => 11],
        ];

        foreach ($enrollments as $i => $enrollment) {
            $scores = $sampleScores[$i] ?? ['ac1' => 10, 'ac2' => 10, 'pp' => 10, 'pt' => 10];

            Grade::firstOrCreate(['assessment_id' => $ac1->id, 'enrollment_id' => $enrollment->id],
                ['grade_book_id' => $gradeBook->id, 'score' => $scores['ac1'], 'graded_by' => 1, 'is_annulled' => false]);

            Grade::firstOrCreate(['assessment_id' => $ac2->id, 'enrollment_id' => $enrollment->id],
                ['grade_book_id' => $gradeBook->id, 'score' => $scores['ac2'], 'graded_by' => 1, 'is_annulled' => false]);

            Grade::firstOrCreate(['assessment_id' => $pp->id, 'enrollment_id' => $enrollment->id],
                ['grade_book_id' => $gradeBook->id, 'score' => $scores['pp'], 'graded_by' => 1, 'is_annulled' => false]);

            Grade::firstOrCreate(['assessment_id' => $pt->id, 'enrollment_id' => $enrollment->id],
                ['grade_book_id' => $gradeBook->id, 'score' => $scores['pt'], 'graded_by' => 1, 'is_annulled' => false]);

            // Recalculate results
            $enrollment->load('schoolClass');
            $gradingService->recalculate($enrollment, $subject->id, $term->id);
        }

        // Create demo attendance sessions (6 sessions)
        for ($s = 1; $s <= 6; $s++) {
            $sessionDate = $term->start_date->copy()->addWeeks($s);
            $session = AttendanceSession::firstOrCreate(
                ['class_id' => $class->id, 'subject_id' => $subject->id, 'date' => $sessionDate],
                [
                    'school_id'        => $school->id,
                    'academic_year_id' => $year->id,
                    'term_id'          => $term->id,
                    'teacher_assignment_id' => $assignment->id,
                    'weekly_periods'   => 2,
                    'recorded_by'      => 1,
                ]
            );

            foreach ($enrollments as $j => $enrollment) {
                // Simulate some absences
                $status = AttendanceStatusEnum::PRESENT->value;
                if ($j === 4 && $s <= 2) {
                    $status = AttendanceStatusEnum::ABSENT_UNJUSTIFIED->value;
                } elseif ($j === 1 && $s === 3) {
                    $status = AttendanceStatusEnum::ABSENT_JUSTIFIED->value;
                }

                AttendanceRecord::firstOrCreate(
                    ['attendance_session_id' => $session->id, 'enrollment_id' => $enrollment->id],
                    ['status' => $status]
                );
            }
        }
    }
}
