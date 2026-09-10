<?php

namespace Tests\Unit;

use App\Enums\AssessmentTypeEnum;
use App\Models\Assessment;
use App\Models\AssessmentSetting;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeBook;
use App\Models\SchoolClass;
use App\Services\GradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingServiceTest extends TestCase
{
    use RefreshDatabase;

    private GradingService $service;
    private AssessmentSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GradingService();

        $this->settings = new AssessmentSetting([
            'min_grade'     => 0,
            'max_grade'     => 20,
            'passing_grade' => 10,
            'num_terms'     => 3,
            'nf_formula'    => '(MAC + PP + PT) / 3',
            'mfa_formula'   => '(NF1 + NF2 + NF3) / 3',
            'rounding_mode' => 'half_up',
            'pp_active'     => true,
        ]);
    }

    // ── Rounding ──────────────────────────────────────────────────────────────

    public function test_round_grade_half_up_nine_point_five(): void
    {
        $result = $this->service->roundGrade(9.5, 'half_up');
        $this->assertSame(10, $result);
    }

    public function test_round_grade_half_up_nine_point_four_nine(): void
    {
        $result = $this->service->roundGrade(9.49, 'half_up');
        $this->assertSame(9, $result);
    }

    public function test_round_grade_half_up_fourteen_point_five(): void
    {
        $result = $this->service->roundGrade(14.5, 'half_up');
        $this->assertSame(15, $result);
    }

    public function test_round_grade_null_returns_null(): void
    {
        $this->assertNull($this->service->roundGrade(null));
    }

    // ── NF formula evaluation ─────────────────────────────────────────────────

    public function test_evaluate_nf_formula_standard(): void
    {
        // (12 + 14 + 16) / 3 = 14.0
        $nf = $this->service->evaluateNfFormula('(MAC + PP + PT) / 3', 12.0, 14.0, 16.0, true);
        $this->assertEqualsWithDelta(14.0, $nf, 0.001);
    }

    public function test_evaluate_nf_formula_null_mac_returns_null(): void
    {
        $nf = $this->service->evaluateNfFormula('(MAC + PP + PT) / 3', null, 14.0, 16.0, true);
        $this->assertNull($nf);
    }

    public function test_evaluate_nf_formula_null_pt_returns_null(): void
    {
        $nf = $this->service->evaluateNfFormula('(MAC + PP + PT) / 3', 12.0, 14.0, null, true);
        $this->assertNull($nf);
    }

    public function test_evaluate_nf_formula_without_pp(): void
    {
        // PP inactive: (MAC + PT) / 2
        $nf = $this->service->evaluateNfFormula('(MAC + PP + PT) / 3', 12.0, null, 16.0, false);
        $this->assertEqualsWithDelta(14.0, $nf, 0.001);
    }

    // ── MFA formula ───────────────────────────────────────────────────────────

    public function test_evaluate_mfa_formula_standard(): void
    {
        // (12 + 14 + 16) / 3 = 14.0
        $mfa = $this->service->evaluateMfaFormula('(NF1 + NF2 + NF3) / 3', [12.0, 14.0, 16.0]);
        $this->assertEqualsWithDelta(14.0, $mfa, 0.001);
    }

    public function test_evaluate_mfa_formula_rounding_half_up(): void
    {
        // NF average = 9.5 → should round to 10
        $mfa = $this->service->evaluateMfaFormula('(NF1 + NF2 + NF3) / 3', [9.0, 9.5, 10.0]);
        $rounded = $this->service->roundGrade($mfa, 'half_up');
        $this->assertSame(10, $rounded);
    }

    public function test_evaluate_mfa_empty_returns_null(): void
    {
        $this->assertNull($this->service->evaluateMfaFormula('(NF1 + NF2 + NF3) / 3', []));
    }

    // ── Situation determination ───────────────────────────────────────────────

    public function test_determine_situation_approved(): void
    {
        $situation = $this->service->determineTermSituation(14.0, $this->settings);
        $this->assertEquals('aprovado', $situation->value);
    }

    public function test_determine_situation_failed(): void
    {
        $situation = $this->service->determineTermSituation(9.0, $this->settings);
        $this->assertEquals('reprovado_por_nota', $situation->value);
    }

    public function test_determine_situation_null_returns_sem_notas(): void
    {
        $situation = $this->service->determineTermSituation(null, $this->settings);
        $this->assertEquals('sem_notas', $situation->value);
    }

    public function test_grade_at_exactly_passing_is_approved(): void
    {
        // 9.5 rounds to 10 → approved
        $situation = $this->service->determineTermSituation(9.5, $this->settings);
        $this->assertEquals('aprovado', $situation->value);
    }

    public function test_grade_just_below_passing_after_rounding_fails(): void
    {
        // 9.49 rounds to 9 → failed
        $situation = $this->service->determineTermSituation(9.49, $this->settings);
        $this->assertEquals('reprovado_por_nota', $situation->value);
    }
}
