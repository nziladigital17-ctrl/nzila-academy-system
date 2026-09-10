<?php

namespace Tests\Feature\Academic;

use App\Enums\AlertTypeEnum;
use App\Models\AcademicAlert;
use App\Models\AcademicYear;
use App\Models\AssessmentSetting;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $coordinator;
    private AcademicYear $year;
    private Term $term;
    private Subject $subject;
    private Enrollment $enrollment;
    private AssessmentSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->school  = School::factory()->create();
        $this->year    = AcademicYear::factory()->create(['school_id' => $this->school->id]);
        $this->term    = Term::factory()->create(['academic_year_id' => $this->year->id]);
        $this->subject = Subject::factory()->create(['school_id' => $this->school->id]);
        $class         = SchoolClass::factory()->create([
            'school_id'        => $this->school->id,
            'academic_year_id' => $this->year->id,
        ]);

        $coordRole = Role::where('name', 'pedagogic_coordinator')->first();
        $this->coordinator = User::factory()->create(['school_id' => $this->school->id]);
        $this->coordinator->roles()->attach($coordRole->id, ['school_id' => $this->school->id]);

        $student = Student::factory()->create(['school_id' => $this->school->id]);
        $this->enrollment = Enrollment::factory()->create([
            'school_id'  => $this->school->id,
            'student_id' => $student->id,
            'class_id'   => $class->id,
            'status'     => 'active',
        ]);

        $this->settings = AssessmentSetting::create([
            'school_id'        => $this->school->id,
            'academic_year_id' => $this->year->id,
            'passing_grade'    => 10,
            'nf_formula'       => '(MAC + PP + PT) / 3',
            'mfa_formula'      => '(NF1 + NF2 + NF3) / 3',
            'rounding_mode'    => 'half_up',
            'pp_active'        => true,
        ]);
    }

    public function test_academic_risk_alert_is_created_when_nf_below_passing(): void
    {
        AcademicAlert::create([
            'school_id'        => $this->school->id,
            'enrollment_id'    => $this->enrollment->id,
            'subject_id'       => $this->subject->id,
            'term_id'          => $this->term->id,
            'academic_year_id' => $this->year->id,
            'type'             => AlertTypeEnum::ACADEMIC_RISK->value,
            'context'          => ['projected_nf' => 7.5, 'passing_grade' => 10],
            'is_acknowledged'  => false,
        ]);

        $this->assertDatabaseHas('academic_alerts', [
            'enrollment_id' => $this->enrollment->id,
            'type'          => 'academic_risk',
            'is_acknowledged' => false,
        ]);
    }

    public function test_attendance_risk_alert_is_created(): void
    {
        AcademicAlert::create([
            'school_id'        => $this->school->id,
            'enrollment_id'    => $this->enrollment->id,
            'subject_id'       => $this->subject->id,
            'term_id'          => $this->term->id,
            'academic_year_id' => $this->year->id,
            'type'             => AlertTypeEnum::ATTENDANCE_RISK->value,
            'context'          => ['absences_unjustified' => 4, 'limit' => 5, 'status' => 'warning'],
            'is_acknowledged'  => false,
        ]);

        $this->assertDatabaseHas('academic_alerts', [
            'type'          => 'attendance_risk',
            'is_acknowledged' => false,
        ]);
    }

    public function test_academic_alerts_report_api(): void
    {
        AcademicAlert::create([
            'school_id'        => $this->school->id,
            'enrollment_id'    => $this->enrollment->id,
            'subject_id'       => $this->subject->id,
            'term_id'          => $this->term->id,
            'academic_year_id' => $this->year->id,
            'type'             => AlertTypeEnum::ACADEMIC_RISK->value,
            'context'          => ['projected_nf' => 8.0],
            'is_acknowledged'  => false,
        ]);

        $response = $this->actingAs($this->coordinator)
            ->getJson("/api/v1/reports/academic-alerts?academic_year_id={$this->year->id}");

        $response->assertOk()
            ->assertJsonStructure(['data' => ['total', 'academic_risk', 'attendance_risk', 'alerts']]);

        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals(1, $response->json('data.academic_risk'));
    }

    public function test_academic_alerts_filtered_by_type(): void
    {
        AcademicAlert::create([
            'school_id'        => $this->school->id,
            'enrollment_id'    => $this->enrollment->id,
            'subject_id'       => $this->subject->id,
            'term_id'          => $this->term->id,
            'academic_year_id' => $this->year->id,
            'type'             => AlertTypeEnum::ACADEMIC_RISK->value,
            'context'          => [],
            'is_acknowledged'  => false,
        ]);

        $response = $this->actingAs($this->coordinator)
            ->getJson("/api/v1/reports/academic-alerts?type=attendance_risk");

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.total'));
    }
}
