<?php

namespace Tests\Unit;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Services\ListingValueAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingValueAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_uses_total_price_and_explains_each_scoring_factor(): void
    {
        $profile = SearchProfile::factory()->create();
        $attributes = [
            'condition' => 'USED',
            'currency' => 'USD',
            'cpu' => 'Intel Core i7',
            'ram_gb' => 16,
            'storage' => '1 TB SSD',
            'screen' => '15.6 in',
            'gpu' => 'NVIDIA GeForce RTX 4060',
        ];
        $listing = LaptopListing::factory()->create([
            ...$attributes,
            'search_profile_id' => $profile->id,
            'total_price' => 800,
            'shipping_price' => 40,
        ]);

        foreach ([900, 1000, 1100] as $price) {
            LaptopListing::factory()->create([
                ...$attributes,
                'search_profile_id' => $profile->id,
                'total_price' => $price,
                'shipping_price' => 0,
            ]);
        }

        $assessment = app(ListingValueAssessmentService::class)
            ->assessPage(collect([$listing]))[$listing->id];

        $this->assertSame(94, $assessment['score']);
        $this->assertSame(3, $assessment['comparison_count']);
        $this->assertSame([60, 9, 15, 10], array_column($assessment['factors'], 'score'));
        $this->assertStringContainsString('На 20,0% ниже медианы', $assessment['factors'][0]['explanation']);
        $this->assertStringContainsString('Медиана: USD 1 000.00', $assessment['factors'][0]['explanation']);
        $this->assertStringContainsString('Доставка составляет 5,0%', $assessment['factors'][3]['explanation']);
    }

    public function test_assessment_is_unavailable_when_fewer_than_three_similar_products_exist(): void
    {
        $profile = SearchProfile::factory()->create();
        $attributes = [
            'search_profile_id' => $profile->id,
            'condition' => 'USED',
            'currency' => 'USD',
            'cpu' => 'Intel Core i7',
            'ram_gb' => 16,
            'storage' => '1 TB SSD',
            'screen' => '15.6 in',
            'gpu' => 'NVIDIA GeForce RTX 4060',
        ];
        $listing = LaptopListing::factory()->create($attributes);
        LaptopListing::factory()->count(2)->create($attributes);
        LaptopListing::factory()->create([
            ...$attributes,
            'condition' => 'NEW',
        ]);
        LaptopListing::factory()->create([
            ...$attributes,
            'search_profile_id' => SearchProfile::factory(),
        ]);

        $assessment = app(ListingValueAssessmentService::class)
            ->assessPage(collect([$listing]))[$listing->id];

        $this->assertNull($assessment['score']);
        $this->assertSame(2, $assessment['comparison_count']);
    }
}
