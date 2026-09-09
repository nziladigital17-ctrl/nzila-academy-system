<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Grade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GradeService
{
    /**
     * Submit grades in batch for an assessment.
     */
    public function submitGrades(Assessment $assessment, array $gradesData): array
    {
        return DB::transaction(function () use ($assessment, $gradesData) {
            $grades = [];

            foreach ($gradesData as $data) {
                if ($data['score'] > $assessment->max_score) {
                    throw new \RuntimeException(
                        "A nota {$data['score']} excede o máximo ({$assessment->max_score}) para a matrícula {$data['enrollment_id']}."
                    );
                }

                $grades[] = Grade::updateOrCreate(
                    [
                        'assessment_id' => $assessment->id,
                        'enrollment_id' => $data['enrollment_id'],
                    ],
                    [
                        'score' => $data['score'],
                        'remarks' => $data['remarks'] ?? null,
                        'graded_by' => Auth::id(),
                    ]
                );
            }

            return $grades;
        });
    }
}
