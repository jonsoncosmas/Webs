<?php

namespace Tests\Feature;

use App\Models\DisciplineIncident;
use App\Models\Exam;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Discipline\DisciplineService;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DisciplineWorkflowTest extends TestCase
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

    private function logIncident(User $reporter, User $subject, array $overrides = []): DisciplineIncident
    {
        return app(DisciplineService::class)->logIncident($reporter, $subject, array_merge([
            'category' => DisciplineIncident::CATEGORY_MINOR,
            'title' => 'Late to class',
            'description' => 'Arrived 20 min late',
            'occurred_on' => now()->toDateString(),
            'severity' => 2,
        ], $overrides));
    }

    // ---------- Policy: viewAny ----------

    public function test_discipline_head_can_browse_directory(): void
    {
        $actor = $this->user(Role::DISCIPLINE_HEAD);
        $this->assertTrue($actor->can('viewAny', DisciplineIncident::class));
    }

    public function test_teacher_cannot_browse_directory(): void
    {
        $actor = $this->user(Role::TEACHER);
        $this->assertFalse($actor->can('viewAny', DisciplineIncident::class));
    }

    public function test_director_and_deputy_and_school_admin_can_browse(): void
    {
        foreach ([Role::DIRECTOR, Role::DEPUTY_DIRECTOR, Role::SCHOOL_ADMIN, Role::ACADEMIC_HEAD, Role::HR] as $slug) {
            $actor = $this->user($slug);
            $this->assertTrue($actor->can('viewAny', DisciplineIncident::class), "role {$slug} should browse");
        }
    }

    // ---------- Policy: create ----------

    public function test_teacher_can_create(): void
    {
        $actor = $this->user(Role::TEACHER);
        $this->assertTrue($actor->can('create', DisciplineIncident::class));
    }

    public function test_student_cannot_create(): void
    {
        $actor = $this->user(Role::STUDENT);
        $this->assertFalse($actor->can('create', DisciplineIncident::class));
    }

    public function test_parent_cannot_create(): void
    {
        $actor = $this->user(Role::PARENT_ROLE);
        $this->assertFalse($actor->can('create', DisciplineIncident::class));
    }

    public function test_inactive_user_cannot_create(): void
    {
        $actor = $this->user(Role::TEACHER);
        $actor->update(['status' => User::STATUS_SUSPENDED]);
        $this->assertFalse($actor->can('create', DisciplineIncident::class));
    }

    // ---------- Policy: view ----------

    public function test_subject_can_view_own_incident(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $incident = $this->logIncident($reporter, $subject);

        $this->assertTrue($subject->can('view', $incident));
    }

    public function test_reporter_can_view_their_report(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $incident = $this->logIncident($reporter, $subject);

        $this->assertTrue($reporter->can('view', $incident));
    }

    public function test_unrelated_teacher_cannot_view_incident(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $bystander = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $incident = $this->logIncident($reporter, $subject);

        $this->assertFalse($bystander->can('view', $incident));
    }

    public function test_discipline_head_can_view_any_school_incident(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $head = $this->user(Role::DISCIPLINE_HEAD);

        $incident = $this->logIncident($reporter, $subject);
        $this->assertTrue($head->can('view', $incident));
    }

    public function test_cross_school_isolation_on_view(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $otherHead = $this->user(Role::DISCIPLINE_HEAD, $this->otherSchool);

        $incident = $this->logIncident($reporter, $subject);
        $this->assertFalse($otherHead->can('view', $incident));
    }

    // ---------- Policy: decide ----------

    public function test_discipline_head_can_decide(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $head = $this->user(Role::DISCIPLINE_HEAD);
        $incident = $this->logIncident($reporter, $subject);

        $this->assertTrue($head->can('decide', $incident));
    }

    public function test_academic_head_cannot_decide(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $ah = $this->user(Role::ACADEMIC_HEAD);
        $incident = $this->logIncident($reporter, $subject);

        $this->assertFalse($ah->can('decide', $incident));
    }

    public function test_subject_cannot_decide_their_own_incident(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::DISCIPLINE_HEAD); // discipline head is subject of a teacher-conduct report
        $incident = $this->logIncident($reporter, $subject, [
            'category' => DisciplineIncident::CATEGORY_TEACHER_CONDUCT,
        ]);

        $this->assertFalse($subject->can('decide', $incident));
    }

    public function test_cannot_decide_already_closed_incident(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $head = $this->user(Role::DISCIPLINE_HEAD);
        $incident = $this->logIncident($reporter, $subject);

        app(DisciplineService::class)->resolve($head, $incident, 'Parent meeting held');
        $this->assertFalse($head->can('decide', $incident->fresh()));
    }

    // ---------- Lifecycle ----------

    public function test_log_resolve_flow_writes_activity_log(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $head = $this->user(Role::DISCIPLINE_HEAD);

        $this->actingAs($reporter);
        $incident = $this->logIncident($reporter, $subject);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'discipline.incident.logged',
            'user_id' => $reporter->id,
        ]);

        $this->actingAs($head);
        app(DisciplineService::class)->resolve($head, $incident, 'Warning issued and parents notified');
        $fresh = $incident->fresh();
        $this->assertSame(DisciplineIncident::STATUS_RESOLVED, $fresh->status);
        $this->assertSame($head->id, $fresh->decided_by);
        $this->assertNotNull($fresh->decided_at);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'discipline.incident.resolved',
            'user_id' => $head->id,
        ]);
    }

    public function test_dismiss_flow(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $director = $this->user(Role::DIRECTOR);

        $incident = $this->logIncident($reporter, $subject);
        app(DisciplineService::class)->dismiss($director, $incident, 'Insufficient evidence');

        $this->assertSame(DisciplineIncident::STATUS_DISMISSED, $incident->fresh()->status);
    }

    // ---------- HTTP ----------

    public function test_http_index_shows_only_same_school_incidents(): void
    {
        $head = $this->user(Role::DISCIPLINE_HEAD);
        $mineReporter = $this->user(Role::TEACHER);
        $mineSubject = $this->user(Role::STUDENT);
        $this->logIncident($mineReporter, $mineSubject, ['title' => 'Alpha incident']);

        $otherReporter = $this->user(Role::TEACHER, $this->otherSchool);
        $otherSubject = $this->user(Role::STUDENT, $this->otherSchool);
        $this->logIncident($otherReporter, $otherSubject, ['title' => 'Beta incident']);

        $this->actingAs($head)
            ->get(route('discipline.index'))
            ->assertOk()
            ->assertSee('Alpha incident')
            ->assertDontSee('Beta incident');
    }

    public function test_http_store_validates_and_creates(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);

        $this->actingAs($reporter)
            ->post(route('discipline.store'), [
                'subject_id' => $subject->id,
                'category' => 'minor',
                'title' => 'Gum in class',
                'description' => 'Chewing gum during lesson',
                'occurred_on' => now()->toDateString(),
                'severity' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('discipline_incidents', [
            'subject_id' => $subject->id,
            'reported_by' => $reporter->id,
            'title' => 'Gum in class',
        ]);
    }

    public function test_http_store_rejects_future_date(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);

        $this->actingAs($reporter)
            ->post(route('discipline.store'), [
                'subject_id' => $subject->id,
                'category' => 'minor',
                'title' => 'bad date',
                'occurred_on' => now()->addDays(3)->toDateString(),
                'severity' => 2,
            ])
            ->assertSessionHasErrors('occurred_on');
    }

    public function test_http_store_blocks_cross_school_subject(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $otherStudent = $this->user(Role::STUDENT, $this->otherSchool);

        $this->actingAs($reporter)
            ->post(route('discipline.store'), [
                'subject_id' => $otherStudent->id,
                'category' => 'minor',
                'title' => 'ignored',
                'occurred_on' => now()->toDateString(),
                'severity' => 2,
            ])
            ->assertForbidden();
    }

    public function test_http_resolve_happy_path(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $head = $this->user(Role::DISCIPLINE_HEAD);
        $incident = $this->logIncident($reporter, $subject);

        $this->actingAs($head)
            ->post(route('discipline.resolve', $incident), ['resolution' => 'Resolved amicably'])
            ->assertRedirect(route('discipline.show', $incident));

        $this->assertSame(DisciplineIncident::STATUS_RESOLVED, $incident->fresh()->status);
    }

    public function test_http_resolve_forbidden_for_unrelated_role(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $ah = $this->user(Role::ACADEMIC_HEAD);
        $incident = $this->logIncident($reporter, $subject);

        $this->actingAs($ah)
            ->post(route('discipline.resolve', $incident), ['resolution' => 'nope'])
            ->assertForbidden();
    }

    public function test_student_cannot_access_index_page(): void
    {
        $student = $this->user(Role::STUDENT);

        $this->actingAs($student)
            ->get(route('discipline.index'))
            ->assertForbidden();
    }

    // ---------- Timeline / correlation ----------

    public function test_timeline_returns_incidents_and_exam_overlay(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $head = $this->user(Role::DISCIPLINE_HEAD);

        $this->logIncident($reporter, $subject, ['occurred_on' => now()->subDays(30)->toDateString()]);
        $this->logIncident($reporter, $subject, ['occurred_on' => now()->subDays(10)->toDateString(), 'title' => 'Second incident']);

        // Exam in the same window — should appear in overlay.
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'creator_id' => $reporter->id,
            'creator_role_level' => $reporter->role->level,
            'title' => 'Math Mid-term',
            'subject' => 'Math',
            'status' => Exam::STATUS_PUBLISHED,
        ]);
        $exam->timestamps = false;
        $exam->created_at = now()->subDays(20);
        $exam->updated_at = now()->subDays(20);
        $exam->save();

        $this->actingAs($head)
            ->get(route('discipline.timeline', $subject))
            ->assertOk()
            ->assertSee('Discipline timeline')
            ->assertSee('Math Mid-term')
            ->assertSee('Second incident');
    }

    public function test_timeline_subject_can_see_own(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $this->logIncident($reporter, $subject);

        $this->actingAs($subject)
            ->get(route('discipline.timeline', $subject))
            ->assertOk();
    }

    public function test_timeline_cross_school_blocked(): void
    {
        $subject = $this->user(Role::STUDENT);
        $foreignHead = $this->user(Role::DISCIPLINE_HEAD, $this->otherSchool);

        $this->actingAs($foreignHead)
            ->get(route('discipline.timeline', $subject))
            ->assertForbidden();
    }

    public function test_timeline_summary_counts_by_category(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);

        $this->logIncident($reporter, $subject, ['category' => 'minor', 'severity' => 1]);
        $this->logIncident($reporter, $subject, ['category' => 'minor', 'severity' => 1, 'title' => 'Again']);
        $this->logIncident($reporter, $subject, ['category' => 'commendation', 'severity' => 1, 'title' => 'Helpful']);

        $result = app(DisciplineService::class)->timeline($subject);
        $this->assertSame(3, $result['summary']['total']);
        $this->assertSame(2, $result['summary']['by_category']['minor']);
        $this->assertSame(1, $result['summary']['by_category']['commendation']);
    }

    public function test_system_admin_bypasses_school_scope(): void
    {
        $reporter = $this->user(Role::TEACHER);
        $subject = $this->user(Role::STUDENT);
        $incident = $this->logIncident($reporter, $subject);

        $sa = $this->systemAdmin();
        $this->assertTrue($sa->can('view', $incident));
        $this->assertTrue($sa->can('decide', $incident));
        $this->assertTrue($sa->can('viewAny', DisciplineIncident::class));
    }
}
