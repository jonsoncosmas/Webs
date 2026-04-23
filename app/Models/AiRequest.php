<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'task_type',
        'provider',
        'model',
        'prompt_hash',
        'input_tokens',
        'output_tokens',
        'latency_ms',
        'status',
        'error',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
