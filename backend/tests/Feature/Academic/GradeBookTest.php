<?php

namespace Tests\Feature\Academic;

use App\Enums\GradeBookStatusEnum;
use App\Models\AcademicYear;
use App\Models\GradeBook;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeBookTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $teacher;
    private User $coordinator;
    private GradeBook $gradeBook;
    private SchoolClass $class;
    private Subject $subject;
    private Term $term;
    private AcademicYear $year;
    private TeacherAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school  = School::factory()->create();
        $this->year    = AcademicYear::factory()->create(['school_id' => $this->school->id]);
        $this->term    = Term::factory()->create(['academic_year_id' => $this->year->id]);
        $this->subject = Subject::factory()->create(['school_id' => $this->school->id]);
        $this->class   = SchoolClass::factory()->create([
            'school_id'       => $this->school->id,
            'academic_year_id'=> $this->year->id,
        ]);

        $teacherRole = Role::where('name', 'teacher')->first();
        $coordRole   = Role::where('name', 'pedagogic_coordinator')->first();

        $this->teacher = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacher->roles()->attach($teacherRole->id, ['school_id' => $this->school->id]);
        $teacherProfile = Teacher::factory()->create(['school_id' => $this->school->id, 'user_id' => $this->teacher->id]);

        $this->coordinator = User::factory()->create(['school_id' => $this->school->id]);
        $this->coordinator->roles()->attach($coordRole->id, ['school_id' => $this->school->id]);

        $this->assignment = TeacherAssignment::factory()->create([
            'school_id'        => $this->school->id,
            'academic_year_id' => $this->year->id,
            'teacher_id'       => $teacherProfile->id,
            'class_id'         => $this->class->id,
            'subject_id'       => $this->subject->id,
        ]);

        $this->gradeBook = GradeBook::factory()->create([
            'school_id'             => $this->school->id,
            'academic_year_id'      => $this->year->id,
            'term_id'               => $this->term->id,
            'class_id'              => $this->class->id,
            'subject_id'            => $this->subject->id,
            'teacher_assignment_id' => $this->assignment->id,
            'status'                => GradeBookStatusEnum::DRAFT->value,
        ]);
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_teacher_can_create_grade_book(): void
    {
        $otherTerm    = Term::factory()->create(['academic_year_id' => $this->year->id]);
        $otherSubject = Subject::factory()->create(['school_id' => $this->school->id]);

        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/grade-books', [
                'academic_year_id'      => $this->year->id,
                'term_id'               => $otherTerm->id,
                'class_id'              => $this->class->id,
                'subject_id'            => $otherSubject->id,
                'teacher_assignment_id' => $this->assignment->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_duplicate_grade_book_returns_422(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/grade-books', [
                'academic_year_id' => $this->year->id,
                'term_id'          => $this->term->id,
                'class_id'         => $this->class->id,
                'subject_id'       => $this->subject->id,
            ]);

        $response->assertStatus(422);
    }

    // ── Submit ────────────────────────────────────────────────────────────────

    public function test_teacher_can_submit_grade_book(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/grade-books/{$this->gradeBook->id}/submit");

        $response->assertOk()
            ->assertJsonPath('data.status', 'submitted');
    }

    public function test_cannot_submit_already_submitted_grade_book(): void
    {
        $this->gradeBook->update(['status' => 'submitted']);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/grade-books/{$this->gradeBook->id}/submit");

        $response->assertStatus(422);
    }

    // ── Publish ───────────────────────────────────────────────────────────────

    public function test_coordinator_can_publish_grade_book(): void
    {
        $this->gradeBook->update(['status' => 'submitted']);

        $response = $this->actingAs($this->coordinator)
            ->postJson("/api/v1/grade-books/{$this->gradeBook->id}/publish");

        $response->assertOk();
        // After publish, grade book is auto-locked
        $this->assertDatabaseHas('grade_books', ['id' => $this->gradeBook->id, 'status' => 'locked']);
    }

    public function test_teacher_cannot_publish_grade_book(): void
    {
        $this->gradeBook->update(['status' => 'submitted']);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/grade-books/{$this->gradeBook->id}/publish");

        $response->assertStatus(403);
    }

    // ── Unlock ────────────────────────────────────────────────────────────────

    public function test_coordinator_can_unlock_with_reason(): void
    {
        $this->gradeBook->update(['status' => 'locked']);

        $response = $this->actingAs($this->coordinator)
            ->postJson("/api/v1/grade-books/{$this->gradeBook->id}/unlock", [
                'unlock_reason' => 'Correcção de gralha na nota do aluno número 5.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'published');
    }

    public function test_unlock_requires_reason(): void
    {
        $this->gradeBook->update(['status' => 'locked']);

        $response = $this->actingAs($this->coordinator)
            ->postJson("/api/v1/grade-books/{$this->gradeBook->id}/unlock", []);

        $response->assertStatus(422)->assertJsonValidationErrors(['unlock_reason']);
    }

    public function test_teacher_cannot_unlock_grade_book(): void
    {
        $this->gradeBook->update(['status' => 'locked']);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/grade-books/{$this->gradeBook->id}/unlock", [
                'unlock_reason' => 'Tentativa não autorizada.',
            ]);

        $response->assertStatus(403);
    }

    // ── Audit ─────────────────────────────────────────────────────────────────

    public function test_grade_book_status_changes_are_audited(): void
    {
        $this->actingAs($this->teacher)
            ->postJson("/api/v1/grade-books/{$this->gradeBook->id}/submit");

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => GradeBook::class,
            'auditable_id'   => $this->gradeBook->id,
            'action'         => 'updated',
        ]);
    }
}
