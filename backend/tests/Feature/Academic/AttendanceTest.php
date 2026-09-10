<?php

namespace Tests\Feature\Academic;

use App\Enums\AttendancePolicyModeEnum;
use App\Enums\AttendanceStatusEnum;
use App\Models\AcademicYear;
use App\Models\AttendancePolicySetting;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\User;
use App\Services\AttendanceService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $teacher;
    private SchoolClass $class;
    private Subject $subject;
    private Term $term;
    private AcademicYear $year;
    private TeacherAssignment $assignment;
    private Enrollment $enrollment;
    private AttendancePolicySetting $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school  = School::factory()->create();
        $this->year    = AcademicYear::factory()->create(['school_id' => $this->school->id]);
        $this->term    = Term::factory()->create(['academic_year_id' => $this->year->id]);
        $this->subject = Subject::factory()->create(['school_id' => $this->school->id]);
        $this->class   = SchoolClass::factory()->create([
            'school_id'        => $this->school->id,
            'academic_year_id' => $this->year->id,
        ]);

        $teacherRole = Role::where('name', 'teacher')->first();
        $this->teacher = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacher->roles()->attach($teacherRole->id, ['school_id' => $this->school->id]);
        $teacherProfile = Teacher::factory()->create(['school_id' => $this->school->id, 'user_id' => $this->teacher->id]);

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

        $this->policy = AttendancePolicySetting::create([
            'school_id'        => $this->school->id,
            'academic_year_id' => $this->year->id,
            'mode'             => AttendancePolicyModeEnum::ANGOLA_POR_DISCIPLINA->value,
            'angola_limits'    => ['1' => 3, '2' => 4, '3+' => 5],
            'alert_threshold'  => 0.80,
        ]);
    }

    // ── Attendance statuses ───────────────────────────────────────────────────

    public function test_create_session_with_present_status(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/attendance-sessions', [
                'academic_year_id'      => $this->year->id,
                'term_id'               => $this->term->id,
                'class_id'              => $this->class->id,
                'subject_id'            => $this->subject->id,
                'teacher_assignment_id' => $this->assignment->id,
                'date'                  => '2026-03-01',
                'weekly_periods'        => 2,
                'records'               => [
                    ['enrollment_id' => $this->enrollment->id, 'status' => 'P'],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendance_records', [
            'enrollment_id' => $this->enrollment->id,
            'status'        => 'P',
        ]);
    }

    public function test_create_session_with_justified_absence(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/attendance-sessions', [
                'academic_year_id'      => $this->year->id,
                'term_id'               => $this->term->id,
                'class_id'              => $this->class->id,
                'subject_id'            => $this->subject->id,
                'teacher_assignment_id' => $this->assignment->id,
                'date'                  => '2026-03-02',
                'weekly_periods'        => 2,
                'records'               => [
                    ['enrollment_id' => $this->enrollment->id, 'status' => 'J', 'justification' => 'Doença comprovada'],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendance_records', ['status' => 'J']);
    }

    public function test_create_session_with_unjustified_absence(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/attendance-sessions', [
                'academic_year_id'      => $this->year->id,
                'term_id'               => $this->term->id,
                'class_id'              => $this->class->id,
                'subject_id'            => $this->subject->id,
                'teacher_assignment_id' => $this->assignment->id,
                'date'                  => '2026-03-03',
                'weekly_periods'        => 2,
                'records'               => [
                    ['enrollment_id' => $this->enrollment->id, 'status' => 'F'],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('attendance_records', ['status' => 'F']);
    }

    // ── Summary calculation ───────────────────────────────────────────────────

    public function test_attendance_summary_calculates_correctly(): void
    {
        $service = app(AttendanceService::class);

        // Create 3 sessions: 2 present, 1 absent
        $sessions = [
            ['date' => '2026-03-01', 'status' => 'P'],
            ['date' => '2026-03-02', 'status' => 'P'],
            ['date' => '2026-03-03', 'status' => 'F'],
        ];

        foreach ($sessions as $s) {
            $session = AttendanceSession::create([
                'school_id'        => $this->school->id,
                'academic_year_id' => $this->year->id,
                'term_id'          => $this->term->id,
                'class_id'         => $this->class->id,
                'subject_id'       => $this->subject->id,
                'teacher_assignment_id' => $this->assignment->id,
                'date'             => $s['date'],
                'weekly_periods'   => 2,
                'recorded_by'      => $this->teacher->id,
            ]);

            AttendanceRecord::create([
                'attendance_session_id' => $session->id,
                'enrollment_id'         => $this->enrollment->id,
                'status'                => $s['status'],
            ]);
        }

        $summary = $service->calculateSummary($this->enrollment, $this->subject->id, $this->term->id);

        $this->assertEquals(3, $summary['total_sessions']);
        $this->assertEquals(2, $summary['presences']);
        $this->assertEquals(1, $summary['absences_unjustified']);
        $this->assertEqualsWithDelta(66.67, $summary['attendance_percentage'], 0.1);
    }

    // ── Angola policy ─────────────────────────────────────────────────────────

    public function test_angola_policy_limit_1_period(): void
    {
        $this->assertEquals(3, $this->policy->getAngolaLimit(1));
    }

    public function test_angola_policy_limit_2_periods(): void
    {
        $this->assertEquals(4, $this->policy->getAngolaLimit(2));
    }

    public function test_angola_policy_limit_3_plus_periods(): void
    {
        $this->assertEquals(5, $this->policy->getAngolaLimit(3));
        $this->assertEquals(5, $this->policy->getAngolaLimit(5));
    }

    public function test_angola_policy_does_not_count_justified(): void
    {
        $this->assertFalse($this->policy->countsJustified());
    }

    // ── Custom policy ─────────────────────────────────────────────────────────

    public function test_custom_policy_counts_justified_when_configured(): void
    {
        $customPolicy = new AttendancePolicySetting([
            'mode'            => AttendancePolicyModeEnum::ESCOLA_PROPRIA->value,
            'custom_settings' => ['count_justified' => true, 'global_limit' => 10],
            'alert_threshold' => 0.80,
        ]);

        $this->assertTrue($customPolicy->countsJustified());
    }

    // ── Attendance Summary API ────────────────────────────────────────────────

    public function test_attendance_summary_api(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson("/api/v1/attendance/summary?enrollment_id={$this->enrollment->id}&subject_id={$this->subject->id}&term_id={$this->term->id}");

        $response->assertOk()
            ->assertJsonStructure(['message', 'data' => [
                'total_sessions',
                'presences',
                'absences_justified',
                'absences_unjustified',
                'total_absences',
                'attendance_percentage',
            ]]);
    }
}
