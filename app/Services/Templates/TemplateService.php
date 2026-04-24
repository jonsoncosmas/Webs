<?php

namespace App\Services\Templates;

use App\Models\Template;
use App\Models\TemplateAssignment;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Str;

class TemplateService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Create a new template definition (System Admin).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createTemplate(User $creator, array $attributes): Template
    {
        $slug = $attributes['slug'] ?? Str::slug((string) ($attributes['name'] ?? 'template'));

        $template = Template::create([
            'created_by' => $creator->id,
            'kind' => $attributes['kind'],
            'slug' => $this->uniqueSlug($slug),
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'layout' => $attributes['layout'] ?? ['view' => 'templates.layouts.generic'],
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ]);

        $this->logger->log('template.created', $template, [
            'kind' => $template->kind,
            'name' => $template->name,
        ]);

        return $template;
    }

    public function toggleActive(User $actor, Template $template, bool $active): Template
    {
        $template->update(['is_active' => $active]);

        $this->logger->log($active ? 'template.activated' : 'template.archived', $template, [
            'name' => $template->name,
        ]);

        return $template;
    }

    /**
     * Create a new assignment (school user picks template, class, staff).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createAssignment(User $creator, Template $template, array $attributes): TemplateAssignment
    {
        $assignment = TemplateAssignment::create([
            'school_id' => $creator->school_id,
            'template_id' => $template->id,
            'created_by' => $creator->id,
            'assigned_to_id' => $attributes['assigned_to_id'] ?? null,
            'class_label' => $attributes['class_label'],
            'subject' => $attributes['subject'] ?? null,
            'term' => $attributes['term'] ?? null,
            'status' => TemplateAssignment::STATUS_DRAFT,
            'notes' => $attributes['notes'] ?? null,
            'data' => $attributes['data'] ?? null,
        ]);

        $this->logger->log('template_assignment.created', $assignment, [
            'template' => $template->slug,
            'class' => $assignment->class_label,
        ]);

        return $assignment;
    }

    /**
     * Save updates to an assignment's data / notes / assignee (while editable).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateAssignment(User $actor, TemplateAssignment $assignment, array $attributes): TemplateAssignment
    {
        $assignment->fill(array_filter([
            'assigned_to_id' => $attributes['assigned_to_id'] ?? $assignment->assigned_to_id,
            'class_label' => $attributes['class_label'] ?? $assignment->class_label,
            'subject' => array_key_exists('subject', $attributes) ? $attributes['subject'] : $assignment->subject,
            'term' => array_key_exists('term', $attributes) ? $attributes['term'] : $assignment->term,
            'notes' => array_key_exists('notes', $attributes) ? $attributes['notes'] : $assignment->notes,
            'data' => array_key_exists('data', $attributes) ? $attributes['data'] : $assignment->data,
        ], fn ($v) => $v !== null));
        $assignment->save();

        $this->logger->log('template_assignment.updated', $assignment);

        return $assignment;
    }

    public function markReady(User $actor, TemplateAssignment $assignment): TemplateAssignment
    {
        $assignment->update(['status' => TemplateAssignment::STATUS_READY]);

        $this->logger->log('template_assignment.ready', $assignment);

        return $assignment;
    }

    public function reopen(User $actor, TemplateAssignment $assignment): TemplateAssignment
    {
        $assignment->update(['status' => TemplateAssignment::STATUS_DRAFT]);

        $this->logger->log('template_assignment.reopened', $assignment);

        return $assignment;
    }

    public function markPrinted(User $actor, TemplateAssignment $assignment): TemplateAssignment
    {
        if ($assignment->status !== TemplateAssignment::STATUS_PRINTED) {
            $assignment->update([
                'status' => TemplateAssignment::STATUS_PRINTED,
                'printed_at' => now(),
            ]);

            $this->logger->log('template_assignment.printed', $assignment);
        }

        return $assignment;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $i = 2;
        while (Template::where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$i++;
        }

        return $candidate;
    }
}
