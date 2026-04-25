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

    protected $fillable = [
        'exam_id',
        'student_user_id',
        'school_id',
        'score',
        'total_marks',
        'grade',
        'status',
        'scored_by',
        'scored_at',
        'notes',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_marks' => 'integer',
        'scored_at' => 'datetime',
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

    public function percentage(): ?float
    {
        if ($this->score === null || ! $this->total_marks || $this->total_marks <= 0) {
            return null;
        }

        return round(($this->score / $this->total_marks) * 100, 1);
    }
}
