<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceSessionRequest;
use App\Http\Requests\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Http\Resources\AttendanceSessionResource;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceSessionController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    /**
     * List attendance sessions with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = Auth::user();
        $query = AttendanceSession::with(['subject', 'term', 'schoolClass'])
            ->when($request->term_id, fn($q, $t) => $q->where('term_id', $t))
            ->when($request->class_id, fn($q, $c) => $q->where('class_id', $c))
            ->when($request->subject_id, fn($q, $s) => $q->where('subject_id', $s))
            ->when($request->date, fn($q, $d) => $q->where('date', $d))
            ->when($request->academic_year_id, fn($q, $y) => $q->where('academic_year_id', $y));

        // Date range filters
        if ($request->from_date) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->where('date', '<=', $request->to_date);
        }

        // Week filter (ISO week number)
        if ($request->week && $request->year) {
            $query->whereRaw('WEEK(date, 1) = ? AND YEAR(date) = ?', [$request->week, $request->year]);
        }

        // Month filter
        if ($request->month && $request->year) {
            $query->whereMonth('date', $request->month)->whereYear('date', $request->year);
        }

        // Teachers: restrict to their own assignments
        if ($user->hasRole('teacher')) {
            $teacher = $user->teacher;
            $myAssignmentIds = $teacher
                ? \App\Models\TeacherAssignment::where('teacher_id', $teacher->id)->pluck('id')
                : collect();
            $query->whereIn('teacher_assignment_id', $myAssignmentIds);
        }

        $sessions = $query->orderBy('date', 'desc')->paginate(50);

        return response()->json([
            'message' => 'Sessões de presença listadas com sucesso.',
            'data'    => AttendanceSessionResource::collection($sessions->items()),
            'meta'    => [
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
                'total'        => $sessions->total(),
            ],
        ]);
    }

    /**
     * Show a session with its records.
     */
    public function show(AttendanceSession $attendanceSession): JsonResponse
    {
        $attendanceSession->load(['subject', 'term', 'schoolClass', 'attendanceRecords.enrollment.student']);

        return response()->json([
            'message' => 'Sessão de presença obtida com sucesso.',
            'data'    => new AttendanceSessionResource($attendanceSession),
        ]);
    }

    /**
     * Create an attendance session with optional per-student records.
     */
    public function store(StoreAttendanceSessionRequest $request): JsonResponse
    {
        $data    = $request->safe()->except('records');
        $data['school_id'] = Auth::user()->school_id;
        $records = $request->input('records', []);

        $session = $this->attendanceService->recordSession($data, $records);

        return response()->json([
            'message' => 'Sessão de presença registada com sucesso.',
            'data'    => new AttendanceSessionResource($session->load('subject', 'term', 'schoolClass', 'attendanceRecords.enrollment.student')),
        ], 201);
    }

    /**
     * List attendance records for a session.
     */
    public function records(AttendanceSession $attendanceSession): JsonResponse
    {
        $records = $attendanceSession->attendanceRecords()
            ->with('enrollment.student')
            ->get();

        return response()->json([
            'message' => 'Registos de presença listados com sucesso.',
            'data'    => AttendanceRecordResource::collection($records),
        ]);
    }

    /**
     * Update a single attendance record.
     */
    public function updateRecord(UpdateAttendanceRecordRequest $request, AttendanceSession $attendanceSession, AttendanceRecord $attendanceRecord): JsonResponse
    {
        if ($attendanceRecord->attendance_session_id !== $attendanceSession->id) {
            abort(404);
        }

        $record = $this->attendanceService->updateRecord(
            $attendanceRecord,
            $request->status,
            $request->justification
        );

        // Update attendance counts in term result
        $enrollment = $record->enrollment()->with('schoolClass')->first();
        if ($enrollment) {
            $this->attendanceService->updateTermResultAttendance(
                $enrollment,
                $attendanceSession->subject_id,
                $attendanceSession->term_id
            );
        }

        return response()->json([
            'message' => 'Registo de presença actualizado com sucesso.',
            'data'    => new AttendanceRecordResource($record->load('enrollment.student')),
        ]);
    }
}
