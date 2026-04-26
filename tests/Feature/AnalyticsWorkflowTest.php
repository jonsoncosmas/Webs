<?php

namespace Tests\Feature;

use App\Models\DisciplineIncident;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Analytics\AnalyticsService;
use App\Services\Discipline\DisciplineService;
use App\Services\Portal\PortalService;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnalyticsWorkflowTest extends TestCase
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
            'last_name' => ucfirst(str_replace('_', '', $roleSlug)).rand(1000, 9999),
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

    private function scoredAttempt(User $student, User $scorer, int $score = 70, int $total = 100, string $subject = 'Mathematics'): void
    {
        $exam = Exam::create([
            'school_id' => $student->school_id,
            'creator_id' => $scorer->id,
            'creator_role_level' => $scorer->role->level,
            'title' => $subject.' Mid-Term '.uniqid(),
            'subject' => $subject,
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => $total,
        ]);

        app(PortalService::class)->recordScore($scorer, $student, $exam, $score, $total, 'B', 'note');
    }

    private function incident(User $reporter, User $subject, string $category = DisciplineIncident::CATEGORY_MAJOR, int $severity = 3): DisciplineIncident
    {
        return app(DisciplineService::class)->logIncident($reporter, $subject, [
            'category' => $category,
            'title' => 'Test incident '.uniqid(),
            'description' => 'desc',
            'occurred_on' => now()->toDateString(),
            'severity' => $severity,
        ]);
    }

    // ---------- Policy / access ----------

    public function test_director_can_view_school_analytics(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $this->assertTrue($director->can('analytics.view-school', $this->school));
    }

    public function test_school_admin_academic_head_hr_discipline_can_view(): void
    {
        foreach ([Role::SCHOOL_ADMIN, Role::ACADEMIC_HEAD, Role::DEPUTY_ACADEMIC_HEAD, Role::HR, Role::DISCIPLINE_HEAD, Role::DEPUTY_DIRECTOR, Role::DEPUTY_DISCIPLINE_HEAD] as $slug) {
            $u = $this->user($slug);
            $this->assertTrue($u->can('analytics.view-school', $this->school), $slug.' should view');
        }
    }

    public function test_teacher_cannot_view_analytics(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $this->assertFalse($teacher->can('analytics.view-school', $this->school));

        $this->actingAs($teacher)
            ->get(route('analytics.overview'))
            ->assertForbidden();
    }

    public function test_student_and_parent_cannot_view_analytics(): void
    {
        $student = $this->user(Role::STUDENT);
        $parent = $this->user(Role::PARENT_ROLE);
        $this->assertFalse($student->can('analytics.view-school', $this->school));
        $this->assertFalse($parent->can('analytics.view-school', $this->school));
    }

    public function test_cross_school_director_cannot_view_other_school(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->otherSchool);
        $this->assertFalse($director->can('analytics.view-school', $this->school));
    }

    public function test_system_admin_can_view_any_school(): void
    {
        $admin = $this->systemAdmin();
        $this->assertTrue($admin->can('analytics.view-school', $this->school));
        $this->assertTrue($admin->can('analytics.view-school', $this->otherSchool));
    }

    public function test_overview_page_renders_for_director(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $this->actingAs($director)
            ->get(route('analytics.overview'))
            ->assertOk()
            ->assertSee('Analytics')
            ->assertSee('Active staff');
    }

    public function test_overview_page_404s_when_no_school_context(): void
    {
        $admin = $this->systemAdmin();
        $this->actingAs($admin)
            ->get(route('analytics.overview'))
            ->assertNotFound();
    }

    public function test_system_admin_can_pass_school_id_query_param(): void
    {
        $admin = $this->systemAdmin();
        $this->actingAs($admin)
            ->get(route('analytics.overview', ['school_id' => $this->school->id]))
            ->assertOk();
    }

    public function test_non_admin_school_id_param_is_ignored(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $this->actingAs($director)
            ->get(route('analytics.overview', ['school_id' => $this->otherSchool->id]))
            ->assertOk()
            ->assertSee($this->school->name);
    }

    // ---------- KPI correctness ----------

    public function test_school_overview_counts_students_staff_and_attempts(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $this->user(Role::STUDENT);
        $this->scoredAttempt($student, $teacher, 70, 100);

        $kpis = app(AnalyticsService::class)->schoolOverview($this->school);

        $this->assertSame(2, $kpis['active_staff']); // director + teacher
        $this->assertSame(2, $kpis['students']);
        $this->assertSame(1, $kpis['scored_attempts']);
        $this->assertSame(1, $kpis['published_exams']);
    }

    public function test_school_overview_isolates_other_school(): void
    {
        $teacher = $this->user(Role::TEACHER, $this->otherSchool);
        $student = $this->user(Role::STUDENT, $this->otherSchool);
        $this->scoredAttempt($student, $teacher, 90, 100);

        $kpis = app(AnalyticsService::class)->schoolOverview($this->school);

        $this->assertSame(0, $kpis['scored_attempts']);
        $this->assertSame(0, $kpis['students']);
    }

    public function test_score_distribution_buckets(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $a = $this->user(Role::STUDENT);
        $b = $this->user(Role::STUDENT);
        $c = $this->user(Role::STUDENT);
        $d = $this->user(Role::STUDENT);
        $this->scoredAttempt($a, $teacher, 30, 100); // 0-39
        $this->scoredAttempt($b, $teacher, 50, 100); // 40-59
        $this->scoredAttempt($c, $teacher, 65, 100); // 60-79
        $this->scoredAttempt($d, $teacher, 95, 100); // 80-100

        $dist = app(AnalyticsService::class)->scoreDistribution($this->school);

        $this->assertSame(['0-39' => 1, '40-59' => 1, '60-79' => 1, '80-100' => 1], $dist);
    }

    public function test_score_distribution_excludes_zero_total_marks(): void
    {
        // Direct insert to bypass service guards.
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Bad exam',
            'subject' => 'Mathematics',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => 0,
        ]);
        ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_user_id' => $student->id,
            'school_id' => $this->school->id,
            'score' => 0,
            'total_marks' => 0,
            'status' => ExamAttempt::STATUS_SCORED,
        ]);

        $dist = app(AnalyticsService::class)->scoreDistribution($this->school);
        $this->assertSame(['0-39' => 0, '40-59' => 0, '60-79' => 0, '80-100' => 0], $dist);
    }

    public function test_published_exams_by_subject(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $a = $this->user(Role::STUDENT);
        $b = $this->user(Role::STUDENT);
        $c = $this->user(Role::STUDENT);
        $this->scoredAttempt($a, $teacher, 70, 100, 'Mathematics');
        $this->scoredAttempt($b, $teacher, 70, 100, 'Mathematics');
        $this->scoredAttempt($c, $teacher, 70, 100, 'English');

        $bySubject = app(AnalyticsService::class)->publishedExamsBySubject($this->school);

        $this->assertSame(2, $bySubject->get('Mathematics'));
        $this->assertSame(1, $bySubject->get('English'));
    }

    public function test_incidents_by_category_and_severity(): void
    {
        $head = $this->user(Role::DISCIPLINE_HEAD);
        $student = $this->user(Role::STUDENT);
        $other = $this->user(Role::STUDENT);
        $this->incident($head, $student, DisciplineIncident::CATEGORY_MAJOR, 4);
        $this->incident($head, $student, DisciplineIncident::CATEGORY_MINOR, 1);
        $this->incident($head, $other, DisciplineIncident::CATEGORY_MAJOR, 4);

        $svc = app(AnalyticsService::class);
        $byCat = $svc->incidentsByCategory($this->school);
        $sev = $svc->openIncidentSeverity($this->school);

        $this->assertSame(2, $byCat->get('major'));
        $this->assertSame(1, $byCat->get('minor'));
        $this->assertSame(2, $sev[4]);
        $this->assertSame(1, $sev[1]);
    }

    public function test_behaviour_academics_correlation_separates_groups(): void
    {
        $head = $this->user(Role::DISCIPLINE_HEAD);
        $teacher = $this->user(Role::TEACHER);

        $flagged = $this->user(Role::STUDENT);
        $clean = $this->user(Role::STUDENT);

        $this->incident($head, $flagged, DisciplineIncident::CATEGORY_MAJOR, 4);
        $this->scoredAttempt($flagged, $teacher, 40, 100); // flagged → 40%
        $this->scoredAttempt($clean, $teacher, 80, 100);   // clean → 80%

        $c = app(AnalyticsService::class)->behaviourAcademicsCorrelation($this->school);

        $this->assertSame(1, $c['with_incidents']['count']);
        $this->assertSame(40.0, $c['with_incidents']['avg_pct']);
        $this->assertSame(1, $c['clean']['count']);
        $this->assertSame(80.0, $c['clean']['avg_pct']);
    }

    public function test_correlation_excludes_resolved_or_dismissed_incidents(): void
    {
        $head = $this->user(Role::DISCIPLINE_HEAD);
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);

        $incident = $this->incident($head, $student, DisciplineIncident::CATEGORY_MAJOR, 4);
        app(DisciplineService::class)->resolve($head, $incident, 'cleared');
        $this->scoredAttempt($student, $teacher, 90, 100);

        $c = app(AnalyticsService::class)->behaviourAcademicsCorrelation($this->school);

        // Resolved incident should not flag the student.
        $this->assertSame(0, $c['with_incidents']['count']);
        $this->assertSame(1, $c['clean']['count']);
        $this->assertSame(90.0, $c['clean']['avg_pct']);
    }

    public function test_correlation_excludes_commendations_from_flagged_set(): void
    {
        $head = $this->user(Role::DISCIPLINE_HEAD);
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $this->incident($head, $student, DisciplineIncident::CATEGORY_COMMENDATION, 1);
        $this->scoredAttempt($student, $teacher, 90, 100);

        $c = app(AnalyticsService::class)->behaviourAcademicsCorrelation($this->school);

        $this->assertSame(0, $c['with_incidents']['count']);
        $this->assertSame(1, $c['clean']['count']);
        $this->assertSame(90.0, $c['clean']['avg_pct']);
    }

    public function test_student_deep_dive_aggregates_attempts_and_incidents(): void
    {
        $head = $this->user(Role::DISCIPLINE_HEAD);
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $this->scoredAttempt($student, $teacher, 60, 100);
        $this->scoredAttempt($student, $teacher, 80, 100);
        $this->incident($head, $student);

        $deep = app(AnalyticsService::class)->studentDeepDive($student);

        $this->assertSame(2, $deep['attempts_count']);
        $this->assertSame(70.0, $deep['avg_pct']);
        $this->assertSame(1, $deep['incidents_total']);
        $this->assertSame(1, $deep['incidents_open']);
    }

    public function test_student_deep_dive_route_blocks_non_student_target(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $teacher = $this->user(Role::TEACHER);

        $this->actingAs($director)
            ->get(route('analytics.student', $teacher))
            ->assertForbidden();
    }

    public function test_student_deep_dive_route_renders_for_authorized_viewer(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $student = $this->user(Role::STUDENT);

        $this->actingAs($director)
            ->get(route('analytics.student', $student))
            ->assertOk()
            ->assertSee($student->fullName());
    }

    public function test_student_deep_dive_blocks_cross_school_viewer(): void
    {
        $director = $this->user(Role::DIRECTOR, $this->otherSchool);
        $student = $this->user(Role::STUDENT);

        $this->actingAs($director)
            ->get(route('analytics.student', $student))
            ->assertForbidden();
    }

    public function test_scoring_trend_has_one_entry_per_day(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $student = $this->user(Role::STUDENT);
        $this->scoredAttempt($student, $teacher, 50, 100);

        $trend = app(AnalyticsService::class)->scoringTrend($this->school, 7);

        $this->assertCount(7, $trend);
        $this->assertSame(1, $trend->get(now()->toDateString()));
    }
}
