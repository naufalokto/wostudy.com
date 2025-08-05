<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TodoList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'category_id',
        'title',
        'description',
        'task_type',
        'priority',
        'status',
        'deadline',
        'completed_at',
        'is_public',
        'share_token'
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TodoCategory::class, 'category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TodoItem::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    public function sharedAccess(): HasMany
    {
        return $this->hasMany(SharedTodoList::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeCollaborative($query)
    {
        return $query->where('task_type', 'group');
    }

    // Methods
    public function generateShareToken(): string
    {
        $token = \Str::random(32);
        $this->update(['share_token' => $token]);
        return $token;
    }

    public function getShareUrl(): string
    {
        return url("/shared/{$this->share_token}");
    }

    public function canUserAccess(User $user, string $permission = 'can_view'): bool
    {
        // Owner can do everything
        if ($this->user_id === $user->id) {
            return true;
        }

        // Check shared access
        $sharedAccess = $this->sharedAccess()
            ->where('shared_with_user_id', $user->id)
            ->where('is_active', true)
            ->where('permission_type', $permission)
            ->first();

        return $sharedAccess !== null;
    }
} 