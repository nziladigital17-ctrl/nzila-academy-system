<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentGuardianRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\GuardianResource;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('guardians');

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        return StudentResource::collection($query->paginate(15));
    }

    public function store(StoreStudentRequest $request)
    {
        $validated = $request->validated();

        if (!$request->user()->school_id) {
            $request->validate(['school_id' => 'required|exists:schools,id']);
            $validated['school_id'] = $request->input('school_id');
        } else {
            $validated['school_id'] = $request->user()->school_id;
        }

        $validated['student_number'] = $this->generateStudentNumber($validated['school_id']);

        $student = Student::create($validated);

        return (new StudentResource($student))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Student $student)
    {
        return new StudentResource($student->load('guardians', 'enrollments.schoolClass'));
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        $student->update($request->validated());

        return new StudentResource($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return response()->json(['message' => 'Aluno removido com sucesso.']);
    }

    /**
     * GET /api/v1/students/{student}/guardians
     */
    public function listGuardians(Student $student)
    {
        return GuardianResource::collection($student->guardians);
    }

    /**
     * POST /api/v1/students/{student}/guardians
     */
    public function attachGuardian(StoreStudentGuardianRequest $request, Student $student)
    {
        $validated = $request->validated();

        // If setting as primary, ensure only one primary per student
        if (!empty($validated['is_primary']) && $validated['is_primary']) {
            // Remove existing primary
            DB::table('student_guardians')
                ->where('student_id', $student->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $student->guardians()->syncWithoutDetaching([
            $validated['guardian_id'] => [
                'is_primary' => $validated['is_primary'] ?? false,
                'relationship' => $validated['relationship'],
            ],
        ]);

        return response()->json([
            'message' => 'Encarregado associado com sucesso.',
            'data' => GuardianResource::collection($student->load('guardians')->guardians),
        ]);
    }

    /**
     * DELETE /api/v1/students/{student}/guardians/{guardian}
     */
    public function detachGuardian(Student $student, int $guardian)
    {
        $student->guardians()->detach($guardian);

        return response()->json(['message' => 'Encarregado desassociado com sucesso.']);
    }

    /**
     * Generate a unique student number with retry to avoid race conditions.
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

            $exists = Student::withoutGlobalScopes()
                ->where('student_number', $number)
                ->where('school_id', $schoolId)
                ->exists();

            if (!$exists) {
                return $number;
            }
        }

        return sprintf('ALU-%s-%05d', $year, time() % 100000);
    }
}
