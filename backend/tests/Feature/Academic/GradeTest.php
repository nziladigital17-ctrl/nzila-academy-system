<?php

namespace Tests\Feature\Academic;

use App\Enums\AssessmentTypeEnum;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentSetting;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeBook;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $teacher;
    private User $coordinator;
    private User $adminUser;
    private SchoolClass $class;
    private Subject $subject;
    private Term $term;
    private AcademicYear $year;
    private TeacherAssignment $assignment;
    private GradeBook $gradeBook;
    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school = School::factory()->create();
        $this->year   = AcademicYear::factory()->create(['school_id' => $this->school->id]);
        $this->term   = Term::factory()->create(['academic_year_id' => $this->year->id]);
        $this->subject = Subject::factory()->create(['school_id' => $this->school->id]);
        $this->class  = SchoolClass::factory()->create([
            'school_id'       => $this->school->id,
            'academic_year_id'=> $this->year->id,
        ]);

        $teacherRole = Role::where('name', 'teacher')->first();
        $coordRole   = Role::where('name', 'pedagogic_coordinator')->first();
        $adminRole   = Role::where('name', 'school_admin')->first();

        $this->teacher = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacher->roles()->attach($teacherRole->id, ['school_id' => $this->school->id]);

        $teacherProfile = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id'   => $this->teacher->id,
        ]);

        $this->coordinator = User::factory()->create(['school_id' => $this->school->id]);
        $this->coordinator->roles()->attach($coordRole->id, ['school_id' => $this->school->id]);

        $this->adminUser = User::factory()->create(['school_id' => $this->school->id]);
        $this->adminUser->roles()->attach($adminRole->id, ['school_id' => $this->school->id]);

        $this->assignment = TeacherAssignment::factory()->create([
            'school_id'        => $this->school->id,
            'academic_year_id' => $this->year->id,
            'teacher_id'       => $teacherProfile->id,
            'class_id'         => $this->class->id,
            'subject_id'       => $this->subject->id,
        ]);

        $student = Student::factory()->create(['school_id' => $this->school->id]);
        $this->enrollment = Enrollment::factory()->create([
            'school_id'  => $this->school->id,
            'student_id' => $student->id,
            'class_id'   => $this->class->id,
            'status'     => 'active',
        ]);

        $this->gradeBook = GradeBook::factory()->create([
            'school_id'             => $this->school->id,
            'academic_year_id'      => $this->year->id,
            'term_id'               => $this->term->id,
            'class_id'              => $this->class->id,
            'subject_id'            => $this->subject->id,
            'teacher_assignment_id' => $this->assignment->id,
            'status'                => 'draft',
        ]);

        AssessmentSetting::create([
            'school_id'        => $this->school->id,
            'academic_year_id' => $this->year->id,
            'min_grade'        => 0,
            'max_grade'        => 20,
            'passing_grade'    => 10,
            'num_terms'        => 3,
            'nf_formula'       => '(MAC + PP + PT) / 3',
            'mfa_formula'      => '(NF1 + NF2 + NF3) / 3',
            'rounding_mode'    => 'half_up',
            'pp_active'        => true,
        ]);
    }

    // ── Scale validation ──────────────────────────────────────────────────────

    public function test_grade_below_zero_is_rejected(): void
    {
        $assessment = $this->createAssessment('AC');

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/assessments/{$assessment->id}/grades", [
                'enrollment_id' => $this->enrollment->id,
                'score'         => -1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['score']);
    }

    public function test_grade_above_twenty_is_rejected(): void
    {
        $assessment = $this->createAssessment('AC');

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/assessments/{$assessment->id}/grades", [
                'enrollment_id' => $this->enrollment->id,
                'score'         => 21,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['score']);
    }

    public function test_valid_grade_within_scale_is_accepted(): void
    {
        $assessment = $this->createAssessment('AC');

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/assessments/{$assessment->id}/grades", [
                'enrollment_id' => $this->enrollment->id,
                'score'         => 15.5,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.score', 15.5);
    }

    // ── PP/PT uniqueness ──────────────────────────────────────────────────────

    public function test_duplicate_pp_is_rejected(): void
    {
        $pp1 = $this->createAssessment('PP');
        $pp2 = Assessment::factory()->create([
            'school_id'       => $this->school->id,
            'academic_year_id'=> $this->year->id,
            'term_id'         => $this->term->id,
            'class_id'        => $this->class->id,
            'subject_id'      => $this->subject->id,
            'teacher_assignment_id' => $this->assignment->id,
            'type'            => 'PP',
            'is_active'       => true,
        ]);

        // First PP grade
        $this->actingAs($this->teacher)->postJson("/api/v1/assessments/{$pp1->id}/grades", [
            'enrollment_id' => $this->enrollment->id,
            'score'         => 12,
        ]);

        // Second PP grade on a different assessment but same subject/term
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/assessments/{$pp2->id}/grades", [
                'enrollment_id' => $this->enrollment->id,
                'score'         => 14,
            ]);

        $response->assertStatus(422);
    }

    public function test_ac_allows_multiple_grades(): void
    {
        $ac1 = $this->createAssessment('AC', 'AC 1');
        $ac2 = Assessment::factory()->create([
            'school_id'       => $this->school->id,
            'academic_year_id'=> $this->year->id,
            'term_id'         => $this->term->id,
            'class_id'        => $this->class->id,
            'subject_id'      => $this->subject->id,
            'teacher_assignment_id' => $this->assignment->id,
            'type'            => 'AC',
            'label'           => 'AC 2',
            'is_active'       => true,
        ]);

        $this->actingAs($this->teacher)->postJson("/api/v1/assessments/{$ac1->id}/grades", [
            'enrollment_id' => $this->enrollment->id,
            'score'         => 14,
        ])->assertStatus(201);

        $this->actingAs($this->teacher)->postJson("/api/v1/assessments/{$ac2->id}/grades", [
            'enrollment_id' => $this->enrollment->id,
            'score'         => 16,
        ])->assertStatus(201);

        $this->assertDatabaseCount('grades', 2);
    }

    // ── Grade book state ──────────────────────────────────────────────────────

    public function test_cannot_add_grade_when_grade_book_not_draft(): void
    {
        $this->gradeBook->update(['status' => 'submitted']);
        $assessment = $this->createAssessment('AC');

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/assessments/{$assessment->id}/grades", [
                'enrollment_id' => $this->enrollment->id,
                'score'         => 15,
            ]);

        $response->assertStatus(422);
    }

    // ── Annulment ─────────────────────────────────────────────────────────────

    public function test_coordinator_can_annul_grade(): void
    {
        $assessment = $this->createAssessment('AC');
        $grade = Grade::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'grade_book_id' => $this->gradeBook->id,
            'score'         => 15,
            'is_annulled'   => false,
        ]);

        $response = $this->actingAs($this->coordinator)
            ->postJson("/api/v1/assessments/{$assessment->id}/grades/{$grade->id}/annul", [
                'annulled_reason' => 'Nota lançada por engano. Correcção necessária.',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('grades', ['id' => $grade->id, 'is_annulled' => 1]);
    }

    public function test_annulment_requires_reason(): void
    {
        $assessment = $this->createAssessment('AC');
        $grade = Grade::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'grade_book_id' => $this->gradeBook->id,
            'score'         => 15,
            'is_annulled'   => false,
        ]);

        $response = $this->actingAs($this->coordinator)
            ->postJson("/api/v1/assessments/{$assessment->id}/grades/{$grade->id}/annul", []);

        $response->assertStatus(422)->assertJsonValidationErrors(['annulled_reason']);
    }

    // ── School isolation ──────────────────────────────────────────────────────

    public function test_school_isolation_prevents_cross_school_access(): void
    {
        $otherSchool = School::factory()->create();
        $otherYear   = AcademicYear::factory()->create(['school_id' => $otherSchool->id]);
        $otherTerm   = Term::factory()->create(['academic_year_id' => $otherYear->id]);
        $otherClass  = SchoolClass::factory()->create(['school_id' => $otherSchool->id, 'academic_year_id' => $otherYear->id]);
        $otherSubject= Subject::factory()->create(['school_id' => $otherSchool->id]);
        $otherGB     = GradeBook::factory()->create([
            'school_id'       => $otherSchool->id,
            'academic_year_id'=> $otherYear->id,
            'term_id'         => $otherTerm->id,
            'class_id'        => $otherClass->id,
            'subject_id'      => $otherSubject->id,
            'status'          => 'draft',
        ]);

        // Teacher from school 1 cannot access school 2 grade book
        $response = $this->actingAs($this->teacher)
            ->getJson("/api/v1/grade-books/{$otherGB->id}");

        // Either 404 (school scope) or 403
        $this->assertContains($response->status(), [403, 404]);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function createAssessment(string $type, ?string $label = null): Assessment
    {
        return Assessment::factory()->create([
            'school_id'             => $this->school->id,
            'academic_year_id'      => $this->year->id,
            'term_id'               => $this->term->id,
            'class_id'              => $this->class->id,
            'subject_id'            => $this->subject->id,
            'teacher_assignment_id' => $this->assignment->id,
            'type'                  => $type,
            'label'                 => $label,
            'is_active'             => true,
        ]);
    }
}
