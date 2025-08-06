<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CollegeDashboardController;
use App\Http\Controllers\PersonalDashboardController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TodoCategoryController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\CollaborativeDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Protected API routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // College Dashboard API
    Route::prefix('college')->group(function () {
        Route::get('/', [CollegeDashboardController::class, 'index']);
        Route::get('/course/{courseId}', [CollegeDashboardController::class, 'showCourse']);
        Route::post('/course', [CollegeDashboardController::class, 'storeCourse']);
        Route::post('/todo-list', [CollegeDashboardController::class, 'storeTodoList']);
        Route::post('/todo-item/{todoListId}', [CollegeDashboardController::class, 'storeTodoItem']);
        Route::put('/todo-item/{itemId}', [CollegeDashboardController::class, 'updateTodoItem']);
        Route::delete('/todo-item/{itemId}', [CollegeDashboardController::class, 'deleteTodoItem']);
        Route::post('/upload/{todoListId}', [CollegeDashboardController::class, 'uploadFile']);
        Route::get('/download/{fileId}', [CollegeDashboardController::class, 'downloadFile']);
        Route::delete('/file/{fileId}', [CollegeDashboardController::class, 'deleteFile']);
        Route::get('/todo-lists/course/{courseId}', [CollegeDashboardController::class, 'getTodoListsByCourse']);
        Route::get('/todo-lists/category/{categoryId}', [CollegeDashboardController::class, 'getTodoListsByCategory']);
    });

    // Personal Dashboard API
    Route::prefix('personal')->group(function () {
        Route::get('/', [PersonalDashboardController::class, 'index']);
        Route::post('/todo-list', [PersonalDashboardController::class, 'storeTodoList']);
        Route::put('/todo-list/{todoListId}', [PersonalDashboardController::class, 'updateTodoList']);
        Route::delete('/todo-list/{todoListId}', [PersonalDashboardController::class, 'deleteTodoList']);
        Route::post('/todo-item/{todoListId}', [PersonalDashboardController::class, 'storeTodoItem']);
        Route::put('/todo-item/{itemId}', [PersonalDashboardController::class, 'updateTodoItem']);
        Route::delete('/todo-item/{itemId}', [PersonalDashboardController::class, 'deleteTodoItem']);
        Route::post('/upload/{todoListId}', [PersonalDashboardController::class, 'uploadFile']);
        Route::get('/download/{fileId}', [PersonalDashboardController::class, 'downloadFile']);
        Route::delete('/file/{fileId}', [PersonalDashboardController::class, 'deleteFile']);
        Route::get('/todo-lists/category/{categoryId}', [PersonalDashboardController::class, 'getTodoListsByCategory']);
        Route::get('/todo-lists/priority/{priority}', [PersonalDashboardController::class, 'getTodoListsByPriority']);
        Route::get('/todo-lists/status/{status}', [PersonalDashboardController::class, 'getTodoListsByStatus']);
        Route::get('/overdue', [PersonalDashboardController::class, 'getOverdueTasks']);
        Route::post('/complete/{todoListId}', [PersonalDashboardController::class, 'markAsCompleted']);
    });

    // Semester Management API
    Route::prefix('semesters')->group(function () {
        Route::get('/', [SemesterController::class, 'index']);
        Route::post('/', [SemesterController::class, 'store']);
        Route::put('/{id}', [SemesterController::class, 'update']);
        Route::delete('/{id}', [SemesterController::class, 'destroy']);
        Route::get('/current', [SemesterController::class, 'getCurrentSemester']);
        Route::post('/{id}/activate', [SemesterController::class, 'setActive']);
    });

    // Instructor Management API
    Route::prefix('instructors')->group(function () {
        Route::get('/', [InstructorController::class, 'index']);
        Route::post('/', [InstructorController::class, 'store']);
        Route::get('/{id}', [InstructorController::class, 'show']);
        Route::put('/{id}', [InstructorController::class, 'update']);
        Route::delete('/{id}', [InstructorController::class, 'destroy']);
        Route::get('/active/list', [InstructorController::class, 'getActiveInstructors']);
        Route::get('/{id}/courses', [InstructorController::class, 'getInstructorCourses']);
    });

    // Course Management API
    Route::prefix('courses')->group(function () {
        Route::get('/', [CourseController::class, 'index']);
        Route::get('/current-semester', [CourseController::class, 'getCurrentSemesterCourses']);
        Route::get('/types', [CourseController::class, 'getCourseTypes']);
        Route::post('/', [CourseController::class, 'store']);
        Route::get('/{id}', [CourseController::class, 'show']);
        Route::put('/{id}', [CourseController::class, 'update']);
        Route::delete('/{id}', [CourseController::class, 'destroy']);
        Route::post('/{courseId}/enroll', [CourseController::class, 'enrollUser']);
        Route::delete('/{courseId}/enroll/{userId}', [CourseController::class, 'unenrollUser']);
        Route::get('/instructor/{instructorId}', [CourseController::class, 'getCoursesByInstructor']);
        Route::get('/semester/{semesterId}', [CourseController::class, 'getCoursesBySemester']);
        Route::get('/user/{userId}', [CourseController::class, 'getUserCourses']);
    });

    // Todo Category API
    Route::prefix('categories')->group(function () {
        Route::get('/', [TodoCategoryController::class, 'index']);
        Route::post('/', [TodoCategoryController::class, 'store']);
        Route::get('/{id}', [TodoCategoryController::class, 'show']);
        Route::put('/{id}', [TodoCategoryController::class, 'update']);
        Route::delete('/{id}', [TodoCategoryController::class, 'destroy']);
        Route::get('/default/list', [TodoCategoryController::class, 'getDefaultCategories']);
        Route::post('/default/create', [TodoCategoryController::class, 'createDefaultCategories']);
    });

    // File Management API
    Route::prefix('files')->group(function () {
        Route::get('/todo-list/{todoListId}', [FileController::class, 'getFilesByTodoList']);
        Route::post('/upload/{todoListId}', [FileController::class, 'upload']);
        Route::get('/download/{fileId}', [FileController::class, 'download']);
        Route::get('/{fileId}', [FileController::class, 'show']);
        Route::put('/{fileId}', [FileController::class, 'update']);
        Route::delete('/{fileId}', [FileController::class, 'destroy']);
        Route::get('/user/list', [FileController::class, 'getUserFiles']);
        Route::get('/type/{type}', [FileController::class, 'getFilesByType']);
        Route::get('/search', [FileController::class, 'search']);
        Route::get('/statistics', [FileController::class, 'getStatistics']);
    });

    // Collaborative Dashboard API
    Route::prefix('collaborative')->group(function () {
        Route::post('/share/{todoListId}', [CollaborativeDashboardController::class, 'shareTodoList']);
        Route::post('/upload/{todoListId}', [CollaborativeDashboardController::class, 'uploadFile']);
        Route::get('/download/{fileId}', [CollaborativeDashboardController::class, 'downloadFile']);
        Route::put('/item/{itemId}', [CollaborativeDashboardController::class, 'updateTodoItem']);
        Route::get('/updates/{todoListId}', [CollaborativeDashboardController::class, 'getUpdates']);
    });
});

// Public API routes (for shared access)
Route::prefix('shared')->group(function () {
    Route::get('/{shareToken}', [CollaborativeDashboardController::class, 'showCollaborativeDashboard']);
}); 