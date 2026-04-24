<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffCertificate extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'title',
        'issuer',
        'reference_no',
        'issued_on',
        'expires_on',
        'document_url',
        'notes',
        'is_active',
        'added_by',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'expires_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isPast();
    }
}
