<?php

namespace Tests\Feature\Admin;

use App\Models\ProfitabilityPart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitabilityPartControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_hide_a_priced_part(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.profitability-parts.index'))
            ->assertOk()
            ->assertSee('Запчасти для расчёта')
            ->assertSee('Цена, сом');

        $this->post(route('admin.profitability-parts.store'), [
            'name' => 'Батарея',
            'price' => '35.50',
        ])->assertRedirect(route('admin.profitability-parts.index'))
            ->assertSessionHas('success', 'Запчасть добавлена.');

        $part = ProfitabilityPart::query()->where('name', 'Батарея')->firstOrFail();
        $this->assertSame('35.50', $part->price);
        $this->assertTrue($part->is_active);

        $this->patch(route('admin.profitability-parts.update', $part), [
            'name' => 'Батарея усиленная',
            'price' => '45.00',
            'is_active' => 1,
        ])->assertRedirect(route('admin.profitability-parts.index'));

        $this->assertSame('Батарея усиленная', $part->fresh()->name);
        $this->assertSame('45.00', $part->fresh()->price);

        $this->delete(route('admin.profitability-parts.destroy', $part))
            ->assertRedirect(route('admin.profitability-parts.index'));

        $this->assertFalse($part->fresh()->is_active);
        $this->assertDatabaseHas('profitability_parts', ['id' => $part->id, 'name' => 'Батарея усиленная']);
    }

    public function test_non_admin_cannot_manage_the_part_catalog(): void
    {
        $member = User::factory()->create();
        $part = ProfitabilityPart::query()->create([
            'name' => 'Зарядное устройство',
            'price' => '20.00',
        ]);

        $this->actingAs($member, 'admin')
            ->get(route('admin.profitability-parts.index'))
            ->assertForbidden();

        $this->actingAs($member, 'admin')
            ->post(route('admin.profitability-parts.store'), [
                'name' => 'Новая запчасть',
                'price' => '10.00',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('profitability_parts', 1);
        $this->assertDatabaseHas('profitability_parts', ['id' => $part->id, 'is_active' => true]);
    }

    public function test_invalid_part_price_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.profitability-parts.index'))
            ->post(route('admin.profitability-parts.store'), [
                'name' => 'SSD',
                'price' => '-1.00',
            ])
            ->assertRedirect(route('admin.profitability-parts.index'))
            ->assertSessionHasErrors('price');

        $this->assertDatabaseCount('profitability_parts', 0);
    }
}
