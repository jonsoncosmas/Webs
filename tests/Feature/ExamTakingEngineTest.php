<?php

namespace Tests\Feature;

use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Exams\ExamTakingService;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamTakingEngineTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PackageSeeder::class);
        $this->school = School::create(['name' => 'Alpha', 'slug' => 'alpha', 'status' => 'active']);
        $this->otherSchool = School::create(['name' => 'Beta', 'slug' => 'beta', 'status' => 'active']);
    }

    private function user(string $roleSlug, ?School $school = null): User
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return User::create([
            'school_id' => ($school ?? $this->school)->id,
            'role_id' => $role->id,
            'first_name' => 'T',
            'last_name' => ucfirst($roleSlug).rand(1000, 9999),
            'username' => $roleSlug.'-'.uniqid(),
            'password' => Hash::make('x'),
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function systemAdmin(): User
    {
        $role = Role::where('slug', Role::SYSTEM_ADMIN)->firstOrFail();

        return User::create([
            'role_id' => $role->id,
            'first_name' => 'System',
            'last_name' => 'Admin'.rand(1000, 9999),
            'username' => 'sa-'.uniqid(),
            'password' => Hash::make('x'),
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function publishedExam(User $creator, int $total = 30, ?int $duration = 60): Exam
    {
        return Exam::create([
            'school_id' => $creator->school_id,
            'creator_id' => $creator->id,
            'creator_role_level' => $creator->role->level,
            'title' => 'Quiz '.uniqid(),
            'subject' => 'General Knowledge',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => $total,
            'duration_minutes' => $duration,
        ]);
    }

    private function mcq(Exam $exam, string $prompt, array $options, int $correctIndex, int $marks = 10, int $position = 1): ExamQuestion
    {
        $q = $exam->questions()->create([
            'position' => $position,
            'prompt' => $prompt,
            'type' => ExamQuestion::TYPE_MCQ,
            'marks' => $marks,
        ]);
        foreach ($options as $i => $text) {
            $q->options()->create([
                'position' => $i + 1,
                'text' => $text,
                'is_correct' => $i === $correctIndex,
            ]);
        }

        return $q->fresh('options');
    }

    private function trueFalse(Exam $exam, string $prompt, bool $correct, int $marks = 10, int $position = 2): ExamQuestion
    {
        return $exam->questions()->create([
            'position' => $position,
            'prompt' => $prompt,
            'type' => ExamQuestion::TYPE_TRUE_FALSE,
            'marks' => $marks,
            'answer_key' => $correct ? 'true' : 'false',
        ]);
    }

    private function shortAnswer(Exam $exam, string $prompt, int $marks = 10, int $position = 3): ExamQuestion
    {
        return $exam->questions()->create([
            'position' => $position,
            'prompt' => $prompt,
            'type' => ExamQuestion::TYPE_SHORT_ANSWER,
            'marks' => $marks,
        ]);
    }

    // -------------------------- Question bank policy --------------------------

    public function test_teacher_creator_can_add_questions_while_exam_editable(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Draft', 'subject' => 'Math', 'status' => Exam::STATUS_DRAFT,
        ]);

        $this->actingAs($teacher)
            ->post(route('exams.questions.store', $exam), [
                'prompt' => 'What is 2+2?',
                'type' => 'mcq',
                'marks' => 5,
                'options' => [
                    ['text' => '3', 'is_correct' => null],
                    ['text' => '4', 'is_correct' => '1'],
                    ['text' => '5', 'is_correct' => null],
                ],
            ])
            ->assertRedirect(route('exams.questions.index', $exam));

        $this->assertDatabaseCount('exam_questions', 1);
        $this->assertDatabaseHas('question_options', ['text' => '4', 'is_correct' => true]);
    }

    public function test_student_cannot_view_question_bank(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher);
        $student = $this->user(Role::STUDENT);

        $this->actingAs($student)
            ->get(route('exams.questions.index', $exam))
            ->assertForbidden();
    }

    public function test_teacher_cannot_add_questions_to_published_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher);

        $this->actingAs($teacher)
            ->post(route('exams.questions.store', $exam), [
                'prompt' => 'Too late?', 'type' => 'short_answer', 'marks' => 5,
            ])
            ->assertForbidden();
    }

    public function test_mcq_requires_at_least_one_correct_option(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Draft', 'subject' => 'Math', 'status' => Exam::STATUS_DRAFT,
        ]);

        $this->actingAs($teacher)
            ->from(route('exams.questions.index', $exam))
            ->post(route('exams.questions.store', $exam), [
                'prompt' => 'Pick something',
                'type' => 'mcq',
                'marks' => 5,
                'options' => [
                    ['text' => 'a'],
                    ['text' => 'b'],
                ],
            ])
            ->assertSessionHasErrors('options');

        $this->assertDatabaseCount('exam_questions', 0);
    }

    public function test_true_false_requires_explicit_key(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Draft', 'subject' => 'Sci', 'status' => Exam::STATUS_DRAFT,
        ]);

        $this->actingAs($teacher)
            ->from(route('exams.questions.index', $exam))
            ->post(route('exams.questions.store', $exam), [
                'prompt' => 'Earth is flat.',
                'type' => 'true_false',
                'marks' => 5,
                'answer_key' => 'maybe',
            ])
            ->assertSessionHasErrors('answer_key');
    }

    // -------------------------- Take flow --------------------------

    public function test_student_can_start_attempt_on_published_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher);
        $this->mcq($exam, '2+2?', ['3', '4', '5'], 1);
        $student = $this->user(Role::STUDENT);

        $this->actingAs($student)
            ->get(route('portal.exams.take', $exam))
            ->assertOk()
            ->assertSee('2+2?');

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $exam->id,
            'student_user_id' => $student->id,
            'take_phase' => ExamAttempt::PHASE_IN_PROGRESS,
        ]);
    }

    public function test_non_student_cannot_take_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher);

        $this->actingAs($teacher)
            ->get(route('portal.exams.take', $exam))
            ->assertForbidden();
    }

    public function test_student_from_other_school_cannot_take_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher);
        $otherStudent = $this->user(Role::STUDENT, $this->otherSchool);

        $this->actingAs($otherStudent)
            ->get(route('portal.exams.take', $exam))
            ->assertForbidden();
    }

    public function test_student_cannot_take_unpublished_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Draft', 'subject' => 'x', 'status' => Exam::STATUS_DRAFT,
            'total_marks' => 10,
        ]);
        $student = $this->user(Role::STUDENT);

        $this->actingAs($student)
            ->get(route('portal.exams.take', $exam))
            ->assertForbidden();
    }

    public function test_submit_auto_grades_mcq_and_true_false(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher, total: 30);
        $q1 = $this->mcq($exam, '2+2?', ['3', '4', '5'], 1, marks: 10, position: 1);
        $q2 = $this->trueFalse($exam, 'Sky is blue.', correct: true, marks: 10, position: 2);
        $q3 = $this->shortAnswer($exam, 'Explain gravity.', marks: 10, position: 3);

        $student = $this->user(Role::STUDENT);
        $this->actingAs($student)->get(route('portal.exams.take', $exam));

        $correctOption = $q1->options->firstWhere('is_correct', true);

        $this->actingAs($student)
            ->post(route('portal.exams.submit', $exam), [
                'answers' => [
                    $q1->id => ['selected_option_id' => $correctOption->id],
                    $q2->id => ['answer_text' => 'true'],
                    $q3->id => ['answer_text' => 'Pull of massive objects.'],
                ],
            ])
            ->assertRedirect();

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_user_id', $student->id)->firstOrFail();

        $this->assertSame(20, (int) $attempt->score, 'MCQ + T/F auto-graded');
        $this->assertSame(ExamAttempt::PHASE_SUBMITTED, $attempt->take_phase, 'manual Q still pending => submitted, not graded');
        $this->assertNotNull($attempt->submitted_at);

        $shortAnswerRow = AttemptAnswer::where('exam_attempt_id', $attempt->id)
            ->where('exam_question_id', $q3->id)->firstOrFail();
        $this->assertSame(false, (bool) $shortAnswerRow->auto_graded);
        $this->assertNull($shortAnswerRow->marks_awarded);
    }

    public function test_submit_without_manual_questions_reaches_graded_phase(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher, total: 20);
        $q1 = $this->mcq($exam, 'A?', ['x', 'y'], 0, marks: 10, position: 1);
        $q2 = $this->trueFalse($exam, 'B?', correct: false, marks: 10, position: 2);
        $student = $this->user(Role::STUDENT);
        $this->actingAs($student)->get(route('portal.exams.take', $exam));

        $correctOpt = $q1->options->firstWhere('is_correct', true);
        $this->actingAs($student)
            ->post(route('portal.exams.submit', $exam), [
                'answers' => [
                    $q1->id => ['selected_option_id' => $correctOpt->id],
                    $q2->id => ['answer_text' => 'false'],
                ],
            ])
            ->assertRedirect();

        $attempt = ExamAttempt::where('exam_id', $exam->id)->where('student_user_id', $student->id)->firstOrFail();
        $this->assertSame(ExamAttempt::PHASE_GRADED, $attempt->take_phase);
        $this->assertSame(20, (int) $attempt->score);
    }

    public function test_wrong_mcq_gets_zero(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher, total: 10);
        $q = $this->mcq($exam, 'A?', ['wrong', 'right'], 1, marks: 10);
        $student = $this->user(Role::STUDENT);
        $this->actingAs($student)->get(route('portal.exams.take', $exam));

        $wrong = $q->options->firstWhere('is_correct', false);
        $this->actingAs($student)
            ->post(route('portal.exams.submit', $exam), [
                'answers' => [$q->id => ['selected_option_id' => $wrong->id]],
            ])->assertRedirect();

        $attempt = ExamAttempt::first();
        $this->assertSame(0, (int) $attempt->score);
    }

    public function test_cannot_resubmit_attempt(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher, total: 10);
        $q = $this->mcq($exam, 'A?', ['a', 'b'], 0, marks: 10);
        $student = $this->user(Role::STUDENT);

        $this->actingAs($student)->get(route('portal.exams.take', $exam));
        $correct = $q->options->firstWhere('is_correct', true);
        $this->actingAs($student)->post(route('portal.exams.submit', $exam), [
            'answers' => [$q->id => ['selected_option_id' => $correct->id]],
        ]);

        // Second GET should redirect to the already-submitted attempt.
        $this->actingAs($student)
            ->get(route('portal.exams.take', $exam))
            ->assertRedirect();

        // Second POST should also redirect (no duplicate grading).
        $this->actingAs($student)
            ->post(route('portal.exams.submit', $exam), [
                'answers' => [$q->id => ['selected_option_id' => $correct->id]],
            ])->assertRedirect();

        $this->assertSame(1, ExamAttempt::count());
    }

    public function test_cross_school_student_cannot_submit(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher);
        $this->mcq($exam, 'A?', ['a', 'b'], 0);
        $otherStudent = $this->user(Role::STUDENT, $this->otherSchool);

        $this->actingAs($otherStudent)
            ->post(route('portal.exams.submit', $exam), [])
            ->assertForbidden();

        $this->assertSame(0, ExamAttempt::count());
    }

    public function test_deadline_enforced_on_answer_recording(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher, total: 10, duration: 1);
        $q = $this->mcq($exam, 'A?', ['a', 'b'], 0);
        $student = $this->user(Role::STUDENT);

        $this->actingAs($student)->get(route('portal.exams.take', $exam));
        $attempt = ExamAttempt::first();
        $attempt->update(['deadline_at' => now()->subMinute()]);

        $this->expectException(\RuntimeException::class);
        app(ExamTakingService::class)->recordAnswer($attempt->fresh(), $q, ['selected_option_id' => $q->options->first()->id]);
    }

    public function test_foreign_option_on_answer_rejected_by_service(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher);
        $q1 = $this->mcq($exam, 'A?', ['a', 'b'], 0);

        $otherExam = $this->publishedExam($teacher);
        $otherQ = $this->mcq($otherExam, 'B?', ['x', 'y'], 0);
        $foreignOption = $otherQ->options->first();

        $student = $this->user(Role::STUDENT);
        $this->actingAs($student)->get(route('portal.exams.take', $exam));
        $attempt = ExamAttempt::where('exam_id', $exam->id)->firstOrFail();

        $this->expectException(\RuntimeException::class);
        app(ExamTakingService::class)->recordAnswer($attempt, $q1, ['selected_option_id' => $foreignOption->id]);
    }

    public function test_question_not_belonging_to_exam_ignored_in_controller(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher, total: 10);
        $q = $this->mcq($exam, 'A?', ['a', 'b'], 0, marks: 10);

        $otherExam = $this->publishedExam($teacher, total: 10);
        $foreignQ = $this->mcq($otherExam, 'X?', ['x', 'y'], 0, marks: 10);

        $student = $this->user(Role::STUDENT);
        $this->actingAs($student)->get(route('portal.exams.take', $exam));

        $correct = $q->options->firstWhere('is_correct', true);

        $this->actingAs($student)
            ->post(route('portal.exams.submit', $exam), [
                'answers' => [
                    $q->id => ['selected_option_id' => $correct->id],
                    $foreignQ->id => ['selected_option_id' => $foreignQ->options->first()->id],
                ],
            ])->assertRedirect();

        $this->assertSame(10, (int) ExamAttempt::first()->score);
        // Only one answer row should exist for this attempt.
        $this->assertSame(1, AttemptAnswer::count());
    }

    public function test_grade_manual_totals_auto_and_manual_marks(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher, total: 30);
        $q1 = $this->mcq($exam, 'A?', ['wrong', 'right'], 1, marks: 10, position: 1);
        $q2 = $this->trueFalse($exam, 'B?', correct: true, marks: 10, position: 2);
        $q3 = $this->shortAnswer($exam, 'Essay', marks: 10, position: 3);
        $student = $this->user(Role::STUDENT);
        $this->actingAs($student)->get(route('portal.exams.take', $exam));
        $correct = $q1->options->firstWhere('is_correct', true);
        $this->actingAs($student)->post(route('portal.exams.submit', $exam), [
            'answers' => [
                $q1->id => ['selected_option_id' => $correct->id],
                $q2->id => ['answer_text' => 'false'], // wrong
                $q3->id => ['answer_text' => 'Words'],
            ],
        ]);
        $attempt = ExamAttempt::first();

        app(ExamTakingService::class)->gradeManual($teacher, $attempt, [
            $q3->id => 7,
        ]);

        $attempt->refresh();
        $this->assertSame(17, (int) $attempt->score, 'MCQ(10) + T/F(0) + essay(7) = 17');
        $this->assertSame(ExamAttempt::PHASE_GRADED, $attempt->take_phase);
        $this->assertSame($teacher->id, (int) $attempt->scored_by);
    }

    public function test_grade_manual_clamps_above_full_marks(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->publishedExam($teacher, total: 10);
        $q = $this->shortAnswer($exam, 'Essay', marks: 10);
        $student = $this->user(Role::STUDENT);
        $this->actingAs($student)->get(route('portal.exams.take', $exam));
        $this->actingAs($student)->post(route('portal.exams.submit', $exam), [
            'answers' => [$q->id => ['answer_text' => 'text']],
        ]);
        $attempt = ExamAttempt::first();

        app(ExamTakingService::class)->gradeManual($teacher, $attempt, [
            $q->id => 9999,
        ]);

        $this->assertSame(10, (int) $attempt->fresh()->score, 'manual mark clamps to question max');
    }
}
