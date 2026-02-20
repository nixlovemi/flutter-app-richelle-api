<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserSocials;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedUsers();
    }

    private function seedUsers(): void
    {
        $providers = array_keys(UserSocials::fGetProviders());

        User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        // One user with two providers
        $user = User::factory()->create([
            'email' => 'user_with_two_providers@example.com',
        ]);
        foreach ($providers as $provider) {
            UserSocials::factory()->create([
                'user_id' => $user->id,
                'provider' => $provider,
            ]);
        }

        // Create three users that can have a provider (but not necessarily)
        User::factory(3)->create()->each(function ($user) use ($providers) {
            if (rand(0, 1)) {
                UserSocials::factory()->create([
                    'user_id' => $user->id,
                    'provider' => $providers[array_rand($providers)],
                ]);
            }
        });
    }
}
