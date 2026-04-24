<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateAssignment extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_READY = 'ready';

    public const STATUS_PRINTED = 'printed';

    protected $fillable = [
        'school_id',
        'template_id',
        'created_by',
        'assigned_to_id',
        'class_label',
        'subject',
        'term',
        'status',
        'notes',
        'data',
        'printed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'printed_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function isEditable(): bool
    {
        return $this->status !== self::STATUS_PRINTED;
    }

    public function statusLabel(): string
    {
        return str_replace('_', ' ', (string) $this->status);
    }
}
