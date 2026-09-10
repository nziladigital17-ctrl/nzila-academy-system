<?php

use App\Http\Controllers\Api\V1\AcademicResultController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AssessmentController;
use App\Http\Controllers\Api\V1\AssessmentSettingController;
use App\Http\Controllers\Api\V1\AttendanceSessionController;
use App\Http\Controllers\Api\V1\AttendanceSummaryController;
use App\Http\Controllers\Api\V1\ClassController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\GradeBookController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\GuardianController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TermController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\SchoolController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\TeacherAssignmentController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Nzila Academy (/api/v1)
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1 via RouteServiceProvider.
| Authentication via Laravel Sanctum (Bearer token).
|
*/

// ── Public routes ──
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('audit:login');

// ── Authenticated routes ──
Route::middleware(['auth:sanctum', 'school'])->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->middleware('audit:logout');
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/password', [AuthController::class, 'updatePassword'])
        ->middleware('audit:password_change');

    // Schools (super admin)
    Route::middleware('permission:schools.view')->group(function () {
        Route::get('/schools', [SchoolController::class, 'index']);
        Route::get('/schools/{school}', [SchoolController::class, 'show']);
    });
    Route::middleware('permission:schools.create')
        ->post('/schools', [SchoolController::class, 'store']);
    Route::middleware('permission:schools.update')
        ->put('/schools/{school}', [SchoolController::class, 'update']);
    Route::middleware('permission:schools.delete')
        ->delete('/schools/{school}', [SchoolController::class, 'destroy']);

    // Users
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
    });
    Route::middleware('permission:users.create')
        ->post('/users', [UserController::class, 'store']);
    Route::middleware('permission:users.update')
        ->put('/users/{user}', [UserController::class, 'update']);
    Route::middleware('permission:users.delete')
        ->delete('/users/{user}', [UserController::class, 'destroy']);
    Route::middleware('permission:users.assign_roles')->group(function () {
        Route::post('/users/{user}/roles', [UserController::class, 'assignRole']);
        Route::delete('/users/{user}/roles/{role}', [UserController::class, 'removeRole']);
    });

    // Roles & Permissions
    Route::get('/roles', [RoleController::class, 'index'])
        ->middleware('permission:users.view');
    Route::get('/roles/{role}', [RoleController::class, 'show'])
        ->middleware('permission:users.view');
    Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
        ->middleware('permission:users.assign_roles');
    Route::get('/permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:users.view');

    // Teachers
    Route::middleware('permission:teachers.view')->group(function () {
        Route::get('/teachers', [TeacherController::class, 'index']);
        Route::get('/teachers/{teacher}', [TeacherController::class, 'show']);
    });
    Route::middleware('permission:teachers.create')
        ->post('/teachers', [TeacherController::class, 'store']);
    Route::middleware('permission:teachers.update')
        ->put('/teachers/{teacher}', [TeacherController::class, 'update']);
    Route::middleware('permission:teachers.delete')
        ->delete('/teachers/{teacher}', [TeacherController::class, 'destroy']);

    // Subjects
    Route::middleware('permission:subjects.view')->group(function () {
        Route::get('/subjects', [SubjectController::class, 'index']);
        Route::get('/subjects/{subject}', [SubjectController::class, 'show']);
    });
    Route::middleware('permission:subjects.create')
        ->post('/subjects', [SubjectController::class, 'store']);
    Route::middleware('permission:subjects.update')
        ->put('/subjects/{subject}', [SubjectController::class, 'update']);
    Route::middleware('permission:subjects.delete')
        ->delete('/subjects/{subject}', [SubjectController::class, 'destroy']);

    // Rooms
    Route::middleware('permission:rooms.view')->group(function () {
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::get('/rooms/{room}', [RoomController::class, 'show']);
    });
    Route::middleware('permission:rooms.create')
        ->post('/rooms', [RoomController::class, 'store']);
    Route::middleware('permission:rooms.update')
        ->put('/rooms/{room}', [RoomController::class, 'update']);
    Route::middleware('permission:rooms.delete')
        ->delete('/rooms/{room}', [RoomController::class, 'destroy']);

    // Academic Years
    Route::middleware('permission:academic_years.view')->group(function () {
        Route::get('/academic-years', [AcademicYearController::class, 'index']);
        Route::get('/academic-years/{academicYear}', [AcademicYearController::class, 'show']);
    });
    Route::middleware('permission:academic_years.create')
        ->post('/academic-years', [AcademicYearController::class, 'store']);
    Route::middleware('permission:academic_years.update')
        ->put('/academic-years/{academicYear}', [AcademicYearController::class, 'update']);
    Route::middleware('permission:academic_years.delete')
        ->delete('/academic-years/{academicYear}', [AcademicYearController::class, 'destroy']);

    // Terms
    Route::middleware('permission:terms.view')->group(function () {
        Route::get('/academic-years/{academicYear}/terms', [TermController::class, 'index']);
        Route::get('/terms/{term}', [TermController::class, 'show']);
    });
    Route::middleware('permission:terms.create')
        ->post('/terms', [TermController::class, 'store']);
    Route::middleware('permission:terms.update')
        ->put('/terms/{term}', [TermController::class, 'update']);
    Route::middleware('permission:terms.delete')
        ->delete('/terms/{term}', [TermController::class, 'destroy']);

    // Classes
    Route::middleware('permission:classes.view')->group(function () {
        Route::get('/classes', [ClassController::class, 'index']);
        Route::get('/classes/{class}', [ClassController::class, 'show']);
    });
    Route::middleware('permission:classes.create')
        ->post('/classes', [ClassController::class, 'store']);
    Route::middleware('permission:classes.update')
        ->put('/classes/{class}', [ClassController::class, 'update']);
    Route::middleware('permission:classes.delete')
        ->delete('/classes/{class}', [ClassController::class, 'destroy']);

    // Teaching Assignments
    Route::middleware('permission:teaching_assignments.view')->group(function () {
        Route::get('/teaching-assignments', [TeacherAssignmentController::class, 'index']);
        Route::get('/teaching-assignments/{teacherAssignment}', [TeacherAssignmentController::class, 'show']);
    });
    Route::middleware('permission:teaching_assignments.create')
        ->post('/teaching-assignments', [TeacherAssignmentController::class, 'store']);
    Route::middleware('permission:teaching_assignments.update')
        ->put('/teaching-assignments/{teacherAssignment}', [TeacherAssignmentController::class, 'update']);
    Route::middleware('permission:teaching_assignments.delete')
        ->delete('/teaching-assignments/{teacherAssignment}', [TeacherAssignmentController::class, 'destroy']);

    // Guardians
    Route::middleware('permission:guardians.view')->group(function () {
        Route::get('/guardians', [GuardianController::class, 'index']);
        Route::get('/guardians/{guardian}', [GuardianController::class, 'show']);
    });
    Route::middleware('permission:guardians.create')
        ->post('/guardians', [GuardianController::class, 'store']);
    Route::middleware('permission:guardians.update')
        ->put('/guardians/{guardian}', [GuardianController::class, 'update']);
    Route::middleware('permission:guardians.delete')
        ->delete('/guardians/{guardian}', [GuardianController::class, 'destroy']);

    // Students
    Route::middleware('permission:students.view')->group(function () {
        Route::get('/students', [StudentController::class, 'index']);
        Route::get('/students/{student}', [StudentController::class, 'show']);
    });
    Route::middleware('permission:students.create')
        ->post('/students', [StudentController::class, 'store']);
    Route::middleware('permission:students.update')->group(function () {
        Route::put('/students/{student}', [StudentController::class, 'update']);
        Route::post('/students/{student}/guardians', [StudentController::class, 'attachGuardian']);
        Route::delete('/students/{student}/guardians/{guardian}', [StudentController::class, 'detachGuardian']);
    });
    Route::middleware('permission:students.delete')
        ->delete('/students/{student}', [StudentController::class, 'destroy']);
    Route::middleware('permission:students.view')
        ->get('/students/{student}/guardians', [StudentController::class, 'listGuardians']);

    // Enrollments
    Route::middleware('permission:enrollments.view')->group(function () {
        Route::get('/enrollments', [EnrollmentController::class, 'index']);
        Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show']);
    });
    Route::middleware('permission:enrollments.create')
        ->post('/enrollments', [EnrollmentController::class, 'store']);
    Route::middleware('permission:enrollments.update')
        ->put('/enrollments/{enrollment}', [EnrollmentController::class, 'update']);
    Route::middleware('permission:enrollments.delete')
        ->delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);

    // ── Phase 3: Academic Core ────────────────────────────────────────────────

    // Assessment Settings
    Route::middleware('permission:assessment_settings.view')->group(function () {
        Route::get('/assessment-settings', [AssessmentSettingController::class, 'index']);
        Route::get('/assessment-settings/{assessmentSetting}', [AssessmentSettingController::class, 'show']);
    });
    Route::middleware('permission:assessment_settings.update')
        ->put('/assessment-settings/{assessmentSetting}', [AssessmentSettingController::class, 'update']);

    // Assessments
    Route::middleware('permission:grades.view')->group(function () {
        Route::get('/assessments', [AssessmentController::class, 'index']);
        Route::get('/assessments/{assessment}', [AssessmentController::class, 'show']);
    });
    Route::middleware('permission:grades.create')
        ->post('/assessments', [AssessmentController::class, 'store']);
    Route::middleware('permission:grades.update')
        ->put('/assessments/{assessment}', [AssessmentController::class, 'update']);
    Route::middleware('permission:grades.update')
        ->delete('/assessments/{assessment}', [AssessmentController::class, 'destroy']);

    // Grades (per assessment)
    Route::middleware('permission:grades.view')
        ->get('/assessments/{assessment}/grades', [GradeController::class, 'index']);
    Route::middleware('permission:grades.create')
        ->post('/assessments/{assessment}/grades', [GradeController::class, 'store']);
    Route::middleware('permission:grades.update')
        ->put('/assessments/{assessment}/grades/{grade}', [GradeController::class, 'update']);
    Route::middleware('permission:grades.annul')
        ->post('/assessments/{assessment}/grades/{grade}/annul', [GradeController::class, 'annul']);

    // Grade Books
    Route::middleware('permission:grade_books.view')->group(function () {
        Route::get('/grade-books', [GradeBookController::class, 'index']);
        Route::get('/grade-books/{gradeBook}', [GradeBookController::class, 'show']);
    });
    Route::middleware('permission:grade_books.create')
        ->post('/grade-books', [GradeBookController::class, 'store']);
    Route::middleware('permission:grade_books.submit')
        ->post('/grade-books/{gradeBook}/submit', [GradeBookController::class, 'submit']);
    Route::middleware('permission:grade_books.publish')
        ->post('/grade-books/{gradeBook}/publish', [GradeBookController::class, 'publish']);
    Route::middleware('permission:grade_books.lock')
        ->post('/grade-books/{gradeBook}/lock', [GradeBookController::class, 'lock']);
    Route::middleware('permission:grade_books.unlock')
        ->post('/grade-books/{gradeBook}/unlock', [GradeBookController::class, 'unlock']);

    // Attendance Sessions
    Route::middleware('permission:attendance.view')->group(function () {
        Route::get('/attendance-sessions', [AttendanceSessionController::class, 'index']);
        Route::get('/attendance-sessions/{attendanceSession}', [AttendanceSessionController::class, 'show']);
        Route::get('/attendance-sessions/{attendanceSession}/records', [AttendanceSessionController::class, 'records']);
    });
    Route::middleware('permission:attendance.create')
        ->post('/attendance-sessions', [AttendanceSessionController::class, 'store']);
    Route::middleware('permission:attendance.update')
        ->put('/attendance-sessions/{attendanceSession}/records/{attendanceRecord}', [AttendanceSessionController::class, 'updateRecord']);

    // Attendance Summary
    Route::middleware('permission:attendance.view')
        ->get('/attendance/summary', [AttendanceSummaryController::class, 'index']);

    // Academic Results
    Route::middleware('permission:academic_results.view')
        ->get('/academic-results', [AcademicResultController::class, 'index']);

    // Reports
    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports/student-report-card', [ReportController::class, 'studentReportCard']);
        Route::get('/reports/class-grade-sheet', [ReportController::class, 'classGradeSheet']);
        Route::get('/reports/subject-mini-grade-sheet', [ReportController::class, 'subjectMiniGradeSheet']);
        Route::get('/reports/academic-alerts', [ReportController::class, 'academicAlerts']);
    });
});

