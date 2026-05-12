<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ResultPdfController extends Controller
{
    public function download(ExamAttempt $attempt): Response
    {
        $this->authorize('view', $attempt);

        $attempt->loadMissing(['exam', 'school', 'student', 'scorer']);

        $studentSlug = Str::slug($attempt->student?->fullName() ?: 'student');
        $examSlug = Str::slug($attempt->exam?->title ?: 'exam');
        $filename = sprintf('result-%s-%s.pdf', $studentSlug, $examSlug);

        $pdf = Pdf::loadView('pdf.result', [
            'attempt' => $attempt,
            'title' => 'Exam result — '.($attempt->exam?->title ?? '—'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
