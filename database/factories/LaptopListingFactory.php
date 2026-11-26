<?php

namespace Database\Factories;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaptopListing>
 */
class LaptopListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'search_profile_id' => SearchProfile::factory(),
            'ebay_item_id' => fake()->unique()->uuid(),
            'title' => fake()->sentence(4),
            'item_url' => fake()->url(),
            'price' => fake()->randomFloat(2, 100, 1000),
            'total_price' => fake()->randomFloat(2, 100, 1000),
            'seen_at' => now(),
        ];
    }
}
