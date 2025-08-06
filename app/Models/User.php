<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_picture',
        'department',
        'student_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }



    // Relationships for collaborative features
    public function todoLists(): HasMany
    {
        return $this->hasMany(TodoList::class);
    }

    public function sharedTodoLists(): HasMany
    {
        return $this->hasMany(SharedTodoList::class, 'shared_by_user_id');
    }

    public function sharedWithMe(): HasMany
    {
        return $this->hasMany(SharedTodoList::class, 'shared_with_user_id');
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(File::class, 'uploaded_by_user_id');
    }

    public function filePermissions(): HasMany
    {
        return $this->hasMany(FilePermission::class);
    }

    public function grantedFilePermissions(): HasMany
    {
        return $this->hasMany(FilePermission::class, 'granted_by_user_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Collaborative presence relationships
    public function collaborativeParticipants(): HasMany
    {
        return $this->hasMany(CollaborativeParticipant::class);
    }

    public function userPresence(): HasMany
    {
        return $this->hasMany(UserPresence::class);
    }

    // Course relationships
    public function userCourses(): HasMany
    {
        return $this->hasMany(UserCourse::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'user_courses');
    }

    // Methods for collaborative features
    public function getCurrentCollaborativeSessions()
    {
        return $this->collaborativeParticipants()
            ->with(['todoList', 'sharedTodoList'])
            ->active()
            ->online()
            ->get();
    }

    public function isOnlineInSession($todoListId): bool
    {
        return $this->collaborativeParticipants()
            ->where('todo_list_id', $todoListId)
            ->where('status', 'online')
            ->exists();
    }

    public function getAvatarUrl(): string
    {
        $initials = strtoupper(substr($this->name, 0, 2));
        $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8'];
        $color = $colors[array_rand($colors)];
        
        return "https://ui-avatars.com/api/?name={$initials}&background={$color}&color=fff&size=40";
    }
}
