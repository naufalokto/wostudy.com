<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\TodoList;
use App\Models\FilePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Get files for todo list
     */
    public function getFilesByTodoList($todoListId)
    {
        $todoList = TodoList::findOrFail($todoListId);
        
        // Check if user has access to this todo list
        if (!$todoList->canUserAccess(Auth::user(), 'can_view')) {
            abort(403, 'Anda tidak memiliki akses ke file ini');
        }

        $files = File::where('todo_list_id', $todoListId)
            ->with('uploadedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }

    /**
     * Upload file
     */
    public function upload(Request $request, $todoListId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'description' => 'nullable|string|max:500',
        ]);

        $todoList = TodoList::findOrFail($todoListId);
        
        // Check if user can edit
        if (!$todoList->canUserAccess(Auth::user(), 'can_edit')) {
            abort(403, 'Anda tidak memiliki permission untuk upload file');
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::random(32) . '.' . $extension;
        $filePath = "files/{$todoListId}/" . $storedName;

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
            'file' => $fileRecord->load('uploadedBy'),
            'message' => 'File berhasil diupload'
        ]);
    }

    /**
     * Download file
     */
    public function download($fileId)
    {
        $file = File::with('todoList')->findOrFail($fileId);
        
        // Check if user has access to this file
        if (!$file->todoList->canUserAccess(Auth::user(), 'can_view')) {
            abort(403, 'Anda tidak memiliki akses ke file ini');
        }

        // Increment download count
        $file->increment('download_count');

        return Storage::download($file->file_path, $file->original_filename);
    }

    /**
     * Delete file
     */
    public function destroy($fileId)
    {
        $file = File::with('todoList')->findOrFail($fileId);
        
        // Check if user can edit
        if (!$file->todoList->canUserAccess(Auth::user(), 'can_edit')) {
            abort(403, 'Anda tidak memiliki permission untuk menghapus file ini');
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
     * Update file description
     */
    public function update(Request $request, $fileId)
    {
        $request->validate([
            'description' => 'nullable|string|max:500',
        ]);

        $file = File::with('todoList')->findOrFail($fileId);
        
        // Check if user can edit
        if (!$file->todoList->canUserAccess(Auth::user(), 'can_edit')) {
            abort(403, 'Anda tidak memiliki permission untuk mengedit file ini');
        }

        $file->update($request->only(['description']));

        return response()->json([
            'success' => true,
            'file' => $file->load('uploadedBy'),
            'message' => 'Deskripsi file berhasil diupdate'
        ]);
    }

    /**
     * Get file info
     */
    public function show($fileId)
    {
        $file = File::with(['todoList', 'uploadedBy'])->findOrFail($fileId);
        
        // Check if user has access to this file
        if (!$file->todoList->canUserAccess(Auth::user(), 'can_view')) {
            abort(403, 'Anda tidak memiliki akses ke file ini');
        }

        return response()->json([
            'success' => true,
            'file' => $file
        ]);
    }

    /**
     * Get user's uploaded files
     */
    public function getUserFiles()
    {
        $files = File::where('uploaded_by_user_id', Auth::id())
            ->with(['todoList', 'todoItem'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }

    /**
     * Get files by type
     */
    public function getFilesByType($type)
    {
        $files = File::where('mime_type', 'like', $type . '%')
            ->whereHas('todoList', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->with(['todoList', 'uploadedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }

    /**
     * Search files
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->query;
        
        $files = File::where('original_filename', 'like', '%' . $query . '%')
            ->orWhere('description', 'like', '%' . $query . '%')
            ->whereHas('todoList', function($q) {
                $q->where('user_id', Auth::id());
            })
            ->with(['todoList', 'uploadedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'files' => $files
        ]);
    }

    /**
     * Get file statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total_files' => File::whereHas('todoList', function($query) {
                $query->where('user_id', Auth::id());
            })->count(),
            'total_size' => File::whereHas('todoList', function($query) {
                $query->where('user_id', Auth::id());
            })->sum('file_size'),
            'uploaded_today' => File::whereHas('todoList', function($query) {
                $query->where('user_id', Auth::id());
            })->whereDate('created_at', today())->count(),
            'most_downloaded' => File::whereHas('todoList', function($query) {
                $query->where('user_id', Auth::id());
            })->orderBy('download_count', 'desc')->first(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats
        ]);
    }
} 