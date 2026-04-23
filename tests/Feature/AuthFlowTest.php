<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PackageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PackageSeeder::class);
    }

    private function makeUser(string $firstName, ?string $middleName, string $lastName, string $roleSlug = Role::TEACHER): User
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return User::create([
            'role_id' => $role->id,
            'school_id' => null,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'username' => User::defaultUsernameFor($firstName, $middleName),
            'password' => Hash::make(User::defaultPasswordFor($lastName)),
            'must_change_password' => true,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_user_can_login_with_default_credentials_and_is_forced_to_change_password(): void
    {
        $this->makeUser('Asha', 'Juma', 'Mollel');

        $res = $this->post('/login', [
            'username' => 'asha juma',
            'password' => 'MOLLEL',
        ]);

        $res->assertRedirect(route('password.change'));
        $this->assertAuthenticated();
    }

    public function test_authenticated_user_with_unchanged_password_is_redirected_to_change_page(): void
    {
        $user = $this->makeUser('Juma', 'Rajabu', 'Kibona');

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('password.change'));
    }

    public function test_user_can_update_password_and_reach_dashboard(): void
    {
        $user = $this->makeUser('Juma', 'Rajabu', 'Kibona');

        $this->actingAs($user)
            ->post('/password/change', [
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertFalse($user->fresh()->must_change_password);

        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = $this->makeUser('John', 'Peter', 'Doe');
        $user->update(['status' => User::STATUS_SUSPENDED, 'must_change_password' => false, 'password' => Hash::make('Secret123')]);

        $this->post('/login', [
            'username' => 'john peter',
            'password' => 'Secret123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_invalid_credentials_fail(): void
    {
        $this->makeUser('Asha', 'Juma', 'Mollel');
        $this->post('/login', [
            'username' => 'asha juma',
            'password' => 'WRONG',
        ])->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
