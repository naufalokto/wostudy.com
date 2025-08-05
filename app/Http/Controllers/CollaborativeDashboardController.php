<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\SharedTodoList;
use App\Models\File;
use App\Models\FilePermission;
use App\Models\TodoItem;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CollaborativeDashboardController extends Controller
{
    /**
     * Show collaborative dashboard for a shared todo list
     */
    public function showCollaborativeDashboard($shareToken)
    {
        // Find shared access
        $sharedAccess = SharedTodoList::where('share_link', $shareToken)
            ->active()
            ->with(['todoList.items', 'todoList.files', 'sharedBy'])
            ->first();

        if (!$sharedAccess) {
            abort(404, 'Share link tidak valid atau sudah expired');
        }

        $todoList = $sharedAccess->todoList;
        $user = Auth::user();
        $canEdit = $sharedAccess->canEdit();

        return view('collaborative.dashboard', compact('todoList', 'sharedAccess', 'canEdit'));
    }

    /**
     * Share todo list with specific permissions
     */
    public function shareTodoList(Request $request, $todoListId)
    {
        $request->validate([
            'permission_type' => 'required|in:can_edit,can_view',
            'shared_with_email' => 'nullable|email',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $todoList = TodoList::findOrFail($todoListId);
        
        // Check if user owns the todo list
        if ($todoList->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses untuk share todo list ini');
        }

        $sharedAccess = SharedTodoList::create([
            'todo_list_id' => $todoListId,
            'shared_by_user_id' => Auth::id(),
            'shared_with_user_id' => $request->shared_with_email ? 
                User::where('email', $request->shared_with_email)->first()?->id : null,
            'permission_type' => $request->permission_type,
            'share_link' => Str::random(32),
            'expires_at' => $request->expires_at,
        ]);

        return response()->json([
            'success' => true,
            'share_url' => $sharedAccess->getFullShareUrl(),
            'message' => 'Todo list berhasil di-share'
        ]);
    }

    /**
     * Upload file to collaborative dashboard
     */
    public function uploadFile(Request $request, $todoListId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500',
        ]);

        $todoList = TodoList::findOrFail($todoListId);
        $user = Auth::user();

        // Check if user can edit
        if (!$todoList->canUserAccess($user, 'can_edit')) {
            abort(403, 'Anda tidak memiliki permission untuk upload file');
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::random(32) . '.' . $extension;
        $filePath = "collaborative/{$todoListId}/" . $storedName;

        // Store file
        Storage::put($filePath, file_get_contents($file));

        // Create file record
        $fileRecord = File::create([
            'todo_list_id' => $todoListId,
            'uploaded_by_user_id' => $user->id,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_extension' => $extension,
            'description' => $request->description,
        ]);

        // Create activity log
        $this->logActivity($todoList, 'file_uploaded', [
            'file_name' => $originalName,
            'uploaded_by' => $user->name
        ]);

        return response()->json([
            'success' => true,
            'file' => $fileRecord,
            'message' => 'File berhasil diupload'
        ]);
    }

    /**
     * Download file from collaborative dashboard
     */
    public function downloadFile($fileId)
    {
        $file = File::findOrFail($fileId);
        $user = Auth::user();

        // Check if user can view/download
        if (!$file->canUserAccess($user, 'can_view')) {
            abort(403, 'Anda tidak memiliki akses ke file ini');
        }

        // Increment download count
        $file->incrementDownloadCount();

        // Log activity
        $this->logActivity($file->todoList, 'file_downloaded', [
            'file_name' => $file->original_filename,
            'downloaded_by' => $user->name
        ]);

        return Storage::download($file->file_path, $file->original_filename);
    }

    /**
     * Update todo item in collaborative dashboard
     */
    public function updateTodoItem(Request $request, $itemId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        $todoItem = TodoItem::with('todoList')->findOrFail($itemId);
        $user = Auth::user();

        // Check if user can edit
        if (!$todoItem->todoList->canUserAccess($user, 'can_edit')) {
            abort(403, 'Anda tidak memiliki permission untuk edit item ini');
        }

        $todoItem->update($request->only(['title', 'description', 'is_completed']));

        // Log activity
        $this->logActivity($todoItem->todoList, 'item_updated', [
            'item_title' => $todoItem->title,
            'updated_by' => $user->name
        ]);

        return response()->json([
            'success' => true,
            'item' => $todoItem,
            'message' => 'Item berhasil diupdate'
        ]);
    }

    /**
     * Get real-time updates for collaborative dashboard
     */
    public function getUpdates($todoListId)
    {
        $todoList = TodoList::findOrFail($todoListId);
        $user = Auth::user();

        if (!$todoList->canUserAccess($user, 'can_view')) {
            abort(403);
        }

        // Get recent activities
        $activities = ActivityLog::where('todo_list_id', $todoListId)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        // Get updated items
        $items = $todoList->items()->with('files')->get();
        $files = $todoList->files()->latest()->get();

        return response()->json([
            'activities' => $activities,
            'items' => $items,
            'files' => $files,
            'last_updated' => now()->toISOString()
        ]);
    }

    /**
     * Log activity for collaborative dashboard
     */
    private function logActivity($todoList, $action, $data = [])
    {
        ActivityLog::create([
            'todo_list_id' => $todoList->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
        ]);
    }
} 