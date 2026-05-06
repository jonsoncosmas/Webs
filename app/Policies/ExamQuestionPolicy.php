<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Role;
use App\Models\User;

class ExamQuestionPolicy
{
    /**
     * Manage the question bank of an exam. Mirrors who may update the exam
     * itself, so only creators (or higher-rank overriders) edit questions,
     * and only while the exam is editable.
     */
    public function manage(User $user, Exam $exam): bool
    {
        return $user->can('update', $exam);
    }

    /**
     * View the question bank (staff). Students don't hit this — they go
     * through the take-flow instead.
     */
    public function viewBank(User $user, Exam $exam): bool
    {
        if ($user->hasRole(Role::SYSTEM_ADMIN)) {
            return true;
        }

        if ($user->school_id !== $exam->school_id) {
            return false;
        }

        return ! $user->hasRole(Role::STUDENT, Role::PARENT_ROLE);
    }

    /**
     * Update an existing question — must be editable and manageable.
     */
    public function update(User $user, ExamQuestion $question): bool
    {
        return $this->manage($user, $question->exam);
    }

    public function delete(User $user, ExamQuestion $question): bool
    {
        return $this->manage($user, $question->exam);
    }
}
