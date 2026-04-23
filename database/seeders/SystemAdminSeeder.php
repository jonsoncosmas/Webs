<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('slug', Role::SYSTEM_ADMIN)->firstOrFail();

        $firstName = env('SEED_ADMIN_FIRST_NAME', 'System');
        $middleName = env('SEED_ADMIN_MIDDLE_NAME', 'Platform');
        $lastName = env('SEED_ADMIN_LAST_NAME', 'Admin');

        User::updateOrCreate(
            ['username' => User::defaultUsernameFor($firstName, $middleName)],
            [
                'role_id' => $role->id,
                'school_id' => null,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => env('SEED_ADMIN_EMAIL', 'admin@somalite.test'),
                'password' => Hash::make(User::defaultPasswordFor($lastName)),
                'must_change_password' => true,
                'status' => User::STATUS_ACTIVE,
            ],
        );
    }
}
