<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_director_outranks_school_admin(): void
    {
        $director = $this->makeUserWithRole(Role::DIRECTOR);
        $schoolAdmin = $this->makeUserWithRole(Role::SCHOOL_ADMIN);

        $this->assertTrue($director->outranks($schoolAdmin));
        $this->assertFalse($schoolAdmin->outranks($director));
    }

    public function test_system_admin_outranks_all(): void
    {
        $sys = $this->makeUserWithRole(Role::SYSTEM_ADMIN);
        foreach ([Role::DIRECTOR, Role::SCHOOL_ADMIN, Role::TEACHER, Role::STUDENT] as $slug) {
            $other = $this->makeUserWithRole($slug);
            $this->assertTrue($sys->outranks($other), "system_admin should outrank {$slug}");
        }
    }

    public function test_default_username_and_password_conventions(): void
    {
        $this->assertSame('asha juma', User::defaultUsernameFor('Asha', 'Juma'));
        $this->assertSame('asha', User::defaultUsernameFor('Asha', null));
        $this->assertSame('MOLLEL', User::defaultPasswordFor('Mollel'));
    }

    private function makeUserWithRole(string $slug): User
    {
        $role = Role::where('slug', $slug)->firstOrFail();

        return User::create([
            'role_id' => $role->id,
            'first_name' => 'T',
            'last_name' => 'U'.$role->id,
            'username' => 't-'.$role->id.'-'.uniqid(),
            'password' => bcrypt('x'),
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
