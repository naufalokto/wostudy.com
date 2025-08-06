<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'semester_number',
        'semester_name',
        'start_date',
        'end_date',
        'is_active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    public function getFullNameAttribute(): string
    {
        return "{$this->semester_name} {$this->academic_year}";
    }

    public function isCurrent(): bool
    {
        return $this->is_active;
    }

    public function isInProgress(): bool
    {
        $now = now();
        return $now->between($this->start_date, $this->end_date);
    }
} 