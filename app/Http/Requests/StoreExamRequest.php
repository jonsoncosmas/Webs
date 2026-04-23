<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Exam::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'subject' => ['required', 'string', 'max:100'],
            'form_level' => ['nullable', 'string', 'max:40'],
            'curriculum' => ['nullable', 'string', 'in:NECTA,Cambridge,Other'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:600'],
            'total_marks' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
