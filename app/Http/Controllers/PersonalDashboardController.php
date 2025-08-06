<?php

namespace App\Http\Controllers;

use App\Models\TodoList;
use App\Models\TodoItem;
use App\Models\TodoCategory;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersonalDashboardController extends Controller
{
    /**
     * Show personal dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get personal todo lists (without course_id)
        $todoLists = TodoList::where('user_id', $user->id)
            ->whereNull('course_id')
            ->with(['category', 'items', 'files'])
            ->latest()
            ->get();

        // Get categories
        $categories = TodoCategory::all();

        // Get statistics
        $stats = [
            'total_tasks' => $todoLists->sum(function($list) {
                return $list->items->count();
            }),
            'completed_tasks' => $todoLists->sum(function($list) {
                return $list->items->where('is_completed', true)->count();
            }),
            'pending_tasks' => $todoLists->sum(function($list) {
                return $list->items->where('is_completed', false)->count();
            }),
            'overdue_tasks' => $todoLists->sum(function($list) {
                return $list->items->where('is_completed', false)
                    ->where('deadline', '<', now())->count();
            }),
        ];

        return view('personal.dashboard', compact('todoLists', 'categories', 'stats'));
    }

    /**
     * Store new personal todo list
     */
    public function storeTodoList(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:todo_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'required|in:individual,group',
            'priority' => 'required|in:low,medium,high,urgent',
            'deadline' => 'nullable|date|after:now',
        ]);

        $todoList = TodoList::create([
            'user_id' => Auth::id(),
            'course_id' => null, // Personal todo list
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'task_type' => $request->task_type,
            'priority' => $request->priority,
            'deadline' => $request->deadline,
        ]);

        return response()->json([
            'success' => true,
            'todo_list' => $todoList->load(['category']),
            'message' => 'Todo list pribadi berhasil ditambahkan'
        ]);
    }

    /**
     * Update personal todo list
     */
    public function updateTodoList(Request $request, $todoListId)
    {
        $request->validate([
            'category_id' => 'required|exists:todo_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'required|in:individual,group',
            'priority' => 'required|in:low,medium,high,urgent',
            'deadline' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $todoList = TodoList::where('user_id', Auth::id())
            ->whereNull('course_id')
            ->findOrFail($todoListId);

        $todoList->update($request->all());

        return response()->json([
            'success' => true,
            'todo_list' => $todoList->load(['category']),
            'message' => 'Todo list berhasil diupdate'
        ]);
    }

    /**
     * Delete personal todo list
     */
    public function deleteTodoList($todoListId)
    {
        $todoList = TodoList::where('user_id', Auth::id())
            ->whereNull('course_id')
            ->findOrFail($todoListId);

        $todoList->delete();

        return response()->json([
            'success' => true,
            'message' => 'Todo list berhasil dihapus'
        ]);
    }

    /**
     * Store new todo item for personal todo list
     */
    public function storeTodoItem(Request $request, $todoListId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        $todoList = TodoList::where('user_id', Auth::id())
            ->whereNull('course_id')
            ->findOrFail($todoListId);

        $todoItem = TodoItem::create([
            'todo_list_id' => $todoListId,
            'title' => $request->title,
            'description' => $request->description,
            'is_completed' => $request->is_completed ?? false,
        ]);

        return response()->json([
            'success' => true,
            'todo_item' => $todoItem,
            'message' => 'Item berhasil ditambahkan'
        ]);
    }

    /**
     * Update todo item for personal todo list
     */
    public function updateTodoItem(Request $request, $itemId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        $todoItem = TodoItem::with(['todoList' => function($query) {
            $query->where('user_id', Auth::id())->whereNull('course_id');
        }])->findOrFail($itemId);

        if (!$todoItem->todoList) {
            abort(403, 'Anda tidak memiliki akses ke item ini');
        }

        $todoItem->update($request->all());

        return response()->json([
            'success' => true,
            'todo_item' => $todoItem,
            'message' => 'Item berhasil diupdate'
        ]);
    }

    /**
     * Delete todo item for personal todo list
     */
    public function deleteTodoItem($itemId)
    {
        $todoItem = TodoItem::with(['todoList' => function($query) {
            $query->where('user_id', Auth::id())->whereNull('course_id');
        }])->findOrFail($itemId);

        if (!$todoItem->todoList) {
            abort(403, 'Anda tidak memiliki akses ke item ini');
        }

        $todoItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus'
        ]);
    }

    /**
     * Upload file for personal todo list
     */
    public function uploadFile(Request $request, $todoListId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500',
        ]);

        $todoList = TodoList::where('user_id', Auth::id())
            ->whereNull('course_id')
            ->findOrFail($todoListId);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::random(32) . '.' . $extension;
        $filePath = "personal/{$todoListId}/" . $storedName;

        // Store file
        Storage::put($filePath, file_get_contents($file));

        // Create file record
        $fileRecord = File::create([
            'todo_list_id' => $todoListId,
            'uploaded_by_user_id' => Auth::id(),
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_extension' => $extension,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'file' => $fileRecord,
            'message' => 'File berhasil diupload'
        ]);
    }

    /**
     * Download file from personal todo list
     */
    public function downloadFile($fileId)
    {
        $file = File::with(['todoList' => function($query) {
            $query->where('user_id', Auth::id())->whereNull('course_id');
        }])->findOrFail($fileId);

        if (!$file->todoList) {
            abort(403, 'Anda tidak memiliki akses ke file ini');
        }

        // Increment download count
        $file->increment('download_count');

        return Storage::download($file->file_path, $file->original_filename);
    }

    /**
     * Delete file from personal todo list
     */
    public function deleteFile($fileId)
    {
        $file = File::with(['todoList' => function($query) {
            $query->where('user_id', Auth::id())->whereNull('course_id');
        }])->findOrFail($fileId);

        if (!$file->todoList) {
            abort(403, 'Anda tidak memiliki akses ke file ini');
        }

        // Delete file from storage
        Storage::delete($file->file_path);
        
        // Delete record
        $file->delete();

        return response()->json([
            'success' => true,
            'message' => 'File berhasil dihapus'
        ]);
    }

    /**
     * Get todo lists by category for personal dashboard
     */
    public function getTodoListsByCategory($categoryId)
    {
        $todoLists = TodoList::where('user_id', Auth::id())
            ->whereNull('course_id')
            ->where('category_id', $categoryId)
            ->with(['category', 'items', 'files'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'todo_lists' => $todoLists
        ]);
    }

    /**
     * Get todo lists by priority for personal dashboard
     */
    public function getTodoListsByPriority($priority)
    {
        $todoLists = TodoList::where('user_id', Auth::id())
            ->whereNull('course_id')
            ->where('priority', $priority)
            ->with(['category', 'items', 'files'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'todo_lists' => $todoLists
        ]);
    }

    /**
     * Get todo lists by status for personal dashboard
     */
    public function getTodoListsByStatus($status)
    {
        $todoLists = TodoList::where('user_id', Auth::id())
            ->whereNull('course_id')
            ->where('status', $status)
            ->with(['category', 'items', 'files'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'todo_lists' => $todoLists
        ]);
    }

    /**
     * Get overdue tasks for personal dashboard
     */
    public function getOverdueTasks()
    {
        $todoLists = TodoList::where('user_id', Auth::id())
            ->whereNull('course_id')
            ->where('deadline', '<', now())
            ->where('status', '!=', 'completed')
            ->with(['category', 'items' => function($query) {
                $query->where('is_completed', false);
            }, 'files'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'todo_lists' => $todoLists
        ]);
    }

    /**
     * Mark todo list as completed
     */
    public function markAsCompleted($todoListId)
    {
        $todoList = TodoList::where('user_id', Auth::id())
            ->whereNull('course_id')
            ->findOrFail($todoListId);

        $todoList->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);

        // Mark all items as completed
        $todoList->items()->update(['is_completed' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Todo list berhasil ditandai sebagai selesai'
        ]);
    }
} 