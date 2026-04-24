<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\StaffCertificate;
use App\Models\StaffLeave;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\HR\StaffService;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HRWorkflowTest extends TestCase
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

    private function service(): StaffService
    {
        return app(StaffService::class);
    }

    // ---------- StaffProfile policy ----------

    public function test_hr_can_edit_teacher_profile(): void
    {
        $hr = $this->user(Role::HR);
        $teacher = $this->user(Role::TEACHER);

        $this->assertTrue($hr->can('updateFor', [StaffProfile::class, $teacher]));
    }

    public function test_hr_cannot_edit_director_profile(): void
    {
        $hr = $this->user(Role::HR);
        $director = $this->user(Role::DIRECTOR);

        $this->assertFalse($hr->can('updateFor', [StaffProfile::class, $director]));
    }

    public function test_hr_cannot_edit_school_admin_profile(): void
    {
        $hr = $this->user(Role::HR);
        $admin = $this->user(Role::SCHOOL_ADMIN);

        $this->assertFalse($hr->can('updateFor', [StaffProfile::class, $admin]));
    }

    public function test_hr_cannot_edit_deputy_director_profile(): void
    {
        $hr = $this->user(Role::HR);
        $deputy = $this->user(Role::DEPUTY_DIRECTOR);

        $this->assertFalse($hr->can('updateFor', [StaffProfile::class, $deputy]));
    }

    public function test_director_can_view_any_staff_profile(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $teacher = $this->user(Role::TEACHER);

        $this->assertTrue($director->can('viewUser', [StaffProfile::class, $teacher]));
    }

    public function test_director_cannot_edit_via_hr_policy(): void
    {
        $director = $this->user(Role::DIRECTOR);
        $teacher = $this->user(Role::TEACHER);

        // HR is the only school-level editor of staff profiles.
        $this->assertFalse($director->can('updateFor', [StaffProfile::class, $teacher]));
    }

    public function test_teacher_can_view_own_profile(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $this->assertTrue($teacher->can('viewUser', [StaffProfile::class, $teacher]));
    }

    public function test_teacher_cannot_view_other_teacher_profile(): void
    {
        $a = $this->user(Role::TEACHER);
        $b = $this->user(Role::TEACHER);
        $this->assertFalse($a->can('viewUser', [StaffProfile::class, $b]));
    }

    public function test_cross_school_isolation_on_view(): void
    {
        $hr = $this->user(Role::HR);
        $otherTeacher = $this->user(Role::TEACHER, $this->otherSchool);

        $this->assertFalse($hr->can('viewUser', [StaffProfile::class, $otherTeacher]));
        $this->assertFalse($hr->can('updateFor', [StaffProfile::class, $otherTeacher]));
    }

    public function test_system_admin_bypasses_school_scope(): void
    {
        $sa = $this->systemAdmin();
        $teacher = $this->user(Role::TEACHER, $this->otherSchool);

        $this->assertTrue($sa->can('viewUser', [StaffProfile::class, $teacher]));
        $this->assertTrue($sa->can('updateFor', [StaffProfile::class, $teacher]));
    }

    // ---------- Certificates ----------

    public function test_hr_can_add_certificate_to_teacher(): void
    {
        $hr = $this->user(Role::HR);
        $teacher = $this->user(Role::TEACHER);

        $this->assertTrue($hr->can('createFor', [StaffCertificate::class, $teacher]));
    }

    public function test_hr_cannot_add_certificate_to_director(): void
    {
        $hr = $this->user(Role::HR);
        $director = $this->user(Role::DIRECTOR);

        $this->assertFalse($hr->can('createFor', [StaffCertificate::class, $director]));
    }

    public function test_archive_certificate_respects_protected_roles(): void
    {
        $hr = $this->user(Role::HR);
        $director = $this->user(Role::DIRECTOR);
        $teacher = $this->user(Role::TEACHER);

        $directorCert = StaffCertificate::create([
            'user_id' => $director->id,
            'school_id' => $director->school_id,
            'title' => 'Leadership Award',
            'is_active' => true,
        ]);
        $teacherCert = StaffCertificate::create([
            'user_id' => $teacher->id,
            'school_id' => $teacher->school_id,
            'title' => 'Teaching Diploma',
            'is_active' => true,
        ]);

        $this->assertFalse($hr->can('archive', $directorCert));
        $this->assertTrue($hr->can('archive', $teacherCert));
    }

    // ---------- Leaves ----------

    public function test_teacher_can_request_own_leave(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $this->assertTrue($teacher->can('createFor', [StaffLeave::class, $teacher]));
    }

    public function test_hr_can_request_leave_on_behalf_of_teacher(): void
    {
        $hr = $this->user(Role::HR);
        $teacher = $this->user(Role::TEACHER);
        $this->assertTrue($hr->can('createFor', [StaffLeave::class, $teacher]));
    }

    public function test_hr_cannot_request_leave_on_behalf_of_director(): void
    {
        $hr = $this->user(Role::HR);
        $director = $this->user(Role::DIRECTOR);
        $this->assertFalse($hr->can('createFor', [StaffLeave::class, $director]));
    }

    public function test_teacher_cannot_request_leave_for_other_teacher(): void
    {
        $a = $this->user(Role::TEACHER);
        $b = $this->user(Role::TEACHER);
        $this->assertFalse($a->can('createFor', [StaffLeave::class, $b]));
    }

    public function test_hr_can_approve_teacher_leave(): void
    {
        $hr = $this->user(Role::HR);
        $teacher = $this->user(Role::TEACHER);

        $leave = $this->service()->requestLeave($teacher, $teacher, [
            'type' => 'annual',
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertTrue($hr->can('decide', $leave));
        $this->service()->approveLeave($hr, $leave);
        $this->assertSame(StaffLeave::STATUS_APPROVED, $leave->fresh()->status);
    }

    public function test_hr_cannot_decide_on_directors_leave(): void
    {
        $hr = $this->user(Role::HR);
        $director = $this->user(Role::DIRECTOR);

        $leave = $this->service()->requestLeave($director, $director, [
            'type' => 'annual',
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertFalse($hr->can('decide', $leave));
    }

    public function test_director_can_decide_on_directors_leave(): void
    {
        $d1 = $this->user(Role::DIRECTOR);
        $d2 = $this->user(Role::DIRECTOR);

        $leave = $this->service()->requestLeave($d1, $d1, [
            'type' => 'annual',
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertTrue($d2->can('decide', $leave));
    }

    public function test_user_cannot_approve_own_leave(): void
    {
        $hr = $this->user(Role::HR);

        $leave = $this->service()->requestLeave($hr, $hr, [
            'type' => 'annual',
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertFalse($hr->can('decide', $leave));
    }

    public function test_leave_cannot_be_decided_twice(): void
    {
        $hr = $this->user(Role::HR);
        $teacher = $this->user(Role::TEACHER);

        $leave = $this->service()->requestLeave($teacher, $teacher, [
            'type' => 'annual',
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(3)->toDateString(),
        ]);

        $this->service()->approveLeave($hr, $leave);
        $leave->refresh();

        $this->assertFalse($hr->can('decide', $leave));
        $this->assertFalse($hr->can('cancel', $leave));
    }

    public function test_requester_can_cancel_own_pending_leave(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $leave = $this->service()->requestLeave($teacher, $teacher, [
            'type' => 'annual',
            'starts_on' => now()->addDay()->toDateString(),
            'ends_on' => now()->addDays(3)->toDateString(),
        ]);

        $this->assertTrue($teacher->can('cancel', $leave));
    }

    // ---------- HTTP smoke ----------

    public function test_hr_directory_loads_for_hr_user(): void
    {
        $hr = $this->user(Role::HR);
        $teacher = $this->user(Role::TEACHER);

        $this->actingAs($hr)
            ->get(route('hr.index'))
            ->assertOk()
            ->assertSee($teacher->fullName());
    }

    public function test_teacher_cannot_list_staff_directory(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $this->actingAs($teacher)->get(route('hr.index'))->assertForbidden();
    }

    public function test_hr_profile_update_happy_path(): void
    {
        $hr = $this->user(Role::HR);
        $teacher = $this->user(Role::TEACHER);

        $this->actingAs($hr)
            ->post(route('hr.profile.update', $teacher), [
                'employee_no' => 'E-42',
                'employment_type' => 'permanent',
                'nhif_number' => 'NHIF-XYZ',
            ])
            ->assertRedirect(route('hr.show', $teacher));

        $this->assertDatabaseHas('staff_profiles', [
            'user_id' => $teacher->id,
            'employee_no' => 'E-42',
            'nhif_number' => 'NHIF-XYZ',
        ]);
    }

    public function test_hr_profile_update_blocked_for_director(): void
    {
        $hr = $this->user(Role::HR);
        $director = $this->user(Role::DIRECTOR);

        $this->actingAs($hr)
            ->post(route('hr.profile.update', $director), [
                'employee_no' => 'SHOULD-NOT-SAVE',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('staff_profiles', [
            'user_id' => $director->id,
        ]);
    }

    public function test_leave_lifecycle_via_http(): void
    {
        $teacher = $this->user(Role::TEACHER);
        $hr = $this->user(Role::HR);

        // Teacher requests own leave.
        $this->actingAs($teacher)
            ->post(route('hr.leaves.store', $teacher), [
                'type' => 'annual',
                'starts_on' => now()->addDay()->toDateString(),
                'ends_on' => now()->addDays(3)->toDateString(),
                'reason' => 'Rest',
            ])
            ->assertRedirect();

        $leave = StaffLeave::where('user_id', $teacher->id)->firstOrFail();
        $this->assertSame('pending', $leave->status);

        // HR approves.
        $this->actingAs($hr)
            ->post(route('hr.leaves.approve', $leave))
            ->assertRedirect();

        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame($hr->id, $leave->fresh()->decided_by);
    }
}
