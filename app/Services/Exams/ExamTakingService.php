<?php

namespace App\Services\Exams;

use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamTakingService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Start (or resume) a take for a published exam. Returns the attempt in
     * the PHASE_IN_PROGRESS phase. Fails if the student already submitted.
     */
    public function startOrResume(User $student, Exam $exam): ExamAttempt
    {
        if ($exam->status !== Exam::STATUS_PUBLISHED) {
            throw new RuntimeException('Exam is not published.');
        }

        $attempt = ExamAttempt::firstOrNew([
            'exam_id' => $exam->id,
            'student_user_id' => $student->id,
        ]);

        if ($attempt->exists && $attempt->isSubmitted()) {
            throw new RuntimeException('Attempt already submitted.');
        }

        if (! $attempt->exists) {
            $attempt->school_id = $student->school_id;
            $attempt->total_marks = $exam->total_marks ?: (int) $exam->questions()->sum('marks');
            $attempt->take_phase = ExamAttempt::PHASE_IN_PROGRESS;
            $attempt->status = ExamAttempt::STATUS_SCORED; // placeholder; overwritten on submit
            $attempt->started_at = now();
            $attempt->deadline_at = $exam->duration_minutes
                ? now()->addMinutes((int) $exam->duration_minutes)
                : null;
            $attempt->save();

            $this->logger->log('exam.attempt.started', $attempt, [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ]);
        }

        return $attempt;
    }

    /**
     * Record or update a single answer.
     *
     * @param  array{selected_option_id?: int|null, answer_text?: string|null}  $payload
     */
    public function recordAnswer(ExamAttempt $attempt, ExamQuestion $question, array $payload): AttemptAnswer
    {
        if (! $attempt->isInProgress()) {
            throw new RuntimeException('Attempt is not in progress.');
        }

        if ($attempt->isDeadlinePassed()) {
            throw new RuntimeException('Deadline has passed.');
        }

        if ($question->exam_id !== $attempt->exam_id) {
            throw new RuntimeException('Question does not belong to this exam.');
        }

        $selectedOptionId = $payload['selected_option_id'] ?? null;
        if ($selectedOptionId !== null) {
            $ownsOption = QuestionOption::where('id', $selectedOptionId)
                ->where('exam_question_id', $question->id)
                ->exists();
            if (! $ownsOption) {
                throw new RuntimeException('Option does not belong to this question.');
            }
        }

        return AttemptAnswer::updateOrCreate(
            [
                'exam_attempt_id' => $attempt->id,
                'exam_question_id' => $question->id,
            ],
            [
                'selected_option_id' => $selectedOptionId,
                'answer_text' => $payload['answer_text'] ?? null,
            ],
        );
    }

    /**
     * Final submit — run auto-grading on MCQ + true/false, leave short-answer
     * for staff. Writes the summed objective score immediately; status stays
     * 'scored' so the portal can display what's graded so far.
     */
    public function submit(ExamAttempt $attempt): ExamAttempt
    {
        if (! $attempt->isInProgress()) {
            throw new RuntimeException('Attempt is not in progress.');
        }

        return DB::transaction(function () use ($attempt) {
            $attempt->load(['exam.questions.options', 'answers']);

            $objectiveAwarded = 0;
            $hasManual = false;

            foreach ($attempt->exam->questions as $question) {
                /** @var ExamQuestion $question */
                $answer = $attempt->answers->firstWhere('exam_question_id', $question->id)
                    ?? AttemptAnswer::create([
                        'exam_attempt_id' => $attempt->id,
                        'exam_question_id' => $question->id,
                    ]);

                if ($question->isAutoGradable()) {
                    $awarded = $this->autoGrade($question, $answer);
                    $answer->marks_awarded = $awarded;
                    $answer->auto_graded = true;
                    $answer->save();
                    $objectiveAwarded += $awarded;
                } else {
                    $hasManual = true;
                }
            }

            $attempt->take_phase = $hasManual
                ? ExamAttempt::PHASE_SUBMITTED
                : ExamAttempt::PHASE_GRADED;
            $attempt->submitted_at = now();
            $attempt->score = $objectiveAwarded;
            $attempt->status = ExamAttempt::STATUS_SCORED;
            $attempt->scored_at = now();
            $attempt->save();

            $this->logger->log('exam.attempt.submitted', $attempt, [
                'exam_id' => $attempt->exam_id,
                'student_id' => $attempt->student_user_id,
                'auto_score' => $objectiveAwarded,
                'has_manual' => $hasManual,
            ]);

            return $attempt;
        });
    }

    /**
     * Staff may add manual marks for short-answer questions after auto-grade.
     * This keeps the existing score-entry flow compatible by updating both
     * per-answer marks AND the attempt's total score.
     */
    public function gradeManual(User $grader, ExamAttempt $attempt, array $marksByQuestionId): ExamAttempt
    {
        return DB::transaction(function () use ($grader, $attempt, $marksByQuestionId) {
            $total = 0;
            $attempt->load(['exam.questions', 'answers']);

            foreach ($attempt->exam->questions as $question) {
                $answer = $attempt->answers->firstWhere('exam_question_id', $question->id);
                if (! $answer) {
                    continue;
                }

                if (! $question->isAutoGradable() && array_key_exists($question->id, $marksByQuestionId)) {
                    $mark = max(0, min((int) $question->marks, (int) $marksByQuestionId[$question->id]));
                    $answer->marks_awarded = $mark;
                    $answer->auto_graded = false;
                    $answer->save();
                }

                $total += (int) ($answer->marks_awarded ?? 0);
            }

            $attempt->score = $total;
            $attempt->take_phase = ExamAttempt::PHASE_GRADED;
            $attempt->scored_by = $grader->id;
            $attempt->scored_at = now();
            $attempt->status = ExamAttempt::STATUS_SCORED;
            $attempt->save();

            $this->logger->log('exam.attempt.graded', $attempt, [
                'grader_id' => $grader->id,
                'total' => $total,
            ]);

            return $attempt;
        });
    }

    private function autoGrade(ExamQuestion $question, AttemptAnswer $answer): int
    {
        $full = (int) $question->marks;

        if ($question->type === ExamQuestion::TYPE_MCQ) {
            if ($answer->selected_option_id === null) {
                return 0;
            }
            $correct = $question->options->firstWhere('id', $answer->selected_option_id);

            return ($correct && $correct->is_correct) ? $full : 0;
        }

        if ($question->type === ExamQuestion::TYPE_TRUE_FALSE) {
            $submitted = strtolower((string) $answer->answer_text);
            $expected = strtolower((string) $question->answer_key);
            if ($submitted === '' || $expected === '') {
                return 0;
            }

            return $submitted === $expected ? $full : 0;
        }

        return 0;
    }
}
