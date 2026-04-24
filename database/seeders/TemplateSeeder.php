<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the two built-in templates every school starts with:
 *   - Result marklist (basic table of students & marks)
 *   - Academic report (per-student report card)
 */
class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::whereHas('role', fn ($q) => $q->where('slug', Role::SYSTEM_ADMIN))->first();

        Template::updateOrCreate(
            ['slug' => 'result-marklist-basic'],
            [
                'created_by' => $creator?->id,
                'kind' => Template::KIND_RESULT_MARKLIST,
                'name' => 'Basic result marklist',
                'description' => 'Class-wide marklist with students, scores and average. Prints on A4 portrait.',
                'layout' => ['view' => 'templates.layouts.result_marklist_basic'],
                'is_active' => true,
            ],
        );

        Template::updateOrCreate(
            ['slug' => 'academic-report-standard'],
            [
                'created_by' => $creator?->id,
                'kind' => Template::KIND_ACADEMIC_REPORT,
                'name' => 'Standard academic report card',
                'description' => 'Per-student report card showing subjects, grades, comments and signatures. A4 portrait.',
                'layout' => ['view' => 'templates.layouts.academic_report_standard'],
                'is_active' => true,
            ],
        );
    }
}
