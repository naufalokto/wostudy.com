<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_list_id',
        'todo_item_id',
        'uploaded_by_user_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size',
        'mime_type',
        'file_extension',
        'description',
        'download_count',
        'is_public'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_public' => 'boolean',
    ];

    // Relationships
    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class);
    }

    public function todoItem(): BelongsTo
    {
        return $this->belongsTo(TodoItem::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(FilePermission::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeForTodoList($query, $todoListId)
    {
        return $query->where('todo_list_id', $todoListId);
    }

    // Methods
    public function getFullPath(): string
    {
        return storage_path("app/{$this->file_path}");
    }

    public function getDownloadUrl(): string
    {
        return route('files.download', $this->id);
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function canUserAccess(User $user, string $permission = 'can_view'): bool
    {
        // Uploader can do everything
        if ($this->uploaded_by_user_id === $user->id) {
            return true;
        }

        // Public files can be viewed by anyone
        if ($this->is_public && $permission === 'can_view') {
            return true;
        }

        // Check specific permissions
        $filePermission = $this->permissions()
            ->where('user_id', $user->id)
            ->where('permission_type', $permission)
            ->where('is_active', true)
            ->first();

        return $filePermission !== null;
    }

    public function getFormattedSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
} 