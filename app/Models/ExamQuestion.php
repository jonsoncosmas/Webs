<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamQuestion extends Model
{
    public const TYPE_MCQ = 'mcq';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const TYPE_SHORT_ANSWER = 'short_answer';

    public const TYPES = [self::TYPE_MCQ, self::TYPE_TRUE_FALSE, self::TYPE_SHORT_ANSWER];

    protected $fillable = [
        'exam_id',
        'position',
        'prompt',
        'type',
        'marks',
        'answer_key',
    ];

    protected $casts = [
        'position' => 'integer',
        'marks' => 'integer',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->type, [self::TYPE_MCQ, self::TYPE_TRUE_FALSE], true);
    }
}
