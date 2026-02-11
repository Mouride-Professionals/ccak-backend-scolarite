<?php

use App\Http\Controllers\Academic\AcademicProgramController;
use App\Http\Controllers\Academic\CourseController;
use App\Http\Controllers\Academic\CourseUnitController;
use App\Http\Controllers\Academic\GradeStatisticsController;
use App\Http\Controllers\Academic\DepartmentController;
use App\Http\Controllers\Academic\FacultyController;
use App\Http\Controllers\Academic\FacultyContractController;
use App\Http\Controllers\Academic\FacultyDocumentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GeneratedDocumentController;
use App\Http\Controllers\Academic\AcademicYearController;
use App\Http\Controllers\Academic\CourseEnrollmentController;
use App\Http\Controllers\Academic\EnrollmentController;
use App\Http\Controllers\Academic\AcademicCalendarController;
use App\Http\Controllers\Academic\HolidayController;
use App\Http\Controllers\Academic\RoomController;
use App\Http\Controllers\Academic\ActivityTypeController;
use App\Http\Controllers\Academic\ScheduleController;
use App\Http\Controllers\Academic\CourseLogController;
use App\Http\Controllers\Academic\AttendanceController;
use App\Http\Controllers\Academic\EvaluationController;
use App\Http\Controllers\Academic\EvaluationResponseController;
use App\Http\Controllers\Academic\TeachingAssignmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserRoleController;
use \App\Http\Controllers\Academic\DeliberationSessionController;

use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\GuardianController;

use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Notification\AnnouncementController;


Route::middleware('auth:api')->group(function () {
    // Basic protected endpoints
    Route::get('/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
            'message' => 'Operation successful',
            'meta' => null,
        ]);
    });
    Route::post('/logout', [AuthController::class, 'logout']);




    // Deliberation Sessions
    Route::patch('deliberation-sessions/{id}/status', [DeliberationSessionController::class, 'changeStatus']);
    Route::post('deliberations/{deliberation_session}/start', [DeliberationSessionController::class, 'start']);
    Route::patch('deliberations/{deliberation_session}/students/{student}/decision', [DeliberationSessionController::class, 'saveDecision']);
    Route::post('deliberations/{deliberation_session}/complete', [DeliberationSessionController::class, 'complete']);
    Route::get('deliberations/{deliberation_session}/students', [DeliberationSessionController::class, 'getStudents']);
    Route::get('deliberations/{deliberation_session}/minutes', [DeliberationSessionController::class, 'generateMinutes']);

    // Deliberation Results
    Route::apiResource('deliberation-results', \App\Http\Controllers\Academic\DeliberationResultController::class);

    // Student Deliberation History
    Route::get('students/{student_id}/deliberations', [\App\Http\Controllers\Academic\StudentDeliberationController::class, 'history']);

    Route::apiResource('deliberation-sessions', \App\Http\Controllers\Academic\DeliberationSessionController::class);
    Route::apiResource('faculty-members', \App\Http\Controllers\Academic\FacultyMemberController::class);
    Route::get('faculty/available', [\App\Http\Controllers\Academic\FacultyMemberController::class, 'available']);
    Route::get('faculty/{faculty_member}/assignments', [\App\Http\Controllers\Academic\FacultyMemberController::class, 'assignments']);
    Route::get('faculty/{faculty_member}/workload', [\App\Http\Controllers\Academic\FacultyMemberController::class, 'workload']);
    Route::get('faculty/{faculty_member}/documents', [FacultyDocumentController::class, 'index']);
    Route::post('faculty/{faculty_member}/documents', [FacultyDocumentController::class, 'store']);
    Route::get('faculty/{faculty_member}/contracts', [FacultyContractController::class, 'index']);
    Route::post('faculty/{faculty_member}/contracts', [FacultyContractController::class, 'store']);
    Route::get('faculty', [\App\Http\Controllers\Academic\FacultyMemberController::class, 'index']);
    Route::post('faculty', [\App\Http\Controllers\Academic\FacultyMemberController::class, 'store']);
    Route::get('faculty/{faculty_member}', [\App\Http\Controllers\Academic\FacultyMemberController::class, 'show']);
    Route::put('faculty/{faculty_member}', [\App\Http\Controllers\Academic\FacultyMemberController::class, 'update']);
    Route::post('teaching-assignments/import', [TeachingAssignmentController::class, 'import']);
    Route::apiResource('teaching-assignments', TeachingAssignmentController::class)->only(['index', 'store', 'destroy']);


    Route::apiResource('faculties', FacultyController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('academic-programs', AcademicProgramController::class);
    Route::apiResource('course-units', CourseUnitController::class);
    Route::apiResource('courses', CourseController::class);
    Route::get('courses/{course}/grades', [\App\Http\Controllers\Academic\CourseController::class, 'grades']);
    Route::apiResource('course-enrollments', \App\Http\Controllers\Academic\CourseEnrollmentController::class);
    Route::apiResource('enrollments', EnrollmentController::class);

    // Academic calendar & scheduling
    Route::post('academic-calendar', [AcademicCalendarController::class, 'store']);
    Route::get('academic-calendar/{academic_year}', [AcademicCalendarController::class, 'show']);
    Route::apiResource('holidays', HolidayController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('rooms', RoomController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('activity-types', [ActivityTypeController::class, 'index']);
    Route::post('activity-types', [ActivityTypeController::class, 'store']);
    Route::post('schedules', [ScheduleController::class, 'store']);
    Route::post('schedules/check-availability', [ScheduleController::class, 'checkAvailability']);
    Route::get('programs/{program}/schedule', [ScheduleController::class, 'programSchedule']);
    Route::get('faculty/{faculty_member}/schedule', [ScheduleController::class, 'facultySchedule']);
    Route::get('students/{student}/schedule', [ScheduleController::class, 'studentSchedule']);

    // Course logs & attendance
    Route::post('course-logs', [CourseLogController::class, 'store']);
    Route::put('course-logs/{course_log}', [CourseLogController::class, 'update']);
    Route::get('courses/{course}/logs', [CourseLogController::class, 'courseLogs']);
    Route::post('attendance', [AttendanceController::class, 'store']);
    Route::get('students/{student}/attendance', [AttendanceController::class, 'studentAttendance']);
    Route::get('courses/{course}/attendance', [AttendanceController::class, 'courseAttendance']);
    Route::get('students/{student}/dispensations', [AttendanceController::class, 'dispensations']);

    // Evaluations
    Route::post('evaluations', [EvaluationController::class, 'store']);
    Route::post('evaluations/{evaluation}/share', [EvaluationController::class, 'share']);
    Route::get('evaluations/{evaluation}/results', [EvaluationController::class, 'results']);
    Route::post('evaluation-responses', [EvaluationResponseController::class, 'store']);
    Route::get('students/{student}/evaluations', [EvaluationController::class, 'studentEvaluations']);

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']); // NOT-008
        Route::post('/', [NotificationController::class, 'send'])->middleware('role:ADMIN'); // NOT-007
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']); // NOT-009
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']); // NOT-010
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Announcements
    Route::prefix('announcements')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index']); // NOT-012
        Route::post('/', [AnnouncementController::class, 'store'])->middleware('role:ADMIN'); // NOT-011
        Route::get('/{id}', [AnnouncementController::class, 'show']);
        Route::put('/{id}', [AnnouncementController::class, 'update'])->middleware('role:ADMIN');
        Route::delete('/{id}', [AnnouncementController::class, 'destroy'])->middleware('role:ADMIN');
        Route::post('/{id}/dismiss', [AnnouncementController::class, 'dismiss']);
        Route::post('/{id}/publish', [AnnouncementController::class, 'publish'])->middleware('role:ADMIN');
    });

    Route::apiResource('generated-documents', GeneratedDocumentController::class);
    Route::post('/generated-documents/generate', [GeneratedDocumentController::class, 'generate']);
    Route::post('/generated-documents/bulk-generate', [GeneratedDocumentController::class, 'bulkGenerate']);
    Route::post('/generated-documents/{generatedDocument}/issue', [GeneratedDocumentController::class, 'issue']);
    Route::post('/generated-documents/{generatedDocument}/revoke', [GeneratedDocumentController::class, 'revoke']);
    Route::get('/generated-documents/{generatedDocument}/download', [GeneratedDocumentController::class, 'download']);

    Route::post('/documents/generate', [GeneratedDocumentController::class, 'generate']);
    Route::post('/documents/bulk-generate', [GeneratedDocumentController::class, 'bulkGenerate']);
    Route::post('/documents/{generatedDocument}/issue', [GeneratedDocumentController::class, 'issue']);
    Route::post('/documents/{generatedDocument}/revoke', [GeneratedDocumentController::class, 'revoke']);

    Route::apiResource('documents', DocumentController::class)
        ->whereUuid('document');
    Route::prefix('documents')->group(function () {
        // Routes pour les rapports et statistiques
        Route::get('/report', [DocumentController::class, 'report']);
        Route::get('/check-status', [DocumentController::class, 'checkStatus']);
        Route::get('/check-status/{student}', [DocumentController::class, 'checkStatus']);

        // Routes pour les documents en attente
        Route::get('/pending', [DocumentController::class, 'pending']);

        // Routes pour les documents d'un étudiant spécifique
        Route::get('/student/{student}', [DocumentController::class, 'studentDocuments'])
            ->name('documents.student');

        // Routes spécifiques à un document (complémentaires aux routes apiResource)
        Route::prefix('{document}')->group(function () {
            Route::get('/download', [DocumentController::class, 'download'])
                ->name('documents.download');
            Route::get('/download-file', [DocumentController::class, 'downloadFile'])
                ->name('documents.download.file');

            // Review (approbation/rejet)
            Route::post('/approve', [DocumentController::class, 'approve'])
                ->name('documents.approve');
            Route::post('/reject', [DocumentController::class, 'reject'])
                ->name('documents.reject');
        });
    });

    // Legacy-compatible student document endpoints
    Route::get('students/{student}/documents', [DocumentController::class, 'studentDocuments']);
    Route::post('students/{student}/documents', [DocumentController::class, 'storeForStudent']);
    Route::get('students/{student}/documents/{document}', [DocumentController::class, 'showForStudent']);
    Route::put('documents/{document}/review', [DocumentController::class, 'review']);

    // Student area
    Route::apiResource('students', StudentController::class);
    Route::patch('students/{student}/status', [StudentController::class, 'updateStatus']);

    // Nested guardians for students: /api/v1/students/{student}/guardians
    Route::apiResource('students.guardians', GuardianController::class);

    // Documents: nested index/store/show/update/destroy under students and review route
    // Admin roles
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::put('roles/{role}', [RoleController::class, 'update']);
    Route::put('users/{user}/roles', [UserRoleController::class, 'update']);
    Route::get('admin/audits', [AuditLogController::class, 'index']);
    Route::get('admin/audits/{audit}', [AuditLogController::class, 'show']);
    Route::get('admin/audits/model/{model}/{id}', [AuditLogController::class, 'forModel']);

    // Grade management endpoints
    Route::get('grades/statistics', [GradeStatisticsController::class, 'index']);
    Route::apiResource('grades', \App\Http\Controllers\GradeController::class);
    Route::post('grades/{grade}/submit', [\App\Http\Controllers\GradeController::class, 'submit']);
    Route::post('grades/{grade}/validate', [\App\Http\Controllers\GradeController::class, 'validateGrade']);
    Route::post('grades/publish', [\App\Http\Controllers\GradeController::class, 'publish']);

    // Student management endpoints
    Route::get('students/{student}/grades', [StudentController::class, 'grades']);

    // Semester results management endpoints
    Route::post('semester-results/calculate', [\App\Http\Controllers\SemesterResultController::class, 'calculate']);
    Route::get('semester-results/statistics', [\App\Http\Controllers\SemesterResultController::class, 'statistics']);
    Route::post('semester-results/recalculate/{student}', [\App\Http\Controllers\SemesterResultController::class, 'recalculateStudent']);
    Route::apiResource('semester-results', \App\Http\Controllers\SemesterResultController::class)
        ->whereNumber('semester_result');
    // Enrollments
    Route::get('/students/{id}/enrollments', [EnrollmentController::class, 'getByStudent']);

    // Academic Years
    Route::apiResource('academic-years', AcademicYearController::class);
    Route::get('/academic-years/current', [AcademicYearController::class, 'current']);
    Route::put('/academic-years/{id}/set-current', [AcademicYearController::class, 'setCurrent']);

    // Course Enrollments
    Route::post('enrollments/{enrollment}/courses', [CourseEnrollmentController::class, 'enrollCourse']);
    Route::get('enrollments/{enrollment}/courses', [CourseEnrollmentController::class, 'getCourses']);
    Route::delete('enrollments/{enrollment}/courses/{courseEnrollment}', [CourseEnrollmentController::class, 'dropCourse']);
    Route::get('courses/{course}/availability', [CourseEnrollmentController::class, 'checkAvailability']);
    Route::get('programs/{program}/available-courses', [CourseEnrollmentController::class, 'getAvailableCoursesByProgram']);
});

Route::get('/generated-documents/verify/{documentNumber}', [GeneratedDocumentController::class, 'verify']);
Route::get('/documents/verify/{documentNumber}', [GeneratedDocumentController::class, 'verify']);
