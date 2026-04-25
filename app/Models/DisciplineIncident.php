<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplineIncident extends Model
{
    public const CATEGORY_MINOR = 'minor';

    public const CATEGORY_MAJOR = 'major';

    public const CATEGORY_WARNING = 'warning';

    public const CATEGORY_SUSPENSION = 'suspension';

    public const CATEGORY_COMMENDATION = 'commendation';

    public const CATEGORY_TEACHER_CONDUCT = 'teacher_conduct';

    public const CATEGORIES = [
        self::CATEGORY_MINOR,
        self::CATEGORY_MAJOR,
        self::CATEGORY_WARNING,
        self::CATEGORY_SUSPENSION,
        self::CATEGORY_COMMENDATION,
        self::CATEGORY_TEACHER_CONDUCT,
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'school_id',
        'subject_id',
        'subject_role',
        'reported_by',
        'decided_by',
        'category',
        'title',
        'description',
        'occurred_on',
        'severity',
        'status',
        'resolution',
        'decided_at',
        'metadata',
    ];

    protected $casts = [
        'occurred_on' => 'date',
        'decided_at' => 'datetime',
        'severity' => 'integer',
        'metadata' => 'array',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function categoryLabel(): string
    {
        return match ($this->category) {
            self::CATEGORY_MINOR => 'Minor',
            self::CATEGORY_MAJOR => 'Major',
            self::CATEGORY_WARNING => 'Warning',
            self::CATEGORY_SUSPENSION => 'Suspension',
            self::CATEGORY_COMMENDATION => 'Commendation',
            self::CATEGORY_TEACHER_CONDUCT => 'Teacher conduct',
        };
    }

    public function isPositive(): bool
    {
        return $this->category === self::CATEGORY_COMMENDATION;
    }
}
