<?php

namespace App\Http\Requests;

use App\Models\Template;
use App\Models\TemplateAssignment;
use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TemplateAssignment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'exists:templates,id'],
            'class_label' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:120'],
            'term' => ['nullable', 'string', 'max:60'],
            'assigned_to_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function template(): Template
    {
        return Template::findOrFail($this->validated()['template_id']);
    }
}
