<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    public const KIND_RESULT_MARKLIST = 'result_marklist';

    public const KIND_ACADEMIC_REPORT = 'academic_report';

    protected $fillable = [
        'created_by',
        'kind',
        'slug',
        'name',
        'description',
        'layout',
        'is_active',
    ];

    protected $casts = [
        'layout' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TemplateAssignment::class);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            self::KIND_RESULT_MARKLIST => 'Result marklist',
            self::KIND_ACADEMIC_REPORT => 'Academic report',
            default => ucfirst(str_replace('_', ' ', (string) $this->kind)),
        };
    }

    /**
     * Blade view to use for rendering an assignment with this template.
     */
    public function viewName(): string
    {
        return (string) ($this->layout['view'] ?? 'templates.layouts.generic');
    }

    public static function kinds(): array
    {
        return [
            self::KIND_RESULT_MARKLIST => 'Result marklist',
            self::KIND_ACADEMIC_REPORT => 'Academic report',
        ];
    }
}
