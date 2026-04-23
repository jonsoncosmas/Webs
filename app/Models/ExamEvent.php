<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamEvent extends Model
{
    public $timestamps = false;

    public const CREATED = 'created';

    public const SUBMITTED = 'submitted';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const OVERRIDDEN = 'overridden';

    public const PUBLISHED = 'published';

    public const ARCHIVED = 'archived';

    public const EDITED = 'edited';

    protected $fillable = [
        'exam_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'comment',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
