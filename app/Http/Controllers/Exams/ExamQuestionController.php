<?php

namespace App\Http\Controllers\Exams;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamQuestionController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request, Exam $exam): View
    {
        $this->authorize('viewBank', [ExamQuestion::class, $exam]);

        return view('exams.questions.index', [
            'exam' => $exam->load('creator.role'),
            'questions' => $exam->questions()->with('options')->get(),
            'canManage' => $request->user()->can('manage', [ExamQuestion::class, $exam]),
        ]);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('manage', [ExamQuestion::class, $exam]);

        $data = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:2000'],
            'type' => ['required', Rule::in(ExamQuestion::TYPES)],
            'marks' => ['required', 'integer', 'min:1', 'max:100'],
            'answer_key' => ['nullable', 'string', 'max:2000'],
            'options' => ['nullable', 'array', 'min:2', 'max:6'],
            'options.*.text' => ['required_with:options', 'string', 'max:500'],
            'options.*.is_correct' => ['nullable', 'boolean'],
        ]);

        if ($data['type'] === ExamQuestion::TYPE_MCQ) {
            if (empty($data['options']) || count($data['options']) < 2) {
                return back()
                    ->withErrors(['options' => 'MCQ questions need at least 2 options.'])
                    ->withInput();
            }
            $hasCorrect = collect($data['options'])->contains(fn ($o) => ! empty($o['is_correct']));
            if (! $hasCorrect) {
                return back()
                    ->withErrors(['options' => 'Mark at least one option as correct.'])
                    ->withInput();
            }
        }

        if ($data['type'] === ExamQuestion::TYPE_TRUE_FALSE) {
            $key = strtolower((string) ($data['answer_key'] ?? ''));
            if (! in_array($key, ['true', 'false'], true)) {
                return back()
                    ->withErrors(['answer_key' => 'True/False questions need answer_key = true or false.'])
                    ->withInput();
            }
            $data['answer_key'] = $key;
        }

        DB::transaction(function () use ($exam, $data) {
            $position = ((int) $exam->questions()->max('position')) + 1;
            $question = $exam->questions()->create([
                'position' => $position,
                'prompt' => $data['prompt'],
                'type' => $data['type'],
                'marks' => $data['marks'],
                'answer_key' => $data['answer_key'] ?? null,
            ]);

            if ($data['type'] === ExamQuestion::TYPE_MCQ) {
                foreach (($data['options'] ?? []) as $idx => $opt) {
                    $question->options()->create([
                        'position' => $idx + 1,
                        'text' => $opt['text'],
                        'is_correct' => ! empty($opt['is_correct']),
                    ]);
                }
            }

            $this->logger->log('exam.question.created', $question, [
                'exam_id' => $exam->id,
                'type' => $question->type,
            ]);
        });

        return redirect()->route('exams.questions.index', $exam)->with('status', 'Question added.');
    }

    public function destroy(Request $request, ExamQuestion $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $exam = $question->exam;
        $questionId = $question->id;
        $question->delete();

        $this->logger->log('exam.question.deleted', null, [
            'exam_id' => $exam->id,
            'question_id' => $questionId,
        ]);

        return redirect()->route('exams.questions.index', $exam)->with('status', 'Question removed.');
    }
}
