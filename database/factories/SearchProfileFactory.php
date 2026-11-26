<?php

namespace Database\Factories;

use App\Models\SearchProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchProfile>
 */
class SearchProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fn () => auth('web')->id() ?? auth('admin')->id() ?? User::factory(),
            'name' => fake()->words(2, true),
            'query' => 'laptop',
            'active' => true,
        ];
    }
}
