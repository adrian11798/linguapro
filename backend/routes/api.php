<?php

use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Api\Admin\LiveClassController as AdminLiveClassController;
use App\Http\Controllers\Api\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Api\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\LiveClassController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\TeacherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas públicas (RF-01, RF-02)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Recuperar contraseña (limitado para evitar abusos)
Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Rutas autenticadas (cualquier rol)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Contenido del alumno: se bloquea si el administrador lo deshabilitó
    Route::middleware('enabled')->group(function () {
        // Cursos y contenido (RF-06, RF-08)
        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{course}', [CourseController::class, 'show']);
        Route::get('/courses/{course}/lessons', [LessonController::class, 'index']);

        // Inscripciones (RF-07)
        Route::get('/enrollments/me', [EnrollmentController::class, 'myEnrollments']);
        Route::post('/enrollments', [EnrollmentController::class, 'store']);

        // Progreso (RF-09)
        Route::get('/progress/me', [ProgressController::class, 'myProgress']);
        Route::post('/progress', [ProgressController::class, 'store']);

        // Clases en vivo (RF-10)
        Route::get('/live-classes', [LiveClassController::class, 'index']);

        // Tareas: el estudiante ve el material y entrega su archivo
        Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index']);
        Route::post('/assignments/{assignment}/submit', [AssignmentController::class, 'submit']);

        // Mensajes profesor <-> alumno (las rutas fijas van antes que /{user})
        Route::prefix('messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::get('/contacts', [MessageController::class, 'contacts']);
            Route::get('/unread-count', [MessageController::class, 'unreadCount']);
            Route::post('/', [MessageController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/{user}', [MessageController::class, 'thread']);
        });
    });

    /*
    |----------------------------------------------------------------
    | Panel del profesor
    |----------------------------------------------------------------
    */
    Route::middleware('role:teacher')->prefix('teacher')->group(function () {
        Route::get('/students', [TeacherController::class, 'students']);
        Route::get('/students/search', [TeacherController::class, 'searchStudents']);
        Route::post('/enrollments', [TeacherController::class, 'enrollStudent']);
        Route::get('/courses/{course}/students/{user}', [TeacherController::class, 'studentDetail']);
        Route::get('/courses', [TeacherController::class, 'courses']);
        Route::get('/courses/{course}/lessons', [TeacherController::class, 'lessons']);
        Route::post('/lessons', [TeacherController::class, 'storeLesson']);
        Route::put('/lessons/{lesson}', [TeacherController::class, 'updateLesson']);
        Route::delete('/lessons/{lesson}', [TeacherController::class, 'destroyLesson']);
        Route::get('/live-classes', [TeacherController::class, 'liveClasses']);
        Route::post('/live-classes', [TeacherController::class, 'storeLiveClass']);
        Route::delete('/live-classes/{liveClass}', [TeacherController::class, 'destroyLiveClass']);

        // Tareas: el profesor sube material y califica las entregas
        Route::get('/courses/{course}/assignments', [TeacherController::class, 'assignments']);
        Route::post('/assignments', [TeacherController::class, 'storeAssignment']);
        Route::delete('/assignments/{assignment}', [TeacherController::class, 'destroyAssignment']);
        Route::get('/assignments/{assignment}/submissions', [TeacherController::class, 'submissions']);
        Route::post('/submissions/{submission}/grade', [TeacherController::class, 'gradeSubmission']);
    });

    /*
    |----------------------------------------------------------------
    | Panel administrativo (RF-04, RF-05, RF-11, RF-12)
    |----------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/courses', [AdminCourseController::class, 'index']);
        Route::post('/courses', [AdminCourseController::class, 'store']);
        Route::put('/courses/{course}', [AdminCourseController::class, 'update']);
        Route::delete('/courses/{course}', [AdminCourseController::class, 'destroy']);

        Route::post('/lessons', [AdminLessonController::class, 'store']);
        Route::put('/lessons/{lesson}', [AdminLessonController::class, 'update']);
        Route::delete('/lessons/{lesson}', [AdminLessonController::class, 'destroy']);

        Route::get('/teachers', [AdminTeacherController::class, 'index']);
        Route::post('/teachers', [AdminTeacherController::class, 'store']);

        Route::get('/students', [AdminStudentController::class, 'index']);
        Route::put('/students/{user}/status', [AdminStudentController::class, 'updateStatus']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

        Route::get('/live-classes', [AdminLiveClassController::class, 'index']);
        Route::post('/live-classes', [AdminLiveClassController::class, 'store']);
        Route::put('/live-classes/{liveClass}', [AdminLiveClassController::class, 'update']);
        Route::delete('/live-classes/{liveClass}', [AdminLiveClassController::class, 'destroy']);
    });
});
