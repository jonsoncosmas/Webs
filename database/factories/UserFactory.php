<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $first = fake()->firstName();
        $middle = fake()->firstName();
        $last = fake()->lastName();

        return [
            'school_id' => null,
            'role_id' => null,
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'username' => strtolower($first.' '.$middle.' '.Str::random(4)),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
            'remember_token' => Str::random(10),
        ];
    }
}
