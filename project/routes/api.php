<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\AuditLogController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });

    // Courses (all authenticated users can view)
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);
    Route::get('/courses/{courseId}/assignments', [AssignmentController::class, 'index']);
    Route::get('/assignments/{id}', [AssignmentController::class, 'show']);

    // Student routes
    Route::middleware('role:Student')->group(function () {
        Route::post('/courses/{courseId}/enroll', [EnrollmentController::class, 'enroll']);
        Route::get('/enrollments/my-courses', [EnrollmentController::class, 'myCourses']);
        Route::delete('/enrollments/{id}', [EnrollmentController::class, 'destroy']);
        Route::post('/assignments/{assignmentId}/submit', [SubmissionController::class, 'submit']);
        Route::get('/submissions/my-submissions', [SubmissionController::class, 'mySubmissions']);
        Route::put('/submissions/{id}', [SubmissionController::class, 'update']);
    });

    // Instructor and Admin routes
    Route::middleware('role:Instructor,Admin')->group(function () {
        Route::post('/courses', [CourseController::class, 'store']);
        Route::put('/courses/{id}', [CourseController::class, 'update']);
        Route::post('/courses/{courseId}/assignments', [AssignmentController::class, 'store']);
        Route::put('/assignments/{id}', [AssignmentController::class, 'update']);
        Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy']);
        Route::get('/submissions', [SubmissionController::class, 'index']);
        Route::get('/submissions/{id}', [SubmissionController::class, 'show']);
        Route::put('/submissions/{id}/grade', [SubmissionController::class, 'grade']);
    });

    // Admin only routes
    Route::middleware('role:Admin')->group(function () {
        // Users management
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        
        // Course management
        Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
        
        // Enrollment management
        Route::get('/enrollments', [EnrollmentController::class, 'index']);
        Route::put('/enrollments/{id}', [EnrollmentController::class, 'update']);
        
        // Audit logs
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });
});
