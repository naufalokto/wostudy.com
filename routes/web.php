<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CollaborativeDashboardController;
use App\Http\Controllers\CollaborativePresenceController;

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
