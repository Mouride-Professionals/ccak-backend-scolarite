<?php

use App\Http\Controllers\Academic\AcademicProgramController;
use App\Http\Controllers\Academic\CourseController;
use App\Http\Controllers\Academic\CourseUnitController;
use App\Http\Controllers\Academic\DepartmentController;
use App\Http\Controllers\Academic\FacultyController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GeneratedDocumentController;
use App\Http\Controllers\Academic\AcademicYearController;
use App\Http\Controllers\Academic\CourseEnrollmentController;
use App\Http\Controllers\Academic\EnrollmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserRoleController;
use \App\Http\Controllers\Academic\DeliberationSessionController;

use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\DocumentController as StudentDocumentController;
use App\Http\Controllers\Student\GuardianController;

use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Notification\AnnouncementController;
use App\Models\Course;


Route::middleware('auth:api')->group(function () {
    // Basic protected endpoints
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
            'message' => 'Operation successful',
            'meta' => null,
        ]);
    });

    Route::get('/protected-resource', function () {
        return response()->json([
            'success' => true,
            'data' => ['message' => 'This is a protected resource accessible only to authenticated Keycloak users.'],
            'message' => 'Operation successful',
            'meta' => null,
        ]);
    });


    // Deliberation Sessions
    Route::prefix('deliberation-sessions')->group(function () {
        Route::get('/', [DeliberationSessionController::class, 'index']);
        Route::post('/', [DeliberationSessionController::class, 'store']);
        Route::get('{id}', [DeliberationSessionController::class, 'show']);
        Route::put('{id}', [DeliberationSessionController::class, 'update']);
        Route::delete('{id}', [DeliberationSessionController::class, 'destroy']);
        Route::patch('{id}/status', [DeliberationSessionController::class, 'changeStatus']);
    });
    Route::post('deliberations/{deliberation_session}/start', [DeliberationSessionController::class, 'start']);
    Route::post('deliberations/{deliberation_session}/complete', [DeliberationSessionController::class, 'complete']);
    Route::get('deliberations/{deliberation_session}/students', [DeliberationSessionController::class, 'getStudents']);
    Route::get('deliberations/{deliberation_session}/minutes', [DeliberationSessionController::class, 'generateMinutes']);

    // Deliberation Results
    Route::apiResource('deliberation-results', \App\Http\Controllers\Academic\DeliberationResultController::class);


    // Student Deliberation History
    Route::get('students/{student_id}/deliberations', [\App\Http\Controllers\Academic\StudentDeliberationController::class, 'history']);

    Route::apiResource('deliberation-sessions', \App\Http\Controllers\Academic\DeliberationSessionController::class);
    Route::apiResource('deliberation-results', \App\Http\Controllers\Academic\DeliberationResultController::class);
    Route::apiResource('faculty-members', \App\Http\Controllers\Academic\FacultyMemberController::class);


    Route::apiResource('faculties', FacultyController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('academic-programs', AcademicProgramController::class);
    Route::apiResource('course-units', CourseUnitController::class);
    Route::apiResource('courses', CourseController::class);
    Route::get('courses/{course}/grades', [\App\Http\Controllers\Academic\CourseController::class, 'grades']);
    Route::apiResource('course-enrollments', \App\Http\Controllers\CourseEnrollmentController::class);

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
    Route::get('/generated-documents/verify/{documentNumber}', [GeneratedDocumentController::class, 'verify']);
    Route::apiResource('documents', DocumentController::class)
        ->whereUuid('document');
    Route::prefix('documents')->group(function () {
        // Routes pour les rapports et statistiques
        Route::get('/report', [DocumentController::class, 'report']);
        Route::get('/check-status', [DocumentController::class, 'checkStatus']);
        Route::get('/check-status/{studentId}', [DocumentController::class, 'checkStatus']);

        // Routes pour les documents en attente
        Route::get('/pending', [DocumentController::class, 'pending']);

        // Routes pour les documents d'un étudiant spécifique
        Route::get('/student/{studentId}', [DocumentController::class, 'studentDocuments'])
            ->name('documents.student');

        // Routes spécifiques à un document (complémentaires aux routes apiResource)
        Route::prefix('{document}')->group(function () {
            // Téléchargement
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

    // Student area
    Route::apiResource('students', StudentController::class);

    // Nested guardians for students: /api/v1/students/{student}/guardians
    Route::apiResource('students.guardians', GuardianController::class);

    // Documents: nested index/store/show/update/destroy under students and review route
    Route::get('students/{student}/documents', [StudentDocumentController::class, 'index']);
    Route::post('students/{student}/documents', [StudentDocumentController::class, 'store']);
    Route::get('students/{student}/documents/{document}', [StudentDocumentController::class, 'show']);
    Route::put('students/{student}/documents/{document}', [StudentDocumentController::class, 'update']);
    Route::delete('students/{student}/documents/{document}', [StudentDocumentController::class, 'destroy']);

    // Review endpoint (shallow): /api/v1/documents/{id}/review
    Route::put('documents/{document}/review', [StudentDocumentController::class, 'review']);

    // Admin roles
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::put('roles/{role}', [RoleController::class, 'update']);
    Route::put('users/{user}/roles', [UserRoleController::class, 'update']);

    // Grade management endpoints
    Route::apiResource('grades', \App\Http\Controllers\GradeController::class);
    Route::post('grades/{grade}/submit', [\App\Http\Controllers\GradeController::class, 'submit']);
    Route::post('grades/{grade}/validate', [\App\Http\Controllers\GradeController::class, 'validateGrade']);
    Route::post('grades/publish', [\App\Http\Controllers\GradeController::class, 'publish']);

    // Student management endpoints
    Route::get('students/{student}/grades', [\App\Http\Controllers\StudentController::class, 'grades']);

    // Semester results management endpoints
    Route::post('semester-results/calculate', [\App\Http\Controllers\SemesterResultController::class, 'calculate']);
    Route::get('semester-results/statistics', [\App\Http\Controllers\SemesterResultController::class, 'statistics']);
    Route::post('semester-results/recalculate/{student}', [\App\Http\Controllers\SemesterResultController::class, 'recalculateStudent']);
    Route::apiResource('semester-results', \App\Http\Controllers\SemesterResultController::class)
        ->whereNumber('semester_result');
    // Enrollments
    Route::get('/students/{id}/enrollments', [EnrollmentController::class, 'getByStudent']);

    // Academic Years
    Route::get('/academic-years/current', [AcademicYearController::class, 'current']);
    Route::put('/academic-years/{id}/set-current', [AcademicYearController::class, 'setCurrent']);

    // Course Enrollments
    Route::post('enrollments/{id}/courses', [CourseEnrollmentController::class, 'enrollCourse']);
    Route::get('enrollments/{id}/courses', [CourseEnrollmentController::class, 'getCourses']);
    Route::delete('enrollments/{enrollmentId}/courses/{courseEnrollmentId}', [CourseEnrollmentController::class, 'dropCourse']);
    Route::get('courses/{id}/availability', [CourseEnrollmentController::class, 'checkAvailability']);
    Route::get('programs/{id}/available-courses', [CourseEnrollmentController::class, 'getAvailableCoursesByProgram']);

});
