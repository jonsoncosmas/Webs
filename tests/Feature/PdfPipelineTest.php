<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Role;
use App\Models\School;
use App\Models\StaffProfile;
use App\Models\Template;
use App\Models\TemplateAssignment;
use App\Models\User;
use App\Services\Portal\PortalService;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PdfPipelineTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PackageSeeder::class);
        $this->school = School::create(['name' => 'Alpha Academy', 'slug' => 'alpha', 'status' => 'active']);
        $this->otherSchool = School::create(['name' => 'Beta School', 'slug' => 'beta', 'status' => 'active']);
    }

    private function user(string $roleSlug, ?School $school = null, array $overrides = []): User
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return User::create(array_merge([
            'school_id' => ($school ?? $this->school)->id,
            'role_id' => $role->id,
            'first_name' => 'T',
            'last_name' => ucfirst($roleSlug).rand(1000, 9999),
            'username' => $roleSlug.'-'.uniqid(),
            'password' => Hash::make('x'),
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
        ], $overrides));
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

    private function assertIsPdf(TestResponse $response, string $expectedFilenameContains): void
    {
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString($expectedFilenameContains, $disposition);
        // Sanity-check: dompdf output starts with %PDF.
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    // ---------- Assignment marklist PDF ----------

    private function assignment(?User $creator = null, ?School $school = null): TemplateAssignment
    {
        $school ??= $this->school;
        $creator ??= $this->user(Role::ACADEMIC_HEAD, $school);

        $template = Template::create([
            'name' => 'Basic marklist',
            'slug' => 'basic-marklist-'.uniqid(),
            'kind' => Template::KIND_RESULT_MARKLIST,
            'layout' => ['view' => 'templates.layouts.result_marklist_basic'],
            'is_active' => true,
            'created_by' => $this->systemAdmin()->id,
        ]);

        return TemplateAssignment::create([
            'school_id' => $school->id,
            'template_id' => $template->id,
            'created_by' => $creator->id,
            'assigned_to_id' => $creator->id,
            'class_label' => 'Form 4 East',
            'subject' => 'Mathematics',
            'term' => 'Term 2',
            'status' => TemplateAssignment::STATUS_DRAFT,
            'data' => [
                'subjects' => [],
                'rows' => [
                    ['name' => 'Amina Bakari', 'adm_no' => 'A001', 'score' => 88, 'grade' => 'A'],
                    ['name' => 'Joseph Mwangi', 'adm_no' => 'A002', 'score' => 72, 'grade' => 'B'],
                ],
            ],
        ]);
    }

    public function test_school_admin_can_download_assignment_pdf(): void
    {
        $admin = $this->user(Role::SCHOOL_ADMIN);
        $assignment = $this->assignment();

        $response = $this->actingAs($admin)->get(route('assignments.pdf', $assignment));
        $this->assertIsPdf($response, 'result_marklist');
    }

    public function test_cross_school_user_cannot_download_assignment_pdf(): void
    {
        $outsider = $this->user(Role::SCHOOL_ADMIN, $this->otherSchool);
        $assignment = $this->assignment();

        $this->actingAs($outsider)
            ->get(route('assignments.pdf', $assignment))
            ->assertForbidden();
    }

    public function test_system_admin_can_download_any_assignment_pdf(): void
    {
        $assignment = $this->assignment();

        $response = $this->actingAs($this->systemAdmin())->get(route('assignments.pdf', $assignment));
        $this->assertIsPdf($response, 'result_marklist');
    }

    public function test_marklist_pdf_view_contains_student_rows(): void
    {
        $assignment = $this->assignment();
        $assignment->loadMissing(['template', 'school', 'creator', 'assignee']);

        $html = view('pdf.marklist', [
            'assignment' => $assignment,
            'title' => 'test',
        ])->render();

        $this->assertStringContainsString('Alpha Academy', $html);
        $this->assertStringContainsString('Form 4 East', $html);
        $this->assertStringContainsString('Amina Bakari', $html);
        $this->assertStringContainsString('Joseph Mwangi', $html);
        $this->assertStringContainsString('A001', $html);
    }

    public function test_guest_cannot_download_assignment_pdf(): void
    {
        $assignment = $this->assignment();
        $this->get(route('assignments.pdf', $assignment))->assertRedirect(route('login'));
    }

    // ---------- HR certificate PDF ----------

    private function teacherWithProfile(?School $school = null): User
    {
        $school ??= $this->school;
        $teacher = $this->user(Role::TEACHER, $school, [
            'first_name' => 'Rehema',
            'last_name' => 'Saidi',
        ]);

        StaffProfile::create([
            'user_id' => $teacher->id,
            'school_id' => $school->id,
            'employee_no' => 'EMP-001',
            'employment_type' => 'permanent',
            'hired_on' => '2022-03-15',
        ]);

        return $teacher->fresh();
    }

    public function test_hr_can_download_certificate_for_teacher(): void
    {
        $hr = $this->user(Role::HR);
        $teacher = $this->teacherWithProfile();

        $response = $this->actingAs($hr)->get(route('hr.certificate.pdf', $teacher));
        $this->assertIsPdf($response, 'certificate-of-service');
    }

    public function test_staff_can_download_their_own_certificate(): void
    {
        $teacher = $this->teacherWithProfile();

        $response = $this->actingAs($teacher)->get(route('hr.certificate.pdf', $teacher));
        $this->assertIsPdf($response, 'certificate-of-service');
    }

    public function test_unrelated_teacher_cannot_download_others_certificate(): void
    {
        $teacher = $this->teacherWithProfile();
        $intruder = $this->user(Role::TEACHER);

        $this->actingAs($intruder)
            ->get(route('hr.certificate.pdf', $teacher))
            ->assertForbidden();
    }

    public function test_cross_school_hr_cannot_download_certificate(): void
    {
        $teacher = $this->teacherWithProfile();
        $foreignHr = $this->user(Role::HR, $this->otherSchool);

        $this->actingAs($foreignHr)
            ->get(route('hr.certificate.pdf', $teacher))
            ->assertForbidden();
    }

    public function test_certificate_view_contains_subject_details(): void
    {
        $teacher = $this->teacherWithProfile();
        $hr = $this->user(Role::HR);

        $html = view('pdf.certificate', [
            'subject' => $teacher,
            'issuer' => $hr,
            'title' => 'test',
        ])->render();

        $this->assertStringContainsString('Rehema Saidi', $html);
        $this->assertStringContainsString('Alpha Academy', $html);
        $this->assertStringContainsString('Teacher', $html);
        $this->assertStringContainsString('15 March 2022', $html);
        $this->assertStringContainsString('EMP-001', $html);
    }

    // ---------- Exam result PDF ----------

    private function scoredAttempt(?School $school = null): ExamAttempt
    {
        $school ??= $this->school;
        $teacher = $this->user(Role::ACADEMIC_HEAD, $school);
        $student = $this->user(Role::STUDENT, $school, [
            'first_name' => 'Joyce',
            'last_name' => 'Neema',
        ]);

        $exam = Exam::create([
            'school_id' => $school->id,
            'creator_id' => $teacher->id,
            'creator_role_level' => $teacher->role->level,
            'title' => 'Mathematics — End Term',
            'subject' => 'Mathematics',
            'status' => Exam::STATUS_PUBLISHED,
            'total_marks' => 100,
        ]);

        return app(PortalService::class)->recordScore($teacher, $student, $exam, 82, 100, 'A', 'Excellent.');
    }

    public function test_student_can_download_their_own_result_pdf(): void
    {
        $attempt = $this->scoredAttempt();
        $student = User::find($attempt->student_user_id);

        $response = $this->actingAs($student)->get(route('portal.result.pdf', $attempt));
        $this->assertIsPdf($response, 'result-joyce-neema');
    }

    public function test_staff_can_download_student_result_pdf(): void
    {
        $attempt = $this->scoredAttempt();
        $teacher = $this->user(Role::TEACHER);

        $response = $this->actingAs($teacher)->get(route('portal.result.pdf', $attempt));
        $this->assertIsPdf($response, 'result-joyce-neema');
    }

    public function test_cross_school_student_cannot_download_foreign_result_pdf(): void
    {
        $attempt = $this->scoredAttempt();
        $intruder = $this->user(Role::STUDENT, $this->otherSchool);

        $this->actingAs($intruder)
            ->get(route('portal.result.pdf', $attempt))
            ->assertForbidden();
    }

    public function test_result_view_contains_score_and_grade(): void
    {
        $attempt = $this->scoredAttempt()->load(['exam', 'school', 'student', 'scorer']);

        $html = view('pdf.result', [
            'attempt' => $attempt,
            'title' => 'test',
        ])->render();

        $this->assertStringContainsString('Joyce Neema', $html);
        $this->assertStringContainsString('Mathematics — End Term', $html);
        $this->assertStringContainsString('82', $html);
        $this->assertStringContainsString('100', $html);
        $this->assertStringContainsString('Excellent.', $html);
    }
}
