<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\UserSocials;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSocials>
 */
class UserSocialsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => function() {
                return User::inRandomOrder()->first()->id;
            },
            'provider' => $this->faker->randomElement(array_keys(UserSocials::fGetProviders())),
            'provider_id' => $this->faker->uuid,
        ];
    }
}
