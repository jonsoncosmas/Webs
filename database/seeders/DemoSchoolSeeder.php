<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds one demo school + a handful of users spanning the role hierarchy so
 * the exam workflow can be tested end-to-end without manual onboarding.
 *
 * Default passwords follow the brief: LASTNAME in uppercase.
 * must_change_password is FALSE here so demo flows aren't gated by the
 * forced password change screen.
 */
class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        $pkg = Package::where('slug', Package::PRO)->first();
        $school = School::updateOrCreate(
            ['slug' => 'demo-secondary'],
            [
                'name' => 'Demo Secondary School',
                'package_id' => $pkg?->id,
                'curriculum' => 'NECTA',
                'region' => 'Arusha',
                'district' => 'Arusha City',
                'status' => 'active',
            ],
        );

        $people = [
            ['Asha', 'Juma', 'Mollel', Role::DIRECTOR],
            ['John', 'Peter', 'Mwakasege', Role::SCHOOL_ADMIN],
            ['Grace', 'Amani', 'Kimaro', Role::ACADEMIC_HEAD],
            ['David', 'Elias', 'Lema', Role::EXAMINATION_MASTER],
            ['Rehema', 'Saidi', 'Nyambura', Role::TEACHER],
            ['Mary', 'Joseph', 'Haule', Role::HR],
        ];

        foreach ($people as [$first, $middle, $last, $slug]) {
            $role = Role::where('slug', $slug)->firstOrFail();

            $user = User::updateOrCreate(
                ['username' => User::defaultUsernameFor($first, $middle)],
                [
                    'school_id' => $school->id,
                    'role_id' => $role->id,
                    'first_name' => $first,
                    'middle_name' => $middle,
                    'last_name' => $last,
                    'email' => strtolower($first.'.'.$last.'@demo.somalite.test'),
                    'password' => Hash::make(User::defaultPasswordFor($last)),
                    'must_change_password' => false,
                    'status' => User::STATUS_ACTIVE,
                ],
            );

            if ($slug === Role::DIRECTOR && empty($school->director_id)) {
                $school->forceFill(['director_id' => $user->id])->save();
            }
        }
    }
}
