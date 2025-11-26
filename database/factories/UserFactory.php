<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = $this->getStaticUser();

        return [
            'uuid' => fake()->uuid(),
            'site_id' => 1,
            'fullname' => $user['fullname'],
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => 1,
            'role_id' => NULL,
            'created_by' => 1, // TODO: what is the value for this?
            'updated_by' => 1 // TODO: what is the value for this?
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Pull the next static module record.
     */
    private function getStaticUser()
    {
        static $users = [
            [
                'fullname' => 'Janice Laurel',
                'username' => 'jlaurel',
                'email' => 'jlaurel@telcosphere.co'
            ],
            [
                'fullname' => 'Rodel Laurel',
                'username' => 'rlaurel',
                'email' => 'rlaurel@telcosphere.co'
            ],
            [
                'fullname' => 'Arnie Abante',
                'username' => 'aabante',
                'email' => 'aabante@telcoshpere.co'
            ],
            [
                'fullname' => 'Melanie Abante',
                'username' => 'mabante',
                'email' => 'mabante@telcoshpere.co'
            ],
            [
                'fullname' => 'Elmar Malazarte',
                'username' => 'emalazarte',
                'email' => 'emalazarte@telcosphere.co'
            ],
            [
                'fullname' => 'Jeb Saldariega',
                'username' => 'jsaldariega',
                'email' => 'jsaldariega@telcosphere.co'
            ]
        ];

        return array_shift($users);
    }
}
