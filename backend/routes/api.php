<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SchoolController;
use App\Http\Controllers\Api\V1\StudentController;
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

    // Students
    Route::middleware('permission:students.view')->group(function () {
        Route::get('/students', [StudentController::class, 'index']);
        Route::get('/students/{student}', [StudentController::class, 'show']);
    });
    Route::middleware('permission:students.create')
        ->post('/students', [StudentController::class, 'store']);
    Route::middleware('permission:students.update')
        ->put('/students/{student}', [StudentController::class, 'update']);
    Route::middleware('permission:students.delete')
        ->delete('/students/{student}', [StudentController::class, 'destroy']);
    Route::middleware('permission:students.update')
        ->post('/students/{student}/guardians', [StudentController::class, 'attachGuardian']);

    // ── Placeholder routes for future controllers ──
    // These will be implemented in the next development phase:
    //
    // Route::apiResource('guardians', GuardianController::class);
    // Route::apiResource('teachers', TeacherController::class);
    // Route::apiResource('subjects', SubjectController::class);
    // Route::apiResource('rooms', RoomController::class);
    // Route::apiResource('academic-years', AcademicYearController::class);
    // Route::apiResource('academic-years.terms', TermController::class);
    // Route::apiResource('classes', ClassController::class);
    // Route::post('classes/{class}/enrollments', [EnrollmentController::class, 'store']);
    // Route::post('classes/{class}/teacher-assignments', [TeacherAssignmentController::class, 'store']);
    // Route::apiResource('classes.assessments', AssessmentController::class);
    // Route::post('assessments/{assessment}/grades', [GradeController::class, 'store']);
    // Route::post('classes/{class}/attendance', [AttendanceController::class, 'store']);
    // Route::get('classes/{class}/attendance', [AttendanceController::class, 'index']);
    // Route::apiResource('tuition-plans', TuitionPlanController::class);
    // Route::apiResource('invoices', InvoiceController::class);
    // Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store']);
    // Route::apiResource('expenses', ExpenseController::class);
    // Route::apiResource('messages', MessageController::class);
    // Route::get('notifications', [NotificationController::class, 'index']);
    // Route::put('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    // Route::get('audit-logs', [AuditLogController::class, 'index']);
});
