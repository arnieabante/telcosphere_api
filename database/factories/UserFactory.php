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
            'site_id' => $user['site_id'],
            'fullname' => $user['fullname'],
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => 1,
            'role_id' => null,
            'created_by' => 1,
            'updated_by' => 1
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
     * Pull the next static user record, fallback to Faker if exhausted.
     */
    private function getStaticUser()
    {
        static $users = [
            ['site_id' => 1, 'fullname' => 'Janice Laurel', 'username' => 'jlaurel', 'email' => 'jlaurel@telcosphere.co'],
            ['site_id' => 1, 'fullname' => 'Rodel Laurel', 'username' => 'rlaurel', 'email' => 'rlaurel@telcosphere.co'],
            ['site_id' => 1, 'fullname' => 'Arnie Abante', 'username' => 'aabante', 'email' => 'aabante@telcosphere.co'],
            ['site_id' => 1, 'fullname' => 'Melanie Abante', 'username' => 'mabante', 'email' => 'mabante@telcosphere.co'],
            ['site_id' => 1, 'fullname' => 'Elmar Malazarte', 'username' => 'emalazarte', 'email' => 'emalazarte@telcosphere.co'],
            ['site_id' => 1, 'fullname' => 'Jeb Saldariega', 'username' => 'jsaldariega', 'email' => 'jsaldariega@telcosphere.co'],
            ['site_id' => 2, 'fullname' => 'Janice Laurel', 'username' => 'jlaurel2', 'email' => 'jlaurel2@telcosphere.co']
        ];

        $user = array_shift($users);

        // Fallback to faker if static users are exhausted
        if (!$user) {
            $user = [
                'site_id' => rand(1, 2),
                'fullname' => fake()->name(),
                'username' => fake()->unique()->userName(),
                'email' => fake()->unique()->safeEmail(),
            ];
        }

        return $user;
    }
}
