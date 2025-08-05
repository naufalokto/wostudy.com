<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'user_id',
        'permission_type',
        'granted_by_user_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCanEdit($query)
    {
        return $query->where('permission_type', 'can_edit');
    }

    public function scopeCanView($query)
    {
        return $query->where('permission_type', 'can_view');
    }

    public function scopeCanDownload($query)
    {
        return $query->where('permission_type', 'can_download');
    }

    // Methods
    public function canEdit(): bool
    {
        return $this->permission_type === 'can_edit';
    }

    public function canView(): bool
    {
        return in_array($this->permission_type, ['can_view', 'can_edit', 'can_download']);
    }

    public function canDownload(): bool
    {
        return in_array($this->permission_type, ['can_download', 'can_edit']);
    }
} 