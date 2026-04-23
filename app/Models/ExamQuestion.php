<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    protected $fillable = [
        'exam_id',
        'position',
        'prompt',
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
}
