<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_list_id',
        'user_id',
        'action',
        'data',
        'ip_address'
    ];

    protected $casts = [
        'data' => 'array',
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

    // Scopes
    public function scopeRecent($query, $limit = 10)
    {
        return $query->latest()->limit($limit);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    // Methods
    public function getActionDescription(): string
    {
        $descriptions = [
            'file_uploaded' => 'mengupload file',
            'file_downloaded' => 'mendownload file',
            'item_updated' => 'mengupdate item',
            'item_created' => 'membuat item baru',
            'item_completed' => 'menyelesaikan item',
            'list_shared' => 'membagikan todo list',
            'permission_granted' => 'memberikan permission',
        ];

        return $descriptions[$this->action] ?? $this->action;
    }

    public function getFormattedData(): array
    {
        $data = $this->data ?? [];
        
        // Add user info if available
        if ($this->user) {
            $data['user_name'] = $this->user->name;
        }

        return $data;
    }
} 