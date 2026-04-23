<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'school_id',
        'creator_id',
        'creator_role_level',
        'locked_to_id',
        'approver_id',
        'title',
        'subject',
        'form_level',
        'curriculum',
        'duration_minutes',
        'total_marks',
        'scheduled_at',
        'status',
        'notes',
        'settings',
    ];

    protected $casts = [
        'creator_role_level' => 'integer',
        'duration_minutes' => 'integer',
        'total_marks' => 'integer',
        'scheduled_at' => 'datetime',
        'settings' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function lockedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_to_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class)->orderBy('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ExamEvent::class)->latest();
    }

    public function isLocked(): bool
    {
        return $this->locked_to_id !== null;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function isPublishable(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
