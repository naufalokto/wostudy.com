<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'todo_list_id',
        'session_id',
        'status',
        'last_activity_at',
        'current_activity',
        'cursor_position'
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'current_activity' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class);
    }

    // Scopes
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

    public function scopeActive($query)
    {
        return $query->where('last_activity_at', '>', now()->subMinutes(5));
    }

    // Methods
    public function updateActivity(string $activity = null, string $cursorPosition = null): void
    {
        $this->update([
            'status' => 'online',
            'last_activity_at' => now(),
            'current_activity' => $activity ? ['action' => $activity] : $this->current_activity,
            'cursor_position' => $cursorPosition,
        ]);
    }

    public function markAsAway(): void
    {
        $this->update([
            'status' => 'away',
            'last_activity_at' => now(),
        ]);
    }

    public function markAsOffline(): void
    {
        $this->update([
            'status' => 'offline',
            'last_activity_at' => now(),
        ]);
    }

    public function isActive(): bool
    {
        return $this->last_activity_at && $this->last_activity_at->isAfter(now()->subMinutes(5));
    }

    public function getActivityDescription(): string
    {
        if (!$this->current_activity) {
            return 'Idle';
        }

        $activities = [
            'typing' => 'Mengetik...',
            'editing_item' => 'Mengedit item',
            'uploading_file' => 'Mengupload file',
            'downloading_file' => 'Mendownload file',
            'viewing_files' => 'Melihat files',
            'browsing_items' => 'Menjelajahi items',
        ];

        $action = $this->current_activity['action'] ?? 'idle';
        return $activities[$action] ?? 'Aktif';
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
} 