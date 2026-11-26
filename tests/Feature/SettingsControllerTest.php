<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_choose_usd_and_save_the_exchange_rate(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertSee('Курс обмена: сом за 1 USD')
            ->assertSee('data-currency-select', false);

        $this->patch(route('admin.settings.profitability.update'), [
            'currency' => 'USD',
            'exchange_rate' => '90.5000',
        ])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHas('success', 'Настройки валюты рентабельности сохранены.');

        $this->assertDatabaseHas('site_settings', [
            'id' => 1,
            'profitability_currency' => 'USD',
            'kgs_per_usd' => '90.5000',
        ]);
    }

    public function test_switching_back_to_kgs_does_not_discard_the_saved_exchange_rate(): void
    {
        $admin = User::factory()->admin()->create();
        SiteSetting::query()->findOrFail(1)->update([
            'profitability_currency' => 'USD',
            'kgs_per_usd' => '90.5000',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.settings.profitability.update'), [
                'currency' => 'KGS',
            ])
            ->assertRedirect(route('admin.settings'));

        $this->assertDatabaseHas('site_settings', [
            'id' => 1,
            'profitability_currency' => 'KGS',
            'kgs_per_usd' => '90.5000',
        ]);
    }

    public function test_usd_selection_requires_a_valid_exchange_rate(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.settings'))
            ->patch(route('admin.settings.profitability.update'), [
                'currency' => 'USD',
            ])
            ->assertRedirect(route('admin.settings'))
            ->assertSessionHasErrors('exchange_rate');

        $this->assertDatabaseHas('site_settings', [
            'id' => 1,
            'profitability_currency' => 'KGS',
            'kgs_per_usd' => '89.0000',
        ]);
    }

    public function test_non_admin_cannot_change_profitability_currency(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member, 'admin')
            ->patch(route('admin.settings.profitability.update'), [
                'currency' => 'USD',
                'exchange_rate' => '89',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('site_settings', [
            'id' => 1,
            'profitability_currency' => 'KGS',
        ]);
    }
}
