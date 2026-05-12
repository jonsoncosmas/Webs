<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\TemplateAssignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AssignmentPdfController extends Controller
{
    public function download(TemplateAssignment $assignment): Response
    {
        $this->authorize('view', $assignment);

        $assignment->loadMissing(['template', 'school', 'creator', 'assignee']);

        $filename = $this->filename($assignment);

        $pdf = Pdf::loadView('pdf.marklist', [
            'assignment' => $assignment,
            'title' => $filename,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    private function filename(TemplateAssignment $assignment): string
    {
        $kind = $assignment->template?->kind ?? 'marklist';
        $class = Str::slug($assignment->class_label ?? 'class');
        $subject = $assignment->subject ? '-'.Str::slug($assignment->subject) : '';

        return sprintf('%s-%s%s-%d.pdf', $kind, $class, $subject, $assignment->id);
    }
}
