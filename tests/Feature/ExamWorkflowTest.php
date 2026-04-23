<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Exams\ExamService;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PackageSeeder::class);
        $this->school = School::create([
            'name' => 'Test School',
            'slug' => 'test-school',
            'status' => 'active',
        ]);
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

    private function service(): ExamService
    {
        return app(ExamService::class);
    }

    private function draft(User $creator, array $overrides = []): Exam
    {
        return $this->service()->create($creator, array_merge([
            'title' => 'Midterm',
            'subject' => 'Math',
            'form_level' => 'Form 3',
            'duration_minutes' => 60,
            'total_marks' => 100,
        ], $overrides));
    }

    public function test_teacher_can_create_a_draft_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->draft($teacher);

        $this->assertSame(Exam::STATUS_DRAFT, $exam->status);
        $this->assertNull($exam->locked_to_id);
        $this->assertSame($teacher->id, $exam->creator_id);
        $this->assertDatabaseHas('exam_events', ['exam_id' => $exam->id, 'action' => 'created']);
    }

    public function test_director_exam_is_auto_approved_and_locked(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $exam = $this->draft($director);

        $this->assertSame(Exam::STATUS_APPROVED, $exam->status);
        $this->assertSame($director->id, $exam->locked_to_id);
        $this->assertSame($director->id, $exam->approver_id);
    }

    public function test_student_cannot_create_exam(): void
    {
        $student = $this->user(Role::STUDENT);
        $this->assertFalse($student->can('create', Exam::class));
    }

    public function test_teacher_cannot_approve_their_own_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->draft($teacher);
        $this->service()->submit($teacher, $exam);

        $this->assertFalse($teacher->can('approve', $exam->fresh()));
    }

    public function test_academic_head_can_approve_teacher_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $head = $this->user(Role::ACADEMIC_HEAD);

        $exam = $this->draft($teacher);
        $this->service()->submit($teacher, $exam);

        $this->assertTrue($head->can('approve', $exam->fresh()));
        $approved = $this->service()->approve($head, $exam->fresh(), 'LGTM');
        $this->assertSame(Exam::STATUS_APPROVED, $approved->status);
        $this->assertSame($head->id, $approved->approver_id);
    }

    public function test_same_level_cannot_approve_each_other(): void
    {
        $hrA = $this->user(Role::HR);
        $itB = $this->user(Role::IT); // same level as HR (60)

        // IT can create, HR cannot — create with IT, then check HR can't approve.
        $exam = $this->draft($itB);
        $this->service()->submit($itB, $exam);

        $this->assertFalse($hrA->can('approve', $exam->fresh()));
    }

    public function test_director_can_override_teacher_exam(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $director = $this->user(Role::DIRECTOR);
        $exam = $this->draft($teacher);

        $this->assertTrue($director->can('override', $exam));
        $overridden = $this->service()->override($director, $exam);

        $this->assertSame($director->id, $overridden->creator_id);
        $this->assertSame($director->id, $overridden->locked_to_id); // now locked to director
        $this->assertSame(Exam::STATUS_DRAFT, $overridden->status);
    }

    public function test_teacher_cannot_override_academic_head_exam(): void
    {
        $head = $this->user(Role::ACADEMIC_HEAD);
        $teacher = $this->user(Role::TEACHER);
        $exam = $this->draft($head);

        $this->assertFalse($teacher->can('override', $exam));
    }

    public function test_school_admin_cannot_override_director_locked_exam(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $schoolAdmin = $this->user(Role::SCHOOL_ADMIN);
        $exam = $this->draft($director);

        $this->assertTrue($exam->isLocked());
        $this->assertFalse($schoolAdmin->can('override', $exam));
    }

    public function test_director_can_override_their_own_locked_exam(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $exam = $this->draft($director);

        // Need to put it out of published/archived — it's approved by default. Approved is overrideable.
        $this->assertTrue($director->can('override', $exam));
    }

    public function test_published_exam_cannot_be_overridden(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $head = $this->user(Role::ACADEMIC_HEAD);
        $director = $this->user(Role::DIRECTOR);

        $exam = $this->draft($teacher);
        $this->service()->submit($teacher, $exam);
        $this->service()->approve($head, $exam->fresh());
        $this->service()->publish($head, $exam->fresh());

        $this->assertSame(Exam::STATUS_PUBLISHED, $exam->fresh()->status);
        $this->assertFalse($director->can('override', $exam->fresh()));
    }

    public function test_user_from_other_school_cannot_view_or_override(): void
    {
        $otherSchool = School::create(['name' => 'Other', 'slug' => 'other', 'status' => 'active']);
        $director = $this->user(Role::DIRECTOR);
        $outsideDirector = $this->user(Role::DIRECTOR, $otherSchool);
        $exam = $this->draft($director);

        $this->assertFalse($outsideDirector->can('view', $exam));
        $this->assertFalse($outsideDirector->can('override', $exam));
    }

    public function test_system_admin_can_do_anything(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $sys = User::create([
            'school_id' => null,
            'role_id' => Role::where('slug', Role::SYSTEM_ADMIN)->firstOrFail()->id,
            'first_name' => 'S',
            'last_name' => 'A',
            'username' => 'sys-'.uniqid(),
            'password' => Hash::make('x'),
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
        ]);

        $exam = $this->draft($teacher);
        $this->service()->submit($teacher, $exam);

        $this->assertTrue($sys->can('view', $exam->fresh()));
        $this->assertTrue($sys->can('approve', $exam->fresh()));
        $this->assertTrue($sys->can('override', $exam->fresh()));
    }

    public function test_rejected_exam_is_editable_again(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $head = $this->user(Role::ACADEMIC_HEAD);
        $exam = $this->draft($teacher);
        $this->service()->submit($teacher, $exam);
        $this->service()->reject($head, $exam->fresh(), 'Needs more coverage');

        $exam = $exam->fresh();
        $this->assertSame(Exam::STATUS_REJECTED, $exam->status);
        $this->assertTrue($exam->isEditable());
        $this->assertTrue($teacher->can('update', $exam));
    }

    public function test_full_happy_path_recorded_in_events(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $head = $this->user(Role::ACADEMIC_HEAD);

        $exam = $this->draft($teacher);
        $this->service()->submit($teacher, $exam);
        $this->service()->approve($head, $exam->fresh(), 'Good');
        $this->service()->publish($head, $exam->fresh());

        $actions = $exam->fresh()->events()->pluck('action')->sort()->values()->all();
        sort($actions);
        $this->assertSame(['approved', 'created', 'published', 'submitted'], $actions);
    }
}
