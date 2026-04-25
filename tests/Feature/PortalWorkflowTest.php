<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ParentStudentLink;
use App\Models\ResultReviewRequest;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Portal\PortalService;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalWorkflowTest extends TestCase
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

    private function linkParent(User $parent, User $student, bool $primary = true): void
    {
        ParentStudentLink::create([
            'parent_user_id' => $parent->id,
            'student_user_id' => $student->id,
            'school_id' => $student->school_id,
            'relationship' => 'father',
            'is_primary' => $primary,
        ]);
    }

    private function scoredAttempt(User $student, User $scorer, int $score = 70, int $total = 100): ExamAttempt
    {
        $exam = Exam::create([
            'school_id' => $student->school_id,
            'creator_id' => $scorer->id,
            'creator_role_level' => $scorer->role->level,
            'title' => 'Math Mid-Term '.uniqid(),
            'subject' => 'Mathematics',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => $total,
        ]);

        return app(PortalService::class)->recordScore($scorer, $student, $exam, $score, $total, 'B', 'Good effort.');
    }

    // ---------- Dashboard access ----------

    public function test_dashboard_redirects_student_to_portal(): void
    {
        $student = $this->user(Role::STUDENT);
        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_dashboard_redirects_parent_to_portal(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $this->actingAs($parent)
            ->get(route('dashboard'))
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_teacher_cannot_access_portal(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $this->actingAs($teacher)
            ->get(route('portal.dashboard'))
            ->assertForbidden();
    }

    public function test_parent_without_child_sees_no_child_page(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $this->actingAs($parent)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('No linked student');
    }

    // ---------- Parent / child scoping ----------

    public function test_parent_sees_their_linked_child_by_default(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $student = $this->user(Role::STUDENT);
        $this->linkParent($parent, $student);

        $this->actingAs($parent)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee($student->fullName());
    }

    public function test_parent_cannot_switch_to_unlinked_student(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $mine = $this->user(Role::STUDENT);
        $other = $this->user(Role::STUDENT);
        $this->linkParent($parent, $mine);

        $this->actingAs($parent)
            ->get(route('portal.dashboard', ['student_id' => $other->id]))
            ->assertOk()
            ->assertSee($mine->fullName())
            ->assertDontSee($other->fullName());
    }

    // ---------- Attempt policies ----------

    public function test_student_can_view_own_attempt(): void
    {
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);

        $this->assertTrue($student->can('view', $attempt));
    }

    public function test_student_cannot_view_other_students_attempt(): void
    {
        $other = $this->user(Role::STUDENT);
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);

        $this->assertFalse($other->can('view', $attempt));
    }

    public function test_linked_parent_can_view_child_attempt(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);
        $this->linkParent($parent, $student);

        $this->assertTrue($parent->can('view', $attempt));
    }

    public function test_unlinked_parent_cannot_view_attempt(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);

        $this->assertFalse($parent->can('view', $attempt));
    }

    public function test_teacher_can_score_attempts(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $this->assertTrue($teacher->can('viewAny', ExamAttempt::class));
    }

    public function test_hr_cannot_score_attempts(): void
    {
        $hr = $this->user(Role::HR);
        $this->assertFalse($hr->can('viewAny', ExamAttempt::class));
    }

    public function test_cross_school_view_blocked(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $attempt = $this->scoredAttempt($student, $teacher);

        $foreignTeacher = $this->user(Role::TEACHER, $this->otherSchool);
        $this->assertFalse($foreignTeacher->can('view', $attempt));
    }

    // ---------- Review request lifecycle ----------

    public function test_student_can_submit_review_request(): void
    {
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);

        $this->actingAs($student)
            ->post(route('portal.result.review', $attempt), ['reason' => 'I think question 3 was miscounted somewhere.'])
            ->assertRedirect(route('portal.result.show', $attempt));

        $this->assertDatabaseHas('result_review_requests', [
            'attempt_id' => $attempt->id,
            'submitted_by' => $student->id,
            'status' => 'pending',
        ]);
    }

    public function test_review_reason_must_be_at_least_10_chars(): void
    {
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);

        $this->actingAs($student)
            ->post(route('portal.result.review', $attempt), ['reason' => 'short'])
            ->assertSessionHasErrors('reason');
    }

    public function test_linked_parent_can_submit_review_request(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);
        $this->linkParent($parent, $student);

        $this->actingAs($parent)
            ->post(route('portal.result.review', $attempt), ['reason' => 'I would like this re-checked please.'])
            ->assertRedirect();

        $this->assertDatabaseHas('result_review_requests', [
            'attempt_id' => $attempt->id,
            'submitted_by' => $parent->id,
        ]);
    }

    public function test_unlinked_parent_cannot_submit_review_request(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);

        $this->actingAs($parent)
            ->post(route('portal.result.review', $attempt), ['reason' => 'This is not my child.'])
            ->assertForbidden();
    }

    public function test_teacher_cannot_submit_review_request(): void
    {
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);

        $this->actingAs($teacher)
            ->post(route('portal.result.review', $attempt), ['reason' => 'Pretending to be student.'])
            ->assertForbidden();
    }

    // ---------- Review inbox policies ----------

    public function test_academic_head_can_see_inbox(): void
    {
        $ah = $this->user(Role::ACADEMIC_HEAD);
        $this->actingAs($ah)
            ->get(route('academic.reviews.index'))
            ->assertOk();
    }

    public function test_teacher_cannot_see_inbox(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $this->actingAs($teacher)
            ->get(route('academic.reviews.index'))
            ->assertForbidden();
    }

    public function test_academic_head_can_resolve_review(): void
    {
        $ah = $this->user(Role::ACADEMIC_HEAD);
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);
        $review = app(PortalService::class)->submitReviewRequest($student, $attempt, 'Please re-check question 3.');

        $this->actingAs($ah)
            ->post(route('academic.reviews.resolve', $review), ['feedback' => 'Re-marked, no change.'])
            ->assertRedirect(route('academic.reviews.show', $review));

        $fresh = $review->fresh();
        $this->assertSame(ResultReviewRequest::STATUS_RESOLVED, $fresh->status);
        $this->assertSame($ah->id, $fresh->decided_by);
        $this->assertSame('Re-marked, no change.', $fresh->feedback);
    }

    public function test_teacher_cannot_resolve_review(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $attempt = $this->scoredAttempt($student, $teacher);
        $review = app(PortalService::class)->submitReviewRequest($student, $attempt, 'Please re-check question 3.');

        $this->actingAs($teacher)
            ->post(route('academic.reviews.resolve', $review), ['feedback' => 'nope'])
            ->assertForbidden();
    }

    public function test_cross_school_review_access_blocked(): void
    {
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);
        $review = app(PortalService::class)->submitReviewRequest($student, $attempt, 'Reason text here.');

        $foreignAh = $this->user(Role::ACADEMIC_HEAD, $this->otherSchool);
        $this->actingAs($foreignAh)
            ->get(route('academic.reviews.show', $review))
            ->assertForbidden();
    }

    public function test_cannot_resolve_already_resolved_review(): void
    {
        $ah = $this->user(Role::ACADEMIC_HEAD);
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);
        $review = app(PortalService::class)->submitReviewRequest($student, $attempt, 'Please re-check question 3.');
        app(PortalService::class)->resolveReview($ah, $review, 'Done.');

        $this->actingAs($ah)
            ->post(route('academic.reviews.resolve', $review->fresh()), ['feedback' => 'second try'])
            ->assertForbidden();
    }

    // ---------- Score entry ----------

    public function test_score_record_rejects_invalid_score(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $exam = Exam::create([
            'school_id' => $student->school_id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Bad Score Exam',
            'subject' => 'Math',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => 100,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        app(PortalService::class)->recordScore($teacher, $student, $exam, 150, 100);
    }

    public function test_teacher_can_post_score_via_http(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $exam = Exam::create([
            'school_id' => $student->school_id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'HTTP Score Exam',
            'subject' => 'Math',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => 100,
        ]);

        $this->actingAs($teacher)
            ->post(route('portal.scores.store', $exam), [
                'student_user_id' => $student->id,
                'score' => 88,
                'total_marks' => 100,
                'grade' => 'A',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $exam->id,
            'student_user_id' => $student->id,
            'score' => 88,
        ]);
    }

    public function test_teacher_cannot_score_cross_school_student(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $foreignStudent = $this->user(Role::STUDENT, $this->otherSchool);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Score Exam',
            'subject' => 'Math',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => 100,
        ]);

        $this->actingAs($teacher)
            ->post(route('portal.scores.store', $exam), [
                'student_user_id' => $foreignStudent->id,
                'score' => 80,
                'total_marks' => 100,
            ])
            ->assertForbidden();
    }

    public function test_score_entry_rejects_non_student_target(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $otherTeacher = $this->user(Role::TEACHER);
        $exam = Exam::create([
            'school_id' => $teacher->school_id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Role Check Exam',
            'subject' => 'Math',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => 100,
        ]);

        $this->actingAs($teacher)
            ->post(route('portal.scores.store', $exam), [
                'student_user_id' => $otherTeacher->id,
                'score' => 70,
                'total_marks' => 100,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('exam_attempts', [
            'exam_id' => $exam->id,
            'student_user_id' => $otherTeacher->id,
        ]);
    }

    public function test_parent_child_context_preserved_on_back_links(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $mine = $this->user(Role::STUDENT);
        $other = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $this->linkParent($parent, $mine, false);
        $this->linkParent($parent, $other, true);

        $attempt = $this->scoredAttempt($mine, $teacher);

        // Dashboard for non-primary child
        $response = $this->actingAs($parent)
            ->get(route('portal.dashboard', ['student_id' => $mine->id]));
        $response->assertOk();
        $html = $response->getContent();
        $expected = route('portal.results', ['student_id' => $mine->id]);
        $this->assertStringContainsString((string) $expected, (string) $html);
        // Result card deep-link should carry student_id too.
        $showLink = route('portal.result.show', ['attempt' => $attempt->id, 'student_id' => $mine->id]);
        $this->assertStringContainsString((string) $showLink, (string) $html);
    }

    public function test_result_show_back_link_uses_attempt_student(): void
    {
        $parent = $this->user(Role::PARENT_ROLE);
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $this->linkParent($parent, $student);
        $attempt = $this->scoredAttempt($student, $teacher);

        $html = $this->actingAs($parent)
            ->get(route('portal.result.show', $attempt))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            (string) route('portal.results', ['student_id' => $student->id]),
            (string) $html,
        );
    }

    public function test_teacher_cannot_score_own_student_on_foreign_school_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $foreignExam = Exam::create([
            'school_id' => $this->otherSchool->id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Foreign Exam',
            'subject' => 'Math',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => 100,
        ]);

        $this->actingAs($teacher)
            ->post(route('portal.scores.store', $foreignExam), [
                'student_user_id' => $student->id,
                'score' => 50,
                'total_marks' => 100,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('exam_attempts', [
            'exam_id' => $foreignExam->id,
            'student_user_id' => $student->id,
        ]);
    }

    public function test_score_entry_rejects_score_exceeding_total_marks(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $exam = Exam::create([
            'school_id' => $student->school_id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Overshoot Exam',
            'subject' => 'Math',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => 100,
        ]);

        $this->actingAs($teacher)
            ->post(route('portal.scores.store', $exam), [
                'student_user_id' => $student->id,
                'score' => 150,
                'total_marks' => 100,
            ])
            ->assertSessionHasErrors('score');

        $this->assertDatabaseMissing('exam_attempts', [
            'exam_id' => $exam->id,
            'student_user_id' => $student->id,
        ]);
    }

    public function test_percentage_reports_zero_for_zero_score(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $attempt = $this->scoredAttempt($student, $teacher, 0, 100);

        $this->assertSame(0.0, $attempt->percentage());
    }

    // ---------- System admin bypass ----------

    public function test_system_admin_cannot_decide_closed_review(): void
    {
        $sa = $this->systemAdmin();
        $ah = $this->user(Role::ACADEMIC_HEAD);
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);
        $review = app(PortalService::class)->submitReviewRequest($student, $attempt, 'Please re-check question 3.');
        app(PortalService::class)->resolveReview($ah, $review, 'Done.');

        $this->assertFalse($sa->can('decide', $review->fresh()));
    }

    public function test_system_admin_can_view_any_attempt_and_review(): void
    {
        $sa = $this->systemAdmin();
        $student = $this->user(Role::STUDENT);
        $teacher = $this->user(Role::TEACHER);
        $attempt = $this->scoredAttempt($student, $teacher);
        $review = app(PortalService::class)->submitReviewRequest($student, $attempt, 'Reason text.');

        $this->assertTrue($sa->can('view', $attempt));
        $this->assertTrue($sa->can('viewAny', ResultReviewRequest::class));
        $this->assertTrue($sa->can('view', $review));
    }
}
