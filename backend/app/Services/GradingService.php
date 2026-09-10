<?php

namespace App\Services;

use App\Enums\AcademicSituationEnum;
use App\Enums\AlertTypeEnum;
use App\Enums\AssessmentTypeEnum;
use App\Enums\GradeBookStatusEnum;
use App\Models\AcademicAlert;
use App\Models\AnnualResult;
use App\Models\Assessment;
use App\Models\AssessmentSetting;
use App\Models\Enrollment;
use App\Models\GradeBook;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradingService
{
    // ── Rounding ──────────────────────────────────────────────────────────────

    /**
     * Round a grade value according to the configured rounding mode.
     * Default: half_up (9.5 → 10, 9.49 → 9).
     */
    public function roundGrade(?float $value, string $mode = 'half_up'): ?int
    {
        if ($value === null) {
            return null;
        }

        return match ($mode) {
            'half_up'   => (int) round($value, 0, PHP_ROUND_HALF_UP),
            'half_down' => (int) round($value, 0, PHP_ROUND_HALF_DOWN),
            'half_even' => (int) round($value, 0, PHP_ROUND_HALF_EVEN),
            default     => (int) round($value, 0, PHP_ROUND_HALF_UP),
        };
    }

    // ── MAC calculation ──────────────────────────────────────────────────────

    /**
     * Calculate MAC (Média de Avaliação Contínua).
     * Simple arithmetic mean of all active AC grades for the enrollment in the term/subject.
     * Returns null if no AC grades exist.
     */
    public function calculateMac(Enrollment $enrollment, int $subjectId, int $termId): ?float
    {
        $acGrades = $this->getActiveGrades($enrollment->id, $subjectId, $termId, AssessmentTypeEnum::AC);

        if ($acGrades->isEmpty()) {
            return null;
        }

        return $acGrades->avg('score');
    }

    // ── NF calculation ────────────────────────────────────────────────────────

    /**
     * Calculate NF (Nota Final trimestral).
     * Default formula: (MAC + PP + PT) / 3
     * The formula is configurable via AssessmentSetting.nf_formula.
     * Components that are null are excluded from the calculation only if the formula supports it.
     * For the default formula: all three must be non-null for NF to be non-null.
     */
    public function calculateNf(
        Enrollment $enrollment,
        int $subjectId,
        int $termId,
        AssessmentSetting $settings
    ): ?float {
        $mac = $this->calculateMac($enrollment, $subjectId, $termId);
        $pp  = $this->getSingleGrade($enrollment->id, $subjectId, $termId, AssessmentTypeEnum::PP);
        $pt  = $this->getSingleGrade($enrollment->id, $subjectId, $termId, AssessmentTypeEnum::PT);

        return $this->evaluateNfFormula($settings->nf_formula, $mac, $pp, $pt, $settings->pp_active);
    }

    /**
     * Evaluate the NF formula.
     * Supported formula: "(MAC + PP + PT) / 3" or variations without PP.
     */
    public function evaluateNfFormula(
        string $formula,
        ?float $mac,
        ?float $pp,
        ?float $pt,
        bool $ppActive = true
    ): ?float {
        // Simplified formula evaluation for the standard formula
        // Future: replace with a proper expression parser for full flexibility
        $formula = trim($formula);

        if ($formula === '(MAC + PP + PT) / 3') {
            if ($mac === null || $pt === null) {
                return null;
            }
            if ($ppActive && $pp === null) {
                return null;
            }
            if ($ppActive) {
                return ($mac + $pp + $pt) / 3;
            }
            return ($mac + $pt) / 2;
        }

        if ($formula === '(MAC + PT) / 2') {
            if ($mac === null || $pt === null) {
                return null;
            }
            return ($mac + $pt) / 2;
        }

        // Default fallback
        if ($mac === null || $pt === null) {
            return null;
        }
        return $ppActive && $pp !== null
            ? ($mac + $pp + $pt) / 3
            : ($mac + $pt) / 2;
    }

    // ── MFA calculation ───────────────────────────────────────────────────────

    /**
     * Calculate MFA (Média Final Anual).
     * Default formula: (NF1 + NF2 + NF3) / 3
     */
    public function calculateMfa(
        Enrollment $enrollment,
        int $subjectId,
        int $academicYearId,
        AssessmentSetting $settings
    ): ?float {
        $terms = Term::where('academic_year_id', $academicYearId)
            ->orderBy('start_date')
            ->get();

        $nfValues = [];
        foreach ($terms as $term) {
            $nf = $this->calculateNf($enrollment, $subjectId, $term->id, $settings);
            if ($nf !== null) {
                $nfValues[] = $nf;
            }
        }

        if (count($nfValues) < count($terms)) {
            // Not all terms have complete grades
            return count($nfValues) > 0
                ? array_sum($nfValues) / count($nfValues)
                : null;
        }

        return $this->evaluateMfaFormula($settings->mfa_formula, $nfValues);
    }

    /**
     * Evaluate the MFA formula.
     */
    public function evaluateMfaFormula(string $formula, array $nfValues): ?float
    {
        if (empty($nfValues)) {
            return null;
        }

        $formula = trim($formula);

        if ($formula === '(NF1 + NF2 + NF3) / 3') {
            return array_sum($nfValues) / 3;
        }

        // Generic: average of all provided NFs
        return array_sum($nfValues) / count($nfValues);
    }

    // ── Full recalculation ────────────────────────────────────────────────────

    /**
     * Recalculate MAC, NF, MFA and situation for a student/subject/term.
     * Called after any grade is created, updated, annulled, or deleted.
     */
    public function recalculate(Enrollment $enrollment, int $subjectId, int $termId): TermResult
    {
        $settings = $this->getSettings($enrollment->school_id, $enrollment->schoolClass->academic_year_id ?? null);

        $mac = $this->calculateMac($enrollment, $subjectId, $termId);
        $pp  = $this->getSingleGrade($enrollment->id, $subjectId, $termId, AssessmentTypeEnum::PP);
        $pt  = $this->getSingleGrade($enrollment->id, $subjectId, $termId, AssessmentTypeEnum::PT);
        $nf  = $this->evaluateNfFormula(
            $settings->nf_formula,
            $mac,
            $pp,
            $pt,
            $settings->pp_active
        );

        $gradeBook = GradeBook::where([
            'school_id'       => $enrollment->school_id,
            'term_id'         => $termId,
            'class_id'        => $enrollment->class_id,
            'subject_id'      => $subjectId,
        ])->first();

        $termResult = TermResult::updateOrCreate(
            [
                'enrollment_id' => $enrollment->id,
                'subject_id'    => $subjectId,
                'term_id'       => $termId,
            ],
            [
                'school_id'       => $enrollment->school_id,
                'academic_year_id'=> $enrollment->schoolClass->academic_year_id ?? null,
                'grade_book_id'   => $gradeBook?->id,
                'mac'             => $mac,
                'pp'              => $pp,
                'pt'              => $pt,
                'nf'              => $nf,
                'situation'       => $this->determineTermSituation($nf, $settings),
                'calculated_at'   => now(),
            ]
        );

        // Also update annual result
        if ($enrollment->schoolClass?->academic_year_id) {
            $this->recalculateAnnual($enrollment, $subjectId, $enrollment->schoolClass->academic_year_id, $settings);
        }

        return $termResult;
    }

    /**
     * Recalculate annual MFA for a student/subject.
     */
    public function recalculateAnnual(
        Enrollment $enrollment,
        int $subjectId,
        int $academicYearId,
        ?AssessmentSetting $settings = null
    ): AnnualResult {
        $settings ??= $this->getSettings($enrollment->school_id, $academicYearId);

        $mfa = $this->calculateMfa($enrollment, $subjectId, $academicYearId, $settings);

        return AnnualResult::updateOrCreate(
            [
                'enrollment_id'    => $enrollment->id,
                'subject_id'       => $subjectId,
                'academic_year_id' => $academicYearId,
            ],
            [
                'school_id'     => $enrollment->school_id,
                'mfa'           => $mfa,
                'situation'     => $this->determineAnnualSituation($mfa, $settings),
                'calculated_at' => now(),
            ]
        );
    }

    // ── Situation determination ───────────────────────────────────────────────

    /**
     * Determine the academic situation for a given term NF.
     */
    public function determineTermSituation(?float $nf, AssessmentSetting $settings): AcademicSituationEnum
    {
        if ($nf === null) {
            return AcademicSituationEnum::SEM_NOTAS;
        }

        $roundedNf = $this->roundGrade($nf, $settings->rounding_mode);

        if ($roundedNf < $settings->passing_grade) {
            // Check if still in progress (projections below passing)
            return AcademicSituationEnum::REPROVADO_POR_NOTA;
        }

        return AcademicSituationEnum::APROVADO;
    }

    /**
     * Determine the annual situation based on MFA.
     */
    public function determineAnnualSituation(?float $mfa, AssessmentSetting $settings): AcademicSituationEnum
    {
        if ($mfa === null) {
            return AcademicSituationEnum::SEM_NOTAS;
        }

        $roundedMfa = $this->roundGrade($mfa, $settings->rounding_mode);

        if ($roundedMfa < $settings->passing_grade) {
            return AcademicSituationEnum::REPROVADO_POR_NOTA;
        }

        return AcademicSituationEnum::APROVADO;
    }

    // ── Alert generation ──────────────────────────────────────────────────────

    /**
     * Check for and generate academic risk alert if NF projection is below passing.
     */
    public function checkAndGenerateAcademicAlert(
        Enrollment $enrollment,
        int $subjectId,
        int $termId,
        int $academicYearId,
        AssessmentSetting $settings
    ): ?AcademicAlert {
        $nf = $this->calculateNf($enrollment, $subjectId, $termId, $settings);

        if ($nf !== null && $nf < $settings->passing_grade) {
            return AcademicAlert::updateOrCreate(
                [
                    'enrollment_id'    => $enrollment->id,
                    'subject_id'       => $subjectId,
                    'term_id'          => $termId,
                    'type'             => AlertTypeEnum::ACADEMIC_RISK->value,
                ],
                [
                    'school_id'        => $enrollment->school_id,
                    'academic_year_id' => $academicYearId,
                    'is_acknowledged'  => false,
                    'context'          => [
                        'projected_nf'  => round($nf, 2),
                        'passing_grade' => $settings->passing_grade,
                    ],
                ]
            );
        }

        return null;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Get all active grades for a type in a term/subject/enrollment.
     */
    private function getActiveGrades(int $enrollmentId, int $subjectId, int $termId, AssessmentTypeEnum $type): Collection
    {
        return \App\Models\Grade::whereHas('assessment', function ($q) use ($subjectId, $termId, $type) {
            $q->where('subject_id', $subjectId)
              ->where('term_id', $termId)
              ->where('type', $type->value)
              ->where('is_active', true);
        })
        ->where('enrollment_id', $enrollmentId)
        ->where('is_annulled', false)
        ->get();
    }

    /**
     * Get the score for a unique-type grade (PP or PT).
     */
    private function getSingleGrade(int $enrollmentId, int $subjectId, int $termId, AssessmentTypeEnum $type): ?float
    {
        $grade = \App\Models\Grade::whereHas('assessment', function ($q) use ($subjectId, $termId, $type) {
            $q->where('subject_id', $subjectId)
              ->where('term_id', $termId)
              ->where('type', $type->value)
              ->where('is_active', true);
        })
        ->where('enrollment_id', $enrollmentId)
        ->where('is_annulled', false)
        ->first();

        return $grade?->score !== null ? (float) $grade->score : null;
    }

    /**
     * Get assessment settings for a school/year, with defaults if not found.
     */
    private function getSettings(?int $schoolId, ?int $academicYearId): AssessmentSetting
    {
        $settings = null;

        if ($schoolId && $academicYearId) {
            $settings = AssessmentSetting::where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->first();
        }

        if (!$settings) {
            // Return default settings object
            $settings = new AssessmentSetting([
                'min_grade'        => 0,
                'max_grade'        => 20,
                'passing_grade'    => 10,
                'num_terms'        => 3,
                'nf_formula'       => '(MAC + PP + PT) / 3',
                'mfa_formula'      => '(NF1 + NF2 + NF3) / 3',
                'rounding_mode'    => 'half_up',
                'pp_active'        => true,
                'active_components'=> ['AC', 'PP', 'PT'],
            ]);
        }

        return $settings;
    }
}
