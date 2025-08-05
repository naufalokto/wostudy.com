<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TodoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_list_id',
        'title',
        'description',
        'is_completed',
        'completed_at',
        'order_index'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'order_index' => 'integer',
    ];

    // Relationships
    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    // Methods
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now()
        ]);
    }

    public function markAsPending(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null
        ]);
    }
} 