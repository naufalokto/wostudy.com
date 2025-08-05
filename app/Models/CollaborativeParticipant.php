<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CollaborativeParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_list_id',
        'user_id',
        'shared_todo_list_id',
        'permission_type',
        'status',
        'last_seen_at',
        'joined_at',
        'left_at',
        'session_id',
        'user_agent',
        'ip_address',
        'is_active'
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'user_agent' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sharedTodoList(): BelongsTo
    {
        return $this->belongsTo(SharedTodoList::class);
    }

    public function userPresence(): HasOne
    {
        return $this->hasOne(UserPresence::class, 'user_id', 'user_id')
            ->where('todo_list_id', $this->todo_list_id);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeAway($query)
    {
        return $query->where('status', 'away');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
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
    public function markAsOnline(): void
    {
        $this->update([
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }

    public function markAsAway(): void
    {
        $this->update([
            'status' => 'away',
            'last_seen_at' => now(),
        ]);
    }

    public function markAsOffline(): void
    {
        $this->update([
            'status' => 'offline',
            'left_at' => now(),
        ]);
    }

    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isAway(): bool
    {
        return $this->status === 'away';
    }

    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    public function canEdit(): bool
    {
        return $this->permission_type === 'can_edit';
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'online' => 'green',
            'away' => 'yellow',
            'offline' => 'gray',
            default => 'gray'
        };
    }

    public function getStatusIcon(): string
    {
        return match($this->status) {
            'online' => '🟢',
            'away' => '🟡',
            'offline' => '⚪',
            default => '⚪'
        };
    }

    public function getTimeSinceLastSeen(): string
    {
        if (!$this->last_seen_at) {
            return 'Never';
        }

        $diff = now()->diff($this->last_seen_at);
        
        if ($diff->days > 0) {
            return $diff->days . ' days ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hours ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minutes ago';
        } else {
            return 'Just now';
        }
    }
} 