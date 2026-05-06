<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\Exams\ExamTakingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ExamTakingController extends Controller
{
    public function __construct(private readonly ExamTakingService $service) {}

    /**
     * Start (or resume) an in-progress attempt and show the taking form.
     */
    public function take(Request $request, Exam $exam): View|RedirectResponse
    {
        $this->authorize('take', [ExamAttempt::class, $exam]);

        try {
            $attempt = $this->service->startOrResume($request->user(), $exam);
        } catch (RuntimeException $e) {
            return redirect()->route('portal.exams')->with('status', $e->getMessage());
        }

        if ($attempt->isSubmitted()) {
            return redirect()->route('portal.result.show', $attempt);
        }

        $attempt->load('answers');

        $answersByQuestion = $attempt->answers->keyBy('exam_question_id');

        return view('portal.take', [
            'exam' => $exam->load(['questions.options']),
            'attempt' => $attempt,
            'answers' => $answersByQuestion,
        ]);
    }

    /**
     * Bulk-save answers + submit the attempt.
     */
    public function submit(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('take', [ExamAttempt::class, $exam]);

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_user_id', $request->user()->id)
            ->firstOrFail();

        $this->authorize('submit', $attempt);

        if ($attempt->isSubmitted()) {
            return redirect()->route('portal.result.show', $attempt);
        }

        $data = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*.selected_option_id' => ['nullable', 'integer'],
            'answers.*.answer_text' => ['nullable', 'string', 'max:4000'],
        ]);

        foreach (($data['answers'] ?? []) as $questionId => $payload) {
            $question = $exam->questions()->find($questionId);
            if (! $question) {
                continue;
            }
            try {
                $this->service->recordAnswer($attempt, $question, $payload);
            } catch (RuntimeException $e) {
                // Deadline or state race — ignore per-answer failure; final submit will decide.
            }
        }

        try {
            $this->service->submit($attempt);
        } catch (RuntimeException $e) {
            return redirect()->route('portal.exams')->with('status', $e->getMessage());
        }

        return redirect()->route('portal.result.show', $attempt)
            ->with('status', 'Exam submitted.');
    }
}
