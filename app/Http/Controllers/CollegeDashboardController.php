<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Semester;
use App\Models\Instructor;
use App\Models\TodoList;
use App\Models\TodoItem;
use App\Models\TodoCategory;
use App\Models\UserCourse;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CollegeDashboardController extends Controller
{
    /**
     * Show college dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get current semester
        $currentSemester = Semester::where('is_active', true)->first();
        
        // Get user's courses for current semester
        $courses = Course::whereHas('userCourses', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['instructor', 'semester', 'todoLists' => function($query) {
            $query->with('items')->latest();
        }])
        ->where('semester_id', $currentSemester->id ?? 0)
        ->get();

        // Get todo lists for college
        $todoLists = TodoList::where('user_id', $user->id)
            ->whereNotNull('course_id')
            ->with(['course', 'category', 'items'])
            ->latest()
            ->get();

        // Get categories
        $categories = TodoCategory::all();

        return view('college.dashboard', compact('courses', 'todoLists', 'categories', 'currentSemester'));
    }

    /**
     * Show course detail with todo lists
     */
    public function showCourse($courseId)
    {
        $course = Course::with(['instructor', 'semester', 'todoLists.items'])
            ->findOrFail($courseId);

        $user = Auth::user();
        
        // Check if user is enrolled in this course
        $isEnrolled = UserCourse::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$isEnrolled) {
            abort(403, 'Anda tidak terdaftar dalam matakuliah ini');
        }

        $categories = TodoCategory::all();

        return view('college.course-detail', compact('course', 'categories'));
    }

    /**
     * Store new course (for admin/instructor)
     */
    public function storeCourse(Request $request)
    {
        $request->validate([
            'course_code' => 'required|string|max:20',
            'course_name' => 'required|string|max:255',
            'course_type' => 'required|in:lab,lecture_only,lab_lecture',
            'instructor_id' => 'required|exists:instructors,id',
            'semester_id' => 'required|exists:semesters,id',
            'credits' => 'required|integer|min:1|max:6',
            'description' => 'nullable|string',
            'schedule_day' => 'nullable|string|max:20',
            'schedule_time' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:50',
        ]);

        $course = Course::create($request->all());

        return response()->json([
            'success' => true,
            'course' => $course->load(['instructor', 'semester']),
            'message' => 'Matakuliah berhasil ditambahkan'
        ]);
    }

    /**
     * Store new todo list for college
     */
    public function storeTodoList(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'category_id' => 'required|exists:todo_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'task_type' => 'required|in:individual,group',
            'priority' => 'required|in:low,medium,high,urgent',
            'deadline' => 'required|date|after:now',
        ]);

        $todoList = TodoList::create([
            'user_id' => Auth::id(),
            'course_id' => $request->course_id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'task_type' => $request->task_type,
            'priority' => $request->priority,
            'deadline' => $request->deadline,
        ]);

        return response()->json([
            'success' => true,
            'todo_list' => $todoList->load(['course', 'category']),
            'message' => 'Todo list berhasil ditambahkan'
        ]);
    }

    /**
     * Store new todo item
     */
    public function storeTodoItem(Request $request, $todoListId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        $todoList = TodoList::findOrFail($todoListId);
        
        // Check if user owns the todo list
        if ($todoList->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke todo list ini');
        }

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
     * Update todo item
     */
    public function updateTodoItem(Request $request, $itemId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_completed' => 'boolean',
        ]);

        $todoItem = TodoItem::with('todoList')->findOrFail($itemId);
        
        // Check if user owns the todo list
        if ($todoItem->todoList->user_id !== Auth::id()) {
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
     * Delete todo item
     */
    public function deleteTodoItem($itemId)
    {
        $todoItem = TodoItem::with('todoList')->findOrFail($itemId);
        
        // Check if user owns the todo list
        if ($todoItem->todoList->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke item ini');
        }

        $todoItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus'
        ]);
    }

    /**
     * Upload file for todo list
     */
    public function uploadFile(Request $request, $todoListId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500',
        ]);

        $todoList = TodoList::findOrFail($todoListId);
        
        // Check if user owns the todo list
        if ($todoList->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke todo list ini');
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::random(32) . '.' . $extension;
        $filePath = "college/{$todoListId}/" . $storedName;

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
     * Download file
     */
    public function downloadFile($fileId)
    {
        $file = File::with('todoList')->findOrFail($fileId);
        
        // Check if user owns the todo list
        if ($file->todoList->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke file ini');
        }

        // Increment download count
        $file->increment('download_count');

        return Storage::download($file->file_path, $file->original_filename);
    }

    /**
     * Delete file
     */
    public function deleteFile($fileId)
    {
        $file = File::with('todoList')->findOrFail($fileId);
        
        // Check if user owns the todo list
        if ($file->todoList->user_id !== Auth::id()) {
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
     * Get todo lists by course
     */
    public function getTodoListsByCourse($courseId)
    {
        $todoLists = TodoList::where('user_id', Auth::id())
            ->where('course_id', $courseId)
            ->with(['category', 'items', 'files'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'todo_lists' => $todoLists
        ]);
    }

    /**
     * Get todo lists by category
     */
    public function getTodoListsByCategory($categoryId)
    {
        $todoLists = TodoList::where('user_id', Auth::id())
            ->where('category_id', $categoryId)
            ->whereNotNull('course_id')
            ->with(['course', 'category', 'items', 'files'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'todo_lists' => $todoLists
        ]);
    }
} 