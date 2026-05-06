<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    public const STATUS_SCORED = 'scored';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_ARCHIVED = 'archived';

    public const PHASE_IN_PROGRESS = 'in_progress';

    public const PHASE_SUBMITTED = 'submitted';

    public const PHASE_GRADED = 'graded';

    protected $fillable = [
        'exam_id',
        'student_user_id',
        'school_id',
        'score',
        'total_marks',
        'grade',
        'status',
        'take_phase',
        'started_at',
        'submitted_at',
        'deadline_at',
        'scored_by',
        'scored_at',
        'notes',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_marks' => 'integer',
        'scored_at' => 'datetime',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function scorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scored_by');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function reviewRequests(): HasMany
    {
        return $this->hasMany(ResultReviewRequest::class, 'attempt_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function isInProgress(): bool
    {
        return $this->take_phase === self::PHASE_IN_PROGRESS;
    }

    public function isSubmitted(): bool
    {
        return in_array($this->take_phase, [self::PHASE_SUBMITTED, self::PHASE_GRADED], true);
    }

    public function isDeadlinePassed(): bool
    {
        return $this->deadline_at !== null && $this->deadline_at->isPast();
    }

    public function percentage(): ?float
    {
        if ($this->score === null || ! $this->total_marks || $this->total_marks <= 0) {
            return null;
        }

        return round(($this->score / $this->total_marks) * 100, 1);
    }
}
