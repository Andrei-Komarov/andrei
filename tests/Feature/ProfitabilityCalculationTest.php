<?php

namespace Tests\Feature;

use App\Models\LaptopListing;
use App\Models\ProfitabilityCalculation;
use App\Models\ProfitabilityPart;
use App\Models\SearchProfile;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitabilityCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculator_lists_only_the_current_users_products(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListingFor($user, ['title' => 'My eBay laptop']);
        ProfitabilityPart::query()->create([
            'name' => 'Батарея',
            'price' => '20.00',
        ]);
        ProfitabilityPart::query()->create([
            'name' => 'Скрытый аккумулятор',
            'price' => '30.00',
            'is_active' => false,
        ]);
        $otherUser = User::factory()->create();
        $this->createListingFor($otherUser, ['title' => 'Private eBay laptop']);

        $this->actingAs($user)
            ->get(route('profitability.index'))
            ->assertOk()
            ->assertSee('My eBay laptop')
            ->assertDontSee('Private eBay laptop')
            ->assertSee('Рассчитать и сохранить')
            ->assertSee('История расчётов')
            ->assertSee('Батарея')
            ->assertDontSee('Скрытый аккумулятор')
            ->assertSee('Цена покупки, сом')
            ->assertSee('Доставка по США, сом')
            ->assertSee('Затраты на рекламу, сом')
            ->assertSee('Цена продажи, сом')
            ->assertSee('20.00 сом')
            ->assertSee('name="part_ids[]"', false)
            ->assertSee('data-add-part', false);
    }

    public function test_listing_options_include_price_currency_and_conversion_rate_for_autofill(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListingFor($user, [
            'price' => '450.00',
            'shipping_price' => '25.50',
            'total_price' => '475.50',
            'currency' => 'USD',
        ]);
        SiteSetting::query()->findOrFail(1)->update([
            'profitability_currency' => 'KGS',
            'kgs_per_usd' => '90.5000',
        ]);

        $this->actingAs($user)
            ->get(route('profitability.index'))
            ->assertOk()
            ->assertSee('data-currency="KGS" data-kgs-per-usd="90.5000"', false)
            ->assertSee('data-price="475.50"', false)
            ->assertSee('data-shipping-price="25.50"', false)
            ->assertSee('data-currency="USD"', false);
    }

    public function test_valid_calculation_is_saved_with_expected_profit_roi_and_margin(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListingFor($user, ['title' => 'ThinkPad for resale']);
        $battery = ProfitabilityPart::query()->create([
            'name' => 'Батарея',
            'price' => '20.00',
        ]);
        $charger = ProfitabilityPart::query()->create([
            'name' => 'Зарядное устройство',
            'price' => '15.00',
        ]);

        $this->actingAs($user)
            ->post(route('profitability.store'), [
                'listing_id' => $listing->id,
                'purchase_price' => '300.00',
                'additional_costs' => '25.00',
                'advertising_costs' => '10.00',
                'customs_cost' => '40.00',
                'sale_price' => '500.00',
                'part_ids' => [$battery->id, $charger->id],
                'part_prices' => ['0.01', '0.01'],
            ])
            ->assertRedirect(route('profitability.index'))
            ->assertSessionHas('success', 'Расчёт рентабельности сохранён.');

        $this->assertDatabaseHas('profitability_calculations', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'listing_title' => 'ThinkPad for resale',
            'currency_code' => 'KGS',
            'additional_costs' => '25.00',
            'advertising_costs' => '10.00',
            'customs_cost' => '40.00',
            'total_cost' => '410.00',
            'commission_amount' => '0.00',
            'net_profit' => '90.00',
            'roi_percentage' => '21.95',
            'profit_margin_percentage' => '18.00',
        ]);

        $calculation = ProfitabilityCalculation::query()->firstOrFail();
        $this->assertSame([
            ['id' => $battery->id, 'name' => 'Батарея', 'price' => '20.00'],
            ['id' => $charger->id, 'name' => 'Зарядное устройство', 'price' => '15.00'],
        ], $calculation->parts_snapshot);

        $this->get(route('profitability.index'))
            ->assertOk()
            ->assertSee('ThinkPad for resale')
            ->assertSee('Батарея')
            ->assertSee('90.00 сом')
            ->assertSee('21.95%')
            ->assertSee('18.00%');
    }

    public function test_legacy_calculations_remain_labeled_in_usd(): void
    {
        $user = User::factory()->create();
        $calculation = $this->createCalculationFor($user);

        $this->actingAs($user)
            ->get(route('profitability.index'))
            ->assertSee('300.00 USD')
            ->assertSee('500.00 USD')
            ->assertDontSee('300.00 сом');

        $this->assertSame('USD', $calculation->fresh()->currency_code);
    }

    public function test_usd_calculation_converts_catalog_parts_and_saves_the_currency_snapshot(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListingFor($user);
        $part = ProfitabilityPart::query()->create([
            'name' => 'Батарея',
            'price' => '890.00',
        ]);
        SiteSetting::query()->findOrFail(1)->update([
            'profitability_currency' => 'USD',
            'kgs_per_usd' => '89.0000',
        ]);

        $this->actingAs($user)
            ->get(route('profitability.index'))
            ->assertOk()
            ->assertSee('Цена покупки, USD')
            ->assertSee('Батарея — 10.00 USD')
            ->assertSee('data-currency="USD"', false);

        $this->post(route('profitability.store'), [
            'listing_id' => $listing->id,
            'purchase_price' => '100.00',
            'additional_costs' => '10.00',
            'advertising_costs' => '0.00',
            'customs_cost' => '0.00',
            'sale_price' => '200.00',
            'part_ids' => [$part->id],
            'part_prices' => ['0.01'],
        ])->assertRedirect(route('profitability.index'));

        $calculation = ProfitabilityCalculation::query()->firstOrFail();
        $this->assertSame('USD', $calculation->currency_code);
        $this->assertSame([
            ['id' => $part->id, 'name' => 'Батарея', 'price' => '10.00'],
        ], $calculation->parts_snapshot);
        $this->assertSame('120.00', $calculation->total_cost);
        $this->assertSame('80.00', $calculation->net_profit);
    }

    public function test_inactive_parts_cannot_be_added_to_a_calculation(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListingFor($user);
        $part = ProfitabilityPart::query()->create([
            'name' => 'Скрытая запчасть',
            'price' => '50.00',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->post(route('profitability.store'), [
                'listing_id' => $listing->id,
                'purchase_price' => '300.00',
                'additional_costs' => '25.00',
                'advertising_costs' => '0.00',
                'customs_cost' => '0.00',
                'sale_price' => '500.00',
                'part_ids' => [$part->id],
            ])
            ->assertSessionHasErrors('part_ids.0');

        $this->assertDatabaseCount('profitability_calculations', 0);
    }

    public function test_customs_cost_cannot_be_negative(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListingFor($user);

        $this->actingAs($user)
            ->post(route('profitability.store'), [
                'listing_id' => $listing->id,
                'purchase_price' => '300.00',
                'additional_costs' => '25.00',
                'advertising_costs' => '0.00',
                'customs_cost' => '-0.01',
                'sale_price' => '500.00',
            ])
            ->assertSessionHasErrors('customs_cost');

        $this->assertDatabaseCount('profitability_calculations', 0);
    }

    public function test_amounts_with_more_than_two_decimal_places_are_rejected(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListingFor($user);

        $this->actingAs($user)
            ->post(route('profitability.store'), [
                'listing_id' => $listing->id,
                'purchase_price' => '300.001',
                'additional_costs' => '25.00',
                'advertising_costs' => '0.00',
                'customs_cost' => '0.00',
                'sale_price' => '500.00',
            ])
            ->assertSessionHasErrors('purchase_price');

        $this->assertDatabaseCount('profitability_calculations', 0);
    }

    public function test_calculation_cannot_use_another_users_listing(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherListing = $this->createListingFor($otherUser);

        $this->actingAs($user)
            ->post(route('profitability.store'), [
                'listing_id' => $otherListing->id,
                'purchase_price' => '300.00',
                'additional_costs' => '25.00',
                'advertising_costs' => '0.00',
                'customs_cost' => '0.00',
                'sale_price' => '500.00',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('profitability_calculations', 0);
    }

    public function test_user_can_delete_only_their_own_calculation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $userCalculation = $this->createCalculationFor($user);
        $otherCalculation = $this->createCalculationFor($otherUser);

        $this->actingAs($user)
            ->delete(route('profitability.destroy', $userCalculation))
            ->assertRedirect(route('profitability.index'))
            ->assertSessionHas('success', 'Расчёт удалён из истории.');

        $this->assertDatabaseMissing('profitability_calculations', ['id' => $userCalculation->id]);
        $this->assertDatabaseHas('profitability_calculations', ['id' => $otherCalculation->id]);

        $this->delete(route('profitability.destroy', $otherCalculation))->assertNotFound();
        $this->assertDatabaseHas('profitability_calculations', ['id' => $otherCalculation->id]);
    }

    public function test_zero_cost_and_sale_price_produce_zero_roi_and_margin(): void
    {
        $user = User::factory()->create();
        $listing = $this->createListingFor($user);

        $this->actingAs($user)
            ->post(route('profitability.store'), [
                'listing_id' => $listing->id,
                'purchase_price' => '0.00',
                'additional_costs' => '0.00',
                'advertising_costs' => '0.00',
                'customs_cost' => '0.00',
                'sale_price' => '0.00',
                'part_ids' => [''],
            ])
            ->assertRedirect(route('profitability.index'));

        $this->assertDatabaseHas('profitability_calculations', [
            'user_id' => $user->id,
            'total_cost' => '0.00',
            'roi_percentage' => '0.00',
            'profit_margin_percentage' => '0.00',
        ]);
    }

    public function test_guest_is_redirected_to_login_from_calculator(): void
    {
        $this->get(route('profitability.index'))
            ->assertRedirect(route('login'));
    }

    private function createListingFor(User $user, array $attributes = []): LaptopListing
    {
        $profile = SearchProfile::factory()->for($user)->create();

        return LaptopListing::factory()->for($profile)->create($attributes);
    }

    private function createCalculationFor(User $user): ProfitabilityCalculation
    {
        return $user->profitabilityCalculations()->create([
            'listing_title' => 'Saved laptop',
            'purchase_price' => '300.00',
            'additional_costs' => '25.00',
            'advertising_costs' => '0.00',
            'customs_cost' => '0.00',
            'sale_price' => '500.00',
            'commission_percent' => '10.00',
            'total_cost' => '325.00',
            'commission_amount' => '50.00',
            'net_profit' => '125.00',
            'roi_percentage' => '38.46',
            'profit_margin_percentage' => '25.00',
        ]);
    }
}
