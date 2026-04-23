<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Role::catalog() as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role,
            );
        }
    }
}
