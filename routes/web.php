<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CollaborativeDashboardController;
use App\Http\Controllers\CollaborativePresenceController;
use App\Http\Controllers\CollegeDashboardController;
use App\Http\Controllers\PersonalDashboardController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\TodoCategoryController;
use App\Http\Controllers\FileController;

Route::get('/', function () {
    return view('welcome');
});

// Collaborative Dashboard Routes
Route::prefix('collaborative')->group(function () {
    // Public shared access
    Route::get('/shared/{shareToken}', [CollaborativeDashboardController::class, 'showCollaborativeDashboard'])
        ->name('collaborative.dashboard');
    
    // Protected routes (require authentication)
    Route::middleware(['auth'])->group(function () {
        // Share todo list
        Route::post('/share/{todoListId}', [CollaborativeDashboardController::class, 'shareTodoList'])
            ->name('collaborative.share');
        
        // File management
        Route::post('/upload/{todoListId}', [CollaborativeDashboardController::class, 'uploadFile'])
            ->name('collaborative.upload');
        Route::get('/download/{fileId}', [CollaborativeDashboardController::class, 'downloadFile'])
            ->name('collaborative.download');
        
        // Todo item updates
        Route::put('/item/{itemId}', [CollaborativeDashboardController::class, 'updateTodoItem'])
            ->name('collaborative.update-item');
        
        // Real-time updates
        Route::get('/updates/{todoListId}', [CollaborativeDashboardController::class, 'getUpdates'])
            ->name('collaborative.updates');
        
        // Collaborative Presence & Participants
        Route::prefix('presence')->group(function () {
            // Join/Leave session
            Route::post('/join/{shareToken}', [CollaborativePresenceController::class, 'joinSession'])
                ->name('collaborative.join-session');
            Route::post('/leave/{shareToken}', [CollaborativePresenceController::class, 'leaveSession'])
                ->name('collaborative.leave-session');
            
            // Update presence
            Route::post('/update/{shareToken}', [CollaborativePresenceController::class, 'updatePresence'])
                ->name('collaborative.update-presence');
            Route::post('/away/{shareToken}', [CollaborativePresenceController::class, 'markAsAway'])
                ->name('collaborative.mark-away');
            
            // Get participants and updates
            Route::get('/participants/{shareToken}', [CollaborativePresenceController::class, 'getParticipants'])
                ->name('collaborative.participants');
            Route::get('/updates/{shareToken}', [CollaborativePresenceController::class, 'getPresenceUpdates'])
                ->name('collaborative.presence-updates');
        });
    });
});

// Dashboard Routes
Route::middleware(['auth'])->group(function () {
    // College Dashboard Routes
    Route::prefix('college')->group(function () {
        Route::get('/', [CollegeDashboardController::class, 'index'])->name('college.dashboard');
        Route::get('/course/{courseId}', [CollegeDashboardController::class, 'showCourse'])->name('college.course-detail');
        Route::post('/course', [CollegeDashboardController::class, 'storeCourse'])->name('college.store-course');
        Route::post('/todo-list', [CollegeDashboardController::class, 'storeTodoList'])->name('college.store-todo-list');
        Route::post('/todo-item/{todoListId}', [CollegeDashboardController::class, 'storeTodoItem'])->name('college.store-todo-item');
        Route::put('/todo-item/{itemId}', [CollegeDashboardController::class, 'updateTodoItem'])->name('college.update-todo-item');
        Route::delete('/todo-item/{itemId}', [CollegeDashboardController::class, 'deleteTodoItem'])->name('college.delete-todo-item');
        Route::post('/upload/{todoListId}', [CollegeDashboardController::class, 'uploadFile'])->name('college.upload-file');
        Route::get('/download/{fileId}', [CollegeDashboardController::class, 'downloadFile'])->name('college.download-file');
        Route::delete('/file/{fileId}', [CollegeDashboardController::class, 'deleteFile'])->name('college.delete-file');
        Route::get('/todo-lists/course/{courseId}', [CollegeDashboardController::class, 'getTodoListsByCourse'])->name('college.todo-lists-by-course');
        Route::get('/todo-lists/category/{categoryId}', [CollegeDashboardController::class, 'getTodoListsByCategory'])->name('college.todo-lists-by-category');
    });

    // Personal Dashboard Routes
    Route::prefix('personal')->group(function () {
        Route::get('/', [PersonalDashboardController::class, 'index'])->name('personal.dashboard');
        Route::post('/todo-list', [PersonalDashboardController::class, 'storeTodoList'])->name('personal.store-todo-list');
        Route::put('/todo-list/{todoListId}', [PersonalDashboardController::class, 'updateTodoList'])->name('personal.update-todo-list');
        Route::delete('/todo-list/{todoListId}', [PersonalDashboardController::class, 'deleteTodoList'])->name('personal.delete-todo-list');
        Route::post('/todo-item/{todoListId}', [PersonalDashboardController::class, 'storeTodoItem'])->name('personal.store-todo-item');
        Route::put('/todo-item/{itemId}', [PersonalDashboardController::class, 'updateTodoItem'])->name('personal.update-todo-item');
        Route::delete('/todo-item/{itemId}', [PersonalDashboardController::class, 'deleteTodoItem'])->name('personal.delete-todo-item');
        Route::post('/upload/{todoListId}', [PersonalDashboardController::class, 'uploadFile'])->name('personal.upload-file');
        Route::get('/download/{fileId}', [PersonalDashboardController::class, 'downloadFile'])->name('personal.download-file');
        Route::delete('/file/{fileId}', [PersonalDashboardController::class, 'deleteFile'])->name('personal.delete-file');
        Route::get('/todo-lists/category/{categoryId}', [PersonalDashboardController::class, 'getTodoListsByCategory'])->name('personal.todo-lists-by-category');
        Route::get('/todo-lists/priority/{priority}', [PersonalDashboardController::class, 'getTodoListsByPriority'])->name('personal.todo-lists-by-priority');
        Route::get('/todo-lists/status/{status}', [PersonalDashboardController::class, 'getTodoListsByStatus'])->name('personal.todo-lists-by-status');
        Route::get('/overdue', [PersonalDashboardController::class, 'getOverdueTasks'])->name('personal.overdue-tasks');
        Route::post('/complete/{todoListId}', [PersonalDashboardController::class, 'markAsCompleted'])->name('personal.mark-completed');
    });

    // Semester Management Routes
    Route::prefix('semesters')->group(function () {
        Route::get('/', [SemesterController::class, 'index'])->name('semesters.index');
        Route::post('/', [SemesterController::class, 'store'])->name('semesters.store');
        Route::put('/{id}', [SemesterController::class, 'update'])->name('semesters.update');
        Route::delete('/{id}', [SemesterController::class, 'destroy'])->name('semesters.destroy');
        Route::get('/current', [SemesterController::class, 'getCurrentSemester'])->name('semesters.current');
        Route::post('/{id}/activate', [SemesterController::class, 'setActive'])->name('semesters.activate');
    });

    // Instructor Management Routes
    Route::prefix('instructors')->group(function () {
        Route::get('/', [InstructorController::class, 'index'])->name('instructors.index');
        Route::post('/', [InstructorController::class, 'store'])->name('instructors.store');
        Route::get('/{id}', [InstructorController::class, 'show'])->name('instructors.show');
        Route::put('/{id}', [InstructorController::class, 'update'])->name('instructors.update');
        Route::delete('/{id}', [InstructorController::class, 'destroy'])->name('instructors.destroy');
        Route::get('/active/list', [InstructorController::class, 'getActiveInstructors'])->name('instructors.active');
        Route::get('/{id}/courses', [InstructorController::class, 'getInstructorCourses'])->name('instructors.courses');
    });

    // Course Management Routes
    Route::prefix('courses')->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('courses.index');
        Route::get('/current-semester', [CourseController::class, 'getCurrentSemesterCourses'])->name('courses.current-semester');
        Route::post('/', [CourseController::class, 'store'])->name('courses.store');
        Route::get('/{id}', [CourseController::class, 'show'])->name('courses.show');
        Route::put('/{id}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('/{courseId}/enroll', [CourseController::class, 'enrollUser'])->name('courses.enroll');
        Route::delete('/{courseId}/enroll/{userId}', [CourseController::class, 'unenrollUser'])->name('courses.unenroll');
        Route::get('/instructor/{instructorId}', [CourseController::class, 'getCoursesByInstructor'])->name('courses.by-instructor');
        Route::get('/semester/{semesterId}', [CourseController::class, 'getCoursesBySemester'])->name('courses.by-semester');
        Route::get('/user/{userId}', [CourseController::class, 'getUserCourses'])->name('courses.by-user');
    });

    // Todo Category Routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [TodoCategoryController::class, 'index'])->name('categories.index');
        Route::post('/', [TodoCategoryController::class, 'store'])->name('categories.store');
        Route::get('/{id}', [TodoCategoryController::class, 'show'])->name('categories.show');
        Route::put('/{id}', [TodoCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/{id}', [TodoCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('/default/list', [TodoCategoryController::class, 'getDefaultCategories'])->name('categories.default');
        Route::post('/default/create', [TodoCategoryController::class, 'createDefaultCategories'])->name('categories.create-default');
    });

    // File Management Routes
    Route::prefix('files')->group(function () {
        Route::get('/todo-list/{todoListId}', [FileController::class, 'getFilesByTodoList'])->name('files.by-todo-list');
        Route::post('/upload/{todoListId}', [FileController::class, 'upload'])->name('files.upload');
        Route::get('/download/{fileId}', [FileController::class, 'download'])->name('files.download');
        Route::get('/{fileId}', [FileController::class, 'show'])->name('files.show');
        Route::put('/{fileId}', [FileController::class, 'update'])->name('files.update');
        Route::delete('/{fileId}', [FileController::class, 'destroy'])->name('files.destroy');
        Route::get('/user/list', [FileController::class, 'getUserFiles'])->name('files.user');
        Route::get('/type/{type}', [FileController::class, 'getFilesByType'])->name('files.by-type');
        Route::get('/search', [FileController::class, 'search'])->name('files.search');
        Route::get('/statistics', [FileController::class, 'getStatistics'])->name('files.statistics');
    });
});
