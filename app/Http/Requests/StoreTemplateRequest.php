<?php

namespace App\Http\Requests;

use App\Models\Template;
use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Template::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'kind' => ['required', 'in:'.Template::KIND_RESULT_MARKLIST.','.Template::KIND_ACADEMIC_REPORT],
            'slug' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'layout_view' => ['nullable', 'string', 'max:200'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
