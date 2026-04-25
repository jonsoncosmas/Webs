<?php

namespace App\Services\Portal;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ResultReviewRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Collection;

class PortalService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /**
     * Published exams that are relevant for a student's school (and form_level
     * when set). Exams without a form_level are treated as school-wide.
     */
    public function availableExamsFor(User $student): Collection
    {
        return Exam::query()
            ->where('school_id', $student->school_id)
            ->where('status', Exam::STATUS_PUBLISHED)
            ->orderByDesc('scheduled_at')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * All scored attempts for a student, most recent first.
     */
    public function attemptsFor(User $student): Collection
    {
        return ExamAttempt::query()
            ->where('student_user_id', $student->id)
            ->with(['exam', 'scorer', 'reviewRequests'])
            ->orderByDesc('scored_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function recordScore(User $actor, User $student, Exam $exam, int $score, int $totalMarks, ?string $grade = null, ?string $notes = null): ExamAttempt
    {
        if ($score < 0 || $totalMarks <= 0 || $score > $totalMarks) {
            throw new \InvalidArgumentException('Score must be between 0 and total_marks.');
        }

        $attempt = ExamAttempt::updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_user_id' => $student->id,
            ],
            [
                'school_id' => $student->school_id,
                'score' => $score,
                'total_marks' => $totalMarks,
                'grade' => $grade,
                'status' => ExamAttempt::STATUS_SCORED,
                'scored_by' => $actor->id,
                'scored_at' => now(),
                'notes' => $notes,
            ],
        );

        $this->logger->log('portal.attempt.scored', $attempt, [
            'student_id' => $student->id,
            'exam_id' => $exam->id,
        ]);

        return $attempt;
    }

    public function submitReviewRequest(User $submitter, ExamAttempt $attempt, string $reason): ResultReviewRequest
    {
        $request = ResultReviewRequest::create([
            'attempt_id' => $attempt->id,
            'student_user_id' => $attempt->student_user_id,
            'school_id' => $attempt->school_id,
            'submitted_by' => $submitter->id,
            'reason' => $reason,
            'status' => ResultReviewRequest::STATUS_PENDING,
        ]);

        $this->logger->log('portal.review.submitted', $request, [
            'attempt_id' => $attempt->id,
            'student_id' => $attempt->student_user_id,
        ]);

        return $request;
    }

    public function acknowledgeReview(User $actor, ResultReviewRequest $request): ResultReviewRequest
    {
        $request->update([
            'status' => ResultReviewRequest::STATUS_ACKNOWLEDGED,
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ]);

        $this->logger->log('portal.review.acknowledged', $request);

        return $request;
    }

    public function resolveReview(User $actor, ResultReviewRequest $request, string $feedback): ResultReviewRequest
    {
        $request->update([
            'status' => ResultReviewRequest::STATUS_RESOLVED,
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'feedback' => $feedback,
        ]);

        $this->logger->log('portal.review.resolved', $request);

        return $request;
    }

    public function rejectReview(User $actor, ResultReviewRequest $request, string $feedback): ResultReviewRequest
    {
        $request->update([
            'status' => ResultReviewRequest::STATUS_REJECTED,
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'feedback' => $feedback,
        ]);

        $this->logger->log('portal.review.rejected', $request);

        return $request;
    }

    /**
     * Resolve which student a logged-in user should currently be viewing.
     * - Students: themselves.
     * - Parents: their primary child, or first linked child.
     */
    public function defaultStudentFor(User $user, ?int $requestedStudentId = null): ?User
    {
        if ($user->hasRole(Role::STUDENT)) {
            return $user;
        }

        if ($user->hasRole(Role::PARENT_ROLE)) {
            $children = $user->children()->with('role')->get();
            if ($requestedStudentId) {
                $match = $children->firstWhere('id', $requestedStudentId);
                if ($match) {
                    return $match;
                }
            }

            return $children->firstWhere('pivot.is_primary', true) ?? $children->first();
        }

        return null;
    }
}
