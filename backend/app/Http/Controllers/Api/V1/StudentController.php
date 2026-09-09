<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Student::with('guardians');

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('student_number', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $authUser = $request->user();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'birth_date' => 'required|date',
            'birth_place' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:100',
            'bi_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        // Determine school_id: super admin must provide it, others inherit theirs
        if (!$authUser->school_id) {
            $request->validate(['school_id' => 'required|exists:schools,id']);
            $validated['school_id'] = $request->input('school_id');
        } else {
            $validated['school_id'] = $authUser->school_id;
        }

        $validated['student_number'] = $this->generateStudentNumber($validated['school_id']);

        $student = Student::create($validated);

        return response()->json([
            'message' => 'Aluno registado com sucesso.',
            'data' => $student,
        ], 201);
    }

    public function show(Student $student): JsonResponse
    {
        // School isolation enforced by BelongsToSchool global scope + EnsureBelongsToSchool middleware
        return response()->json([
            'data' => $student->load('guardians', 'enrollments.schoolClass'),
        ]);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|in:M,F',
            'birth_date' => 'sometimes|date',
            'birth_place' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:100',
            'bi_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $student->update($validated);

        return response()->json([
            'message' => 'Aluno actualizado com sucesso.',
            'data' => $student,
        ]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $student->delete();

        return response()->json([
            'message' => 'Aluno removido com sucesso.',
        ]);
    }

    /**
     * POST /api/v1/students/{student}/guardians
     */
    public function attachGuardian(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'guardian_id' => 'required|exists:guardians,id',
            'is_primary' => 'sometimes|boolean',
        ]);

        // Validate that the guardian belongs to the same school as the student
        $guardian = Guardian::find($validated['guardian_id']);
        if ($guardian && $student->school_id && $guardian->school_id !== $student->school_id) {
            abort(403, 'O encarregado não pertence à mesma escola do aluno.');
        }

        $student->guardians()->syncWithoutDetaching([
            $validated['guardian_id'] => ['is_primary' => $validated['is_primary'] ?? false],
        ]);

        return response()->json([
            'message' => 'Encarregado associado com sucesso.',
            'data' => $student->load('guardians'),
        ]);
    }

    /**
     * Generate a unique student number with retry to avoid race conditions.
     * Uses the unique constraint on (school_id, student_number) as safety net.
     */
    private function generateStudentNumber(int $schoolId): string
    {
        $year = now()->format('Y');
        $maxRetries = 5;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $count = Student::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->whereYear('created_at', $year)
                ->count() + 1 + $attempt;

            $number = sprintf('ALU-%s-%05d', $year, $count);

            // Check uniqueness before returning
            $exists = Student::withoutGlobalScopes()
                ->where('student_number', $number)
                ->exists();

            if (!$exists) {
                return $number;
            }
        }

        // Fallback: use timestamp-based suffix
        return sprintf('ALU-%s-%05d', $year, time() % 100000);
    }
}
