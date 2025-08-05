<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedTodoList extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_list_id',
        'shared_by_user_id',
        'shared_with_user_id',
        'permission_type',
        'share_link',
        'is_active',
        'expires_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeCanEdit($query)
    {
        return $query->where('permission_type', 'can_edit');
    }

    public function scopeCanView($query)
    {
        return $query->where('permission_type', 'can_view');
    }

    // Methods
    public function generateShareLink(): string
    {
        $token = \Str::random(32);
        $this->update(['share_link' => $token]);
        return $token;
    }

    public function getFullShareUrl(): string
    {
        return url("/shared/{$this->share_link}");
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canEdit(): bool
    {
        return $this->permission_type === 'can_edit';
    }

    public function canView(): bool
    {
        return $this->permission_type === 'can_view' || $this->permission_type === 'can_edit';
    }
} 