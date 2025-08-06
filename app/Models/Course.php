<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_id',
        'instructor_id',
        'course_code',
        'course_name',
        'course_type',
        'credits',
        'description',
        'schedule_day',
        'schedule_time',
        'room',
        'is_active'
    ];

    protected $casts = [
        'credits' => 'integer',
        'is_active' => 'boolean',
    ];

    // Course type constants
    const TYPE_LAB = 'lab';
    const TYPE_LECTURE_ONLY = 'lecture_only';
    const TYPE_LAB_LECTURE = 'lab_lecture';

    // Get all available course types
    public static function getCourseTypes()
    {
        return [
            self::TYPE_LAB => 'Lab',
            self::TYPE_LECTURE_ONLY => 'Lecture Only',
            self::TYPE_LAB_LECTURE => 'Lab & Lecture',
        ];
    }

    // Relationships
    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function todoLists(): HasMany
    {
        return $this->hasMany(TodoList::class);
    }

    public function userCourses(): HasMany
    {
        return $this->hasMany(UserCourse::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_courses');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('course_type', $type);
    }
} 