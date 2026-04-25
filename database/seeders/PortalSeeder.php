<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ParentStudentLink;
use App\Models\ResultReviewRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class PortalSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::whereHas('role', fn ($q) => $q->where('slug', Role::STUDENT))
            ->where('last_name', 'Kitomari')->first();
        $parent = User::whereHas('role', fn ($q) => $q->where('slug', Role::PARENT_ROLE))
            ->where('last_name', 'Kitomari')->first();
        $teacher = User::whereHas('role', fn ($q) => $q->where('slug', Role::TEACHER))->first();

        if (! $student || ! $parent || ! $teacher) {
            return;
        }

        ParentStudentLink::updateOrCreate(
            ['parent_user_id' => $parent->id, 'student_user_id' => $student->id],
            [
                'school_id' => $student->school_id,
                'relationship' => 'father',
                'is_primary' => true,
            ],
        );

        // Make sure there are a couple of published exams for the student.
        $exams = Exam::where('school_id', $student->school_id)
            ->where('status', Exam::STATUS_PUBLISHED)
            ->get();

        if ($exams->count() < 2) {
            $exams = collect([
                Exam::create([
                    'school_id' => $student->school_id,
                    'creator_id' => $teacher->id,
                    'creator_role_level' => $teacher->role->level,
                    'title' => 'Mathematics — Mid-Term',
                    'subject' => 'Mathematics',
                    'form_level' => 'Form 2',
                    'curriculum' => 'NECTA',
                    'duration_minutes' => 90,
                    'total_marks' => 100,
                    'status' => Exam::STATUS_PUBLISHED,
                ]),
                Exam::create([
                    'school_id' => $student->school_id,
                    'creator_id' => $teacher->id,
                    'creator_role_level' => $teacher->role->level,
                    'title' => 'English — Mid-Term',
                    'subject' => 'English',
                    'form_level' => 'Form 2',
                    'curriculum' => 'NECTA',
                    'duration_minutes' => 90,
                    'total_marks' => 100,
                    'status' => Exam::STATUS_PUBLISHED,
                ]),
            ]);
        }

        $attempts = [];
        foreach ($exams->take(2) as $i => $exam) {
            $attempts[] = ExamAttempt::updateOrCreate(
                ['exam_id' => $exam->id, 'student_user_id' => $student->id],
                [
                    'school_id' => $student->school_id,
                    'score' => $i === 0 ? 72 : 54,
                    'total_marks' => $exam->total_marks ?? 100,
                    'grade' => $i === 0 ? 'B' : 'C',
                    'status' => ExamAttempt::STATUS_SCORED,
                    'scored_by' => $teacher->id,
                    'scored_at' => now()->subDays(5 + $i * 3),
                    'notes' => $i === 0 ? 'Good effort, focus on word problems.' : 'Needs more practice with grammar.',
                ],
            );
        }

        if (isset($attempts[1])) {
            ResultReviewRequest::updateOrCreate(
                ['attempt_id' => $attempts[1]->id, 'student_user_id' => $student->id],
                [
                    'school_id' => $student->school_id,
                    'submitted_by' => $student->id,
                    'reason' => 'I think question 4 was marked incorrectly — my answer matches the textbook.',
                    'status' => ResultReviewRequest::STATUS_PENDING,
                ],
            );
        }
    }
}
