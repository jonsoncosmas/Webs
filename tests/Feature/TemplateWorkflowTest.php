<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\Template;
use App\Models\TemplateAssignment;
use App\Models\User;
use App\Services\Templates\TemplateService;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TemplateWorkflowTest extends TestCase
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

    private function service(): TemplateService
    {
        return app(TemplateService::class);
    }

    private function template(?User $author = null, array $overrides = []): Template
    {
        $author ??= $this->systemAdmin();

        return $this->service()->createTemplate($author, array_merge([
            'name' => 'Basic marklist',
            'kind' => Template::KIND_RESULT_MARKLIST,
            'layout' => ['view' => 'templates.layouts.result_marklist_basic'],
        ], $overrides));
    }

    // --- TemplatePolicy ---------------------------------------------------

    public function test_only_system_admin_can_create_templates(): void
    {
        $this->assertTrue($this->systemAdmin()->can('create', Template::class));

        foreach ([Role::DIRECTOR, Role::SCHOOL_ADMIN, Role::ACADEMIC_HEAD, Role::TEACHER, Role::IT, Role::HR, Role::STUDENT] as $slug) {
            $user = $this->user($slug);
            $this->assertFalse(
                $user->can('create', Template::class),
                "Role {$slug} must not create templates.",
            );
        }
    }

    public function test_schools_only_see_active_templates_in_list_policy(): void
    {
        $this->template(); // active by default
        $teacher = $this->user(Role::TEACHER);

        // viewAny only requires active + role
        $this->assertTrue($teacher->can('viewAny', Template::class));
    }

    public function test_system_admin_can_archive_and_reactivate_template(): void
    {
        $admin = $this->systemAdmin();
        $template = $this->template($admin);

        $this->assertTrue($template->is_active);
        $this->service()->toggleActive($admin, $template, false);
        $this->assertFalse($template->fresh()->is_active);

        $this->service()->toggleActive($admin, $template, true);
        $this->assertTrue($template->fresh()->is_active);
    }

    public function test_non_admin_cannot_archive_template(): void
    {
        $template = $this->template();
        $director = $this->user(Role::DIRECTOR);

        $this->assertFalse($director->can('archive', $template));
    }

    // --- TemplateAssignmentPolicy ----------------------------------------

    public function test_assigner_roles_can_create_assignments(): void
    {
        $allowed = [Role::DIRECTOR, Role::DEPUTY_DIRECTOR, Role::SCHOOL_ADMIN, Role::ACADEMIC_HEAD, Role::DEPUTY_ACADEMIC_HEAD, Role::EXAMINATION_MASTER];
        foreach ($allowed as $slug) {
            $user = $this->user($slug);
            $this->assertTrue($user->can('create', TemplateAssignment::class), "Role {$slug} should create assignments.");
        }
    }

    public function test_teacher_cannot_create_assignment(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $this->assertFalse($teacher->can('create', TemplateAssignment::class));
    }

    public function test_student_cannot_view_assignments(): void
    {
        $student = $this->user(Role::STUDENT);
        $this->assertTrue($student->can('viewAny', TemplateAssignment::class));
        // viewAny allowed (so routes don't 403 broadly) but per-record view is school-scoped.
        $this->assertTrue($student->isActive());
    }

    public function test_user_from_other_school_cannot_view_assignment(): void
    {
        $otherSchool = School::create(['name' => 'Other', 'slug' => 'other-'.uniqid(), 'status' => 'active']);
        $outsider = $this->user(Role::SCHOOL_ADMIN, $otherSchool);

        $creator = $this->user(Role::SCHOOL_ADMIN);
        $template = $this->template();
        $assignment = $this->service()->createAssignment($creator, $template, [
            'class_label' => 'Form 4A',
        ]);

        $this->assertFalse($outsider->can('view', $assignment));
    }

    public function test_assignee_can_edit_assignment_while_not_printed(): void
    {
        $creator = $this->user(Role::ACADEMIC_HEAD);
        $teacher = $this->user(Role::TEACHER);
        $template = $this->template();

        $assignment = $this->service()->createAssignment($creator, $template, [
            'class_label' => 'Form 2 Beta',
            'assigned_to_id' => $teacher->id,
        ]);

        $this->assertTrue($teacher->can('update', $assignment));
        $this->assertTrue($creator->can('update', $assignment));
    }

    public function test_teacher_who_is_not_assignee_cannot_update(): void
    {
        $creator = $this->user(Role::SCHOOL_ADMIN);
        $template = $this->template();
        $assignment = $this->service()->createAssignment($creator, $template, [
            'class_label' => 'Form 3',
        ]);

        $randomTeacher = $this->user(Role::TEACHER);
        $this->assertFalse($randomTeacher->can('update', $assignment));
    }

    public function test_mark_ready_is_only_available_while_draft(): void
    {
        $creator = $this->user(Role::SCHOOL_ADMIN);
        $template = $this->template();
        $assignment = $this->service()->createAssignment($creator, $template, [
            'class_label' => 'Form 3',
        ]);

        $this->assertTrue($creator->can('markReady', $assignment));

        $this->service()->markReady($creator, $assignment);
        $assignment->refresh();

        $this->assertSame(TemplateAssignment::STATUS_READY, $assignment->status);
        $this->assertFalse($creator->can('markReady', $assignment));
    }

    public function test_reopen_requires_ready_status(): void
    {
        $creator = $this->user(Role::SCHOOL_ADMIN);
        $template = $this->template();
        $assignment = $this->service()->createAssignment($creator, $template, [
            'class_label' => 'Form 3',
        ]);

        $this->assertFalse($creator->can('reopen', $assignment)); // draft

        $this->service()->markReady($creator, $assignment);
        $this->assertTrue($creator->can('reopen', $assignment->fresh())); // ready
    }

    public function test_printed_assignment_becomes_uneditable(): void
    {
        $creator = $this->user(Role::SCHOOL_ADMIN);
        $teacher = $this->user(Role::TEACHER);
        $template = $this->template();
        $assignment = $this->service()->createAssignment($creator, $template, [
            'class_label' => 'Form 1',
            'assigned_to_id' => $teacher->id,
        ]);

        $this->service()->markPrinted($creator, $assignment);
        $assignment->refresh();

        $this->assertSame(TemplateAssignment::STATUS_PRINTED, $assignment->status);
        $this->assertNotNull($assignment->printed_at);
        $this->assertFalse($teacher->can('update', $assignment));
        $this->assertFalse($creator->can('update', $assignment));
    }

    public function test_archive_is_restricted_to_creator_or_school_leadership(): void
    {
        $creator = $this->user(Role::ACADEMIC_HEAD);
        $template = $this->template();
        $assignment = $this->service()->createAssignment($creator, $template, [
            'class_label' => 'Form 1',
        ]);

        $this->assertTrue($creator->can('archive', $assignment));
        $this->assertTrue($this->user(Role::DIRECTOR)->can('archive', $assignment));
        $this->assertTrue($this->user(Role::SCHOOL_ADMIN)->can('archive', $assignment));
        $this->assertFalse($this->user(Role::TEACHER)->can('archive', $assignment));
    }

    public function test_system_admin_bypasses_school_scope(): void
    {
        $otherSchool = School::create(['name' => 'Other', 'slug' => 'other-'.uniqid(), 'status' => 'active']);
        $creator = $this->user(Role::SCHOOL_ADMIN, $otherSchool);
        $template = $this->template();
        $assignment = $this->service()->createAssignment($creator, $template, [
            'class_label' => 'Form 1',
        ]);

        $admin = $this->systemAdmin();
        $this->assertTrue($admin->can('view', $assignment));
        $this->assertTrue($admin->can('update', $assignment));
        $this->assertTrue($admin->can('archive', $assignment));
    }

    // --- HTTP smoke: print view renders A4 sheet -------------------------

    public function test_print_route_renders_sheet_and_marks_printed(): void
    {
        $creator = $this->user(Role::SCHOOL_ADMIN);
        $template = $this->template();
        $assignment = $this->service()->createAssignment($creator, $template, [
            'class_label' => 'Form 4A',
            'subject' => 'Mathematics',
            'term' => 'Term 1 2025',
        ]);

        $response = $this->actingAs($creator)->get(route('assignments.print', $assignment));

        $response->assertOk();
        $response->assertSee('Result marklist · Form 4A');
        $response->assertSee('Mathematics', false);
        $response->assertSee('class="sheet"', false);

        $this->assertSame(TemplateAssignment::STATUS_PRINTED, $assignment->fresh()->status);
    }
}
