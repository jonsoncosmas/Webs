<?php

namespace App\Services\Exams;

use App\Models\Exam;
use App\Models\ExamEvent;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

/**
 * Centralises exam state transitions so all hierarchy rules + event audit
 * live in one place.
 */
class ExamService
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $creator, array $attributes): Exam
    {
        return DB::transaction(function () use ($creator, $attributes) {
            $isDirector = $creator->hasRole(Role::DIRECTOR);

            $exam = Exam::create(array_merge($attributes, [
                'school_id' => $creator->school_id,
                'creator_id' => $creator->id,
                'creator_role_level' => $creator->roleLevel(),
                'locked_to_id' => $isDirector ? $creator->id : null,
                // Director's exams skip the approval flow and are auto-approved.
                'status' => $isDirector ? Exam::STATUS_APPROVED : Exam::STATUS_DRAFT,
                'approver_id' => $isDirector ? $creator->id : null,
            ]));

            $this->recordEvent($exam, $creator, ExamEvent::CREATED, null, $exam->status);

            if ($isDirector) {
                $this->recordEvent($exam, $creator, ExamEvent::APPROVED, Exam::STATUS_DRAFT, Exam::STATUS_APPROVED, 'Auto-approved (Director-locked).');
            }

            $this->activity->log('exam.created', $exam, ['locked' => $isDirector]);

            return $exam->fresh();
        });
    }

    public function submit(User $actor, Exam $exam): Exam
    {
        return DB::transaction(function () use ($actor, $exam) {
            $from = $exam->status;
            $exam->update(['status' => Exam::STATUS_PENDING]);
            $this->recordEvent($exam, $actor, ExamEvent::SUBMITTED, $from, Exam::STATUS_PENDING);
            $this->activity->log('exam.submitted', $exam);

            return $exam->fresh();
        });
    }

    public function approve(User $approver, Exam $exam, ?string $comment = null): Exam
    {
        return DB::transaction(function () use ($approver, $exam, $comment) {
            $from = $exam->status;
            $exam->update([
                'status' => Exam::STATUS_APPROVED,
                'approver_id' => $approver->id,
            ]);
            $this->recordEvent($exam, $approver, ExamEvent::APPROVED, $from, Exam::STATUS_APPROVED, $comment);
            $this->activity->log('exam.approved', $exam);

            return $exam->fresh();
        });
    }

    public function reject(User $approver, Exam $exam, ?string $comment = null): Exam
    {
        return DB::transaction(function () use ($approver, $exam, $comment) {
            $from = $exam->status;
            $exam->update([
                'status' => Exam::STATUS_REJECTED,
                'approver_id' => $approver->id,
            ]);
            $this->recordEvent($exam, $approver, ExamEvent::REJECTED, $from, Exam::STATUS_REJECTED, $comment);
            $this->activity->log('exam.rejected', $exam);

            return $exam->fresh();
        });
    }

    /**
     * Higher-ranked user takes over an exam. The new owner replaces the creator,
     * the creator_role_level is updated to the new owner's level, and the exam
     * returns to draft so the new owner can edit.
     */
    public function override(User $newOwner, Exam $exam, ?string $comment = null): Exam
    {
        return DB::transaction(function () use ($newOwner, $exam, $comment) {
            $from = $exam->status;
            $previousCreatorId = $exam->creator_id;

            $exam->update([
                'creator_id' => $newOwner->id,
                'creator_role_level' => $newOwner->roleLevel(),
                'status' => Exam::STATUS_DRAFT,
                'approver_id' => null,
                // If the new owner is a Director, lock it to them too.
                'locked_to_id' => $newOwner->hasRole(Role::DIRECTOR) ? $newOwner->id : $exam->locked_to_id,
            ]);

            $this->recordEvent(
                $exam,
                $newOwner,
                ExamEvent::OVERRIDDEN,
                $from,
                Exam::STATUS_DRAFT,
                $comment,
                ['previous_creator_id' => $previousCreatorId],
            );

            $this->activity->log('exam.overridden', $exam, [
                'previous_creator_id' => $previousCreatorId,
            ]);

            return $exam->fresh();
        });
    }

    public function publish(User $actor, Exam $exam): Exam
    {
        return DB::transaction(function () use ($actor, $exam) {
            $from = $exam->status;
            $exam->update(['status' => Exam::STATUS_PUBLISHED]);
            $this->recordEvent($exam, $actor, ExamEvent::PUBLISHED, $from, Exam::STATUS_PUBLISHED);
            $this->activity->log('exam.published', $exam);

            return $exam->fresh();
        });
    }

    public function archive(User $actor, Exam $exam): Exam
    {
        return DB::transaction(function () use ($actor, $exam) {
            $from = $exam->status;
            $exam->update(['status' => Exam::STATUS_ARCHIVED]);
            $this->recordEvent($exam, $actor, ExamEvent::ARCHIVED, $from, Exam::STATUS_ARCHIVED);
            $this->activity->log('exam.archived', $exam);

            return $exam->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function recordEvent(
        Exam $exam,
        User $actor,
        string $action,
        ?string $from,
        ?string $to,
        ?string $comment = null,
        array $meta = [],
    ): void {
        ExamEvent::create([
            'exam_id' => $exam->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
