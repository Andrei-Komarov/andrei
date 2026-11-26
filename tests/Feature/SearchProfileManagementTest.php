<?php

namespace Tests\Feature;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\SearchProfileActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $this->actingAs($admin, 'admin');
    }

    public function test_search_profile_create_page_displays_the_laptop_search_filters(): void
    {
        $response = $this->get(route('profiles.create'));
        $response
            ->assertOk()
            ->assertSee('Finder')
            ->assertSee('Выйти')
            ->assertDontSee('Верхняя навигация')
            ->assertSee('Главное меню')
            ->assertSee('Поиск товаров на eBay')
            ->assertSee('Новый критерий поиска')
            ->assertSee('name="name"', false)
            ->assertSee('name="query"', false)
            ->assertSee('name="schedule_time"', false)
            ->assertSee('name="schedule_interval_hours"', false)
            ->assertSee('08:00')
            ->assertSee('name="min_price"', false)
            ->assertSee('name="max_price"', false)
            ->assertSee('name="price_alert_threshold"', false)
            ->assertSee('name="notify_new_listings"', false)
            ->assertSee('name="notify_price_drops"', false)
            ->assertSee('name="notify_search_errors"', false)
            ->assertSee('name="notify_profile_matches"', false)
            ->assertSee('name="notify_shared_price_changes"', false)
            ->assertSee('name="notification_frequency"', false)
            ->assertSee('name="brand[]"', false)
            ->assertSee('name="processor[]"', false)
            ->assertSee('name="ram_size[]"', false)
            ->assertSee('name="storage_capacity[]"', false)
            ->assertSee('name="screen_size[]"', false)
            ->assertSee('name="gpu[]"', false)
            ->assertSee('multiple', false)
            ->assertSee('data-any-option', false)
            ->assertSee('value="__any__"', false)
            ->assertSee('Любой')
            ->assertSee('name="operating_system"', false);
        $this->assertSame(6, substr_count($response->getContent(), 'size="10"'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('listings.index'));

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertDontSee('Новый критерий поиска')
            ->assertDontSee('name="min_price"', false)
            ->assertDontSee('name="brand"', false)
            ->assertSee(route('profiles.create'))
            ->assertSee(route('profiles.history'))
            ->assertSee('<table', false);
    }

    public function test_filter_options_are_loaded_from_ebay_laptop_taxonomy(): void
    {
        $this->fakeLaptopTaxonomy();

        $response = $this->getJson(route('profiles.filter-options'))
            ->assertOk()
            ->assertJsonPath('filters.brand.aspect_name', 'Brand')
            ->assertJsonPath('filters.operating_system.values.0', 'Windows 11');

        $this->assertContains('Lenovo', $response->json('filters.brand.values'));
        $this->assertContains('Apple', $response->json('filters.brand.values'));
        $this->assertContains('Intel Core i7', $response->json('filters.processor.values'));
        $this->assertContains('Ryzen 7', $response->json('filters.processor.values'));

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'get_category_subtree')
            && str_contains($request->url(), 'category_id=175672'));
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'get_item_aspects_for_category')
            && str_contains($request->url(), 'category_id=177'));
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'get_item_aspects_for_category')
            && str_contains($request->url(), 'category_id=111422'));
    }

    public function test_profile_list_shows_existing_profiles_and_search_criteria(): void
    {
        SearchProfile::factory()->create([
            'name' => 'ThinkPad',
            'query' => 'ThinkPad T14',
            'condition' => 'USED',
            'max_price' => 500,
            'price_alert_threshold' => 450.50,
            'min_ram_gb' => 16,
        ]);

        $this->get(route('profiles.index'))
            ->assertOk()
            ->assertSee('ThinkPad')
            ->assertSee('ThinkPad T14')
            ->assertSee('USED')
            ->assertSee('$500')
            ->assertSee('16 GB')
            ->assertSee('data-analyze-filters', false)
            ->assertSee('data-table-key="profiles"', false)
            ->assertSee('data-column-key="query"', false)
            ->assertSee('Проверить фильтры')
            ->assertDontSee('Проверить строгость фильтров')
            ->assertSee('id="filter-analysis-dialog"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('aria-labelledby="filter-analysis-title"', false)
            ->assertSee('fixed left-1/2 top-1/2', false)
            ->assertSee('backdrop:bg-slate-900/60', false)
            ->assertSee('data-confirm-dialog-form', false)
            ->assertDontSee('onsubmit=', false)
            ->assertSee('Результат проверки фильтров');
    }

    public function test_profile_list_sorts_profiles_and_links_to_the_reverse_order(): void
    {
        SearchProfile::factory()->create(['name' => 'First profile', 'query' => 'Alpha query']);
        SearchProfile::factory()->create(['name' => 'Second profile', 'query' => 'Zulu query']);

        $response = $this->get(route('profiles.index', ['sort' => 'query_desc']))
            ->assertOk()
            ->assertSee(route('profiles.index', ['sort' => 'query_asc']));

        $this->assertSame(
            ['Second profile', 'First profile'],
            $response->viewData('profiles')->pluck('name')->all(),
        );
    }

    public function test_profile_history_sorts_all_activity_rows_by_profile_name(): void
    {
        $firstProfile = SearchProfile::factory()->create(['name' => 'Alpha profile']);
        $lastProfile = SearchProfile::factory()->create(['name' => 'Zulu profile']);
        foreach ([$lastProfile, $firstProfile] as $profile) {
            SearchProfileActivity::query()->create([
                'search_profile_id' => $profile->id,
                'user_id' => $profile->user_id,
                'profile_owner_id' => $profile->user_id,
                'profile_name' => $profile->name,
                'action' => 'created',
                'description' => 'Profile created.',
            ]);
        }

        $response = $this->get(route('profiles.history', ['sort' => 'profile_asc']))->assertOk();

        $this->assertSame(
            ['Alpha profile', 'Zulu profile'],
            $response->viewData('activities')->getCollection()->pluck('profile_name')->all(),
        );
    }

    public function test_profile_filter_analysis_compares_each_ebay_filter_and_recommends_relaxing_strict_criteria(): void
    {
        config([
            'services.ebay.client_id' => 'test-client',
            'services.ebay.client_secret' => 'test-secret',
        ]);
        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*buy/browse/*' => function (Request $request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $parameters);
                $filter = $parameters['filter'] ?? '';
                $total = match ($filter) {
                    'conditions:{USED}' => 70,
                    'price:[100..],priceCurrency:USD' => 50,
                    'price:[..500],priceCurrency:USD' => 90,
                    'conditions:{USED},price:[100..500],priceCurrency:USD' => 5,
                    default => 100,
                };

                return Http::response(['total' => $total]);
            },
        ]);
        $profile = SearchProfile::factory()->create([
            'query' => 'ThinkPad',
            'condition' => 'USED',
            'min_price' => 100,
            'max_price' => 500,
        ]);

        $this->postJson(route('profiles.analyze-filters', $profile))
            ->assertOk()
            ->assertJsonPath('baseline_count', 100)
            ->assertJsonPath('filtered_count', 5)
            ->assertJsonPath('filter_impacts.0.label', 'Минимальная цена')
            ->assertJsonPath('filter_impacts.0.result_count', 50)
            ->assertJsonPath('filter_impacts.0.reduction', 50)
            ->assertJsonPath('filter_impacts.1.label', 'Состояние')
            ->assertJsonPath('filter_impacts.2.label', 'Максимальная цена')
            ->assertJsonPath('recommendation', 'Фильтры сильно ограничивают выдачу. Проверьте, можно ли ослабить «Минимальная цена».');

        Http::assertSentCount(6);
    }

    public function test_profile_filter_analysis_is_available_only_to_the_profile_owner(): void
    {
        $profile = SearchProfile::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $this->postJson(route('profiles.analyze-filters', $profile))
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_profile_edit_form_contains_current_values(): void
    {
        $profile = SearchProfile::factory()->create([
            'name' => 'ThinkPad',
            'query' => 'ThinkPad T14',
            'schedule_time' => '12:30:00',
            'schedule_interval_hours' => 4,
            'condition' => 'USED',
            'min_price' => 300,
            'max_price' => 500,
            'price_alert_threshold' => 450.50,
            'brand' => 'Lenovo',
            'processor' => 'Intel Core i7',
            'ram_size' => '16 GB',
            'storage_capacity' => '512 GB SSD',
            'screen_size' => '15.6 in',
            'gpu' => 'NVIDIA GeForce RTX 4060',
            'operating_system' => 'Windows 11',
        ]);

        $this->get(route('profiles.edit', $profile))
            ->assertOk()
            ->assertSee('value="ThinkPad"', false)
            ->assertSee('value="ThinkPad T14"', false)
            ->assertSee('value="12:30"', false)
            ->assertSee('value="4" selected', false)
            ->assertSee('Каждые 4 ч.')
            ->assertSee('value="300"', false)
            ->assertSee('value="500"', false)
            ->assertSee('value="450.50"', false)
            ->assertSee('data-selected="[&quot;Lenovo&quot;]"', false)
            ->assertSee('data-selected="[&quot;Intel Core i7&quot;]"', false)
            ->assertSee('data-selected="[&quot;16 GB&quot;]"', false)
            ->assertSee('data-selected="[&quot;512 GB SSD&quot;]"', false)
            ->assertSee('data-selected="[&quot;15.6 in&quot;]"', false)
            ->assertSee('data-selected="[&quot;NVIDIA GeForce RTX 4060&quot;]"', false)
            ->assertSee('data-selected="[&quot;Windows 11&quot;]"', false);
    }

    public function test_profile_can_be_created_with_laptop_filters(): void
    {
        $this->fakeLaptopTaxonomy();

        $this->from(route('profiles.create'))->post(route('profiles.store'), [
            'name' => 'Gaming laptop',
            'query' => 'gaming laptop',
            'schedule_time' => '14:30',
            'schedule_interval_hours' => 4,
            'condition' => 'USED',
            'min_price' => 300,
            'max_price' => 1500,
            'price_alert_threshold' => 1200.50,
            'brand' => 'Lenovo',
            'processor' => 'Intel Core i7',
            'ram_size' => '16 GB',
            'storage_capacity' => '1 TB SSD',
            'screen_size' => '15.6 in',
            'gpu' => 'NVIDIA GeForce RTX 4060',
            'operating_system' => 'Windows 11',
        ])->assertRedirect(route('profiles.index'));

        $this->assertDatabaseHas('search_profiles', [
            'name' => 'Gaming laptop',
            'user_id' => auth()->id(),
            'query' => 'gaming laptop',
            'condition' => 'USED',
            'min_price' => 300,
            'max_price' => 1500,
            'price_alert_threshold' => '1200.50',
            'brand' => 'Lenovo',
            'ram_size' => '16 GB',
            'storage_capacity' => '1 TB SSD',
            'screen_size' => '15.6 in',
            'gpu' => 'NVIDIA GeForce RTX 4060',
            'operating_system' => 'Windows 11',
        ]);
        $createdProfile = SearchProfile::query()->where('name', 'Gaming laptop')->firstOrFail();
        $this->assertSame('14:30', substr($createdProfile->schedule_time, 0, 5));
        $this->assertSame(4, $createdProfile->schedule_interval_hours);
        $this->assertSame('Intel Core i7', $createdProfile->processor);
        $this->assertDatabaseHas('search_profile_activities', [
            'search_profile_id' => SearchProfile::query()->where('name', 'Gaming laptop')->value('id'),
            'action' => 'created',
            'profile_name' => 'Gaming laptop',
        ]);
    }

    public function test_profile_rejects_values_that_are_not_available_in_ebay_filters(): void
    {
        $this->fakeLaptopTaxonomy();

        $this->from(route('profiles.create'))->post(route('profiles.store'), [
            'name' => 'Gaming laptop',
            'query' => 'gaming laptop',
            'brand' => ['Lenovo', 'Not an eBay value'],
        ])
            ->assertRedirect(route('profiles.create'))
            ->assertSessionHasErrors('brand.1');

        $this->assertDatabaseMissing('search_profiles', ['name' => 'Gaming laptop']);
    }

    public function test_profile_can_be_created_with_multiple_laptop_aspect_values(): void
    {
        $this->fakeLaptopTaxonomy();

        $this->from(route('profiles.create'))->post(route('profiles.store'), [
            'name' => 'Flexible laptop',
            'query' => 'laptop',
            'brand' => ['Lenovo', 'Apple'],
            'processor' => ['Intel Core i7', 'Ryzen 7'],
            'ram_size' => ['8 GB', '16 GB'],
            'storage_capacity' => ['512 GB SSD', '1 TB SSD'],
            'screen_size' => ['14 in', '15.6 in'],
            'gpu' => ['NVIDIA GeForce RTX 4050', 'NVIDIA GeForce RTX 4060'],
            'operating_system' => 'Windows 11',
        ])->assertRedirect(route('profiles.index'));

        $profile = SearchProfile::query()->where('name', 'Flexible laptop')->firstOrFail();

        $this->assertSame(['Lenovo', 'Apple'], $profile->brands);
        $this->assertSame(['Intel Core i7', 'Ryzen 7'], $profile->processors);
        $this->assertSame(['8 GB', '16 GB'], $profile->ram_sizes);
        $this->assertSame(['512 GB SSD', '1 TB SSD'], $profile->storage_capacities);
        $this->assertSame(['14 in', '15.6 in'], $profile->screen_sizes);
        $this->assertSame(['NVIDIA GeForce RTX 4050', 'NVIDIA GeForce RTX 4060'], $profile->gpus);
        $this->assertNull($profile->brand);
        $this->assertNull($profile->processor);

        $this->get(route('profiles.edit', $profile))
            ->assertOk()
            ->assertSee('data-selected="[&quot;Lenovo&quot;,&quot;Apple&quot;]"', false)
            ->assertSee('data-selected="[&quot;Intel Core i7&quot;,&quot;Ryzen 7&quot;]"', false);

        $this->get(route('profiles.index'))
            ->assertOk()
            ->assertSee('Lenovo, Apple')
            ->assertSee('Intel Core i7, Ryzen 7')
            ->assertSee('8 GB, 16 GB');
    }

    public function test_profile_can_be_created_with_any_value_for_each_multiple_aspect_filter(): void
    {
        Http::preventStrayRequests();

        $this->post(route('profiles.store'), [
            'name' => 'Any laptop filters',
            'query' => 'laptop',
            'brand' => ['__any__'],
            'processor' => ['__any__'],
            'ram_size' => ['__any__'],
            'storage_capacity' => ['__any__'],
            'screen_size' => ['__any__'],
            'gpu' => ['__any__'],
        ])->assertRedirect(route('profiles.index'));

        $profile = SearchProfile::query()->where('name', 'Any laptop filters')->firstOrFail();

        $this->assertSame([], $profile->brands);
        $this->assertSame([], $profile->processors);
        $this->assertSame([], $profile->ram_sizes);
        $this->assertSame([], $profile->storage_capacities);
        $this->assertSame([], $profile->screen_sizes);
        $this->assertSame([], $profile->gpus);

        $this->get(route('profiles.edit', $profile))
            ->assertOk()
            ->assertSee('value="__any__" data-any-option selected', false);
    }

    public function test_profile_can_be_updated_to_use_any_for_each_multiple_aspect_filter(): void
    {
        Http::preventStrayRequests();
        $profile = SearchProfile::factory()->create([
            'brands' => ['Lenovo'],
            'processors' => ['Intel Core i7'],
            'ram_sizes' => ['16 GB'],
            'storage_capacities' => ['1 TB SSD'],
            'screen_sizes' => ['15.6 in'],
            'gpus' => ['NVIDIA GeForce RTX 4060'],
        ]);

        $this->patch(route('profiles.update', $profile), [
            'name' => $profile->name,
            'query' => $profile->query,
            'brand' => ['__any__'],
            'processor' => ['__any__'],
            'ram_size' => ['__any__'],
            'storage_capacity' => ['__any__'],
            'screen_size' => ['__any__'],
            'gpu' => ['__any__'],
            'active' => '1',
        ])->assertRedirect(route('profiles.index'));

        $profile->refresh();
        $this->assertSame([], $profile->brands);
        $this->assertSame([], $profile->processors);
        $this->assertSame([], $profile->ram_sizes);
        $this->assertSame([], $profile->storage_capacities);
        $this->assertSame([], $profile->screen_sizes);
        $this->assertSame([], $profile->gpus);
    }

    public function test_profile_schedule_time_must_be_a_valid_time(): void
    {
        $this->from(route('profiles.create'))->post(route('profiles.store'), [
            'name' => 'Scheduled laptop',
            'query' => 'laptop',
            'schedule_time' => '25:61',
        ])
            ->assertRedirect(route('profiles.create'))
            ->assertSessionHasErrors('schedule_time');

        $this->assertDatabaseMissing('search_profiles', ['name' => 'Scheduled laptop']);
    }

    public function test_profile_schedule_interval_must_be_between_one_and_twenty_four_hours(): void
    {
        $this->from(route('profiles.create'))->post(route('profiles.store'), [
            'name' => 'Scheduled laptop',
            'query' => 'laptop',
            'schedule_interval_hours' => 25,
        ])
            ->assertRedirect(route('profiles.create'))
            ->assertSessionHasErrors('schedule_interval_hours');

        $this->assertDatabaseMissing('search_profiles', ['name' => 'Scheduled laptop']);
    }

    public function test_profile_can_select_notification_categories_and_daily_digest_frequency(): void
    {
        $profile = SearchProfile::factory()->create();

        $this->patch(route('profiles.update', $profile), [
            'name' => $profile->name,
            'query' => $profile->query,
            'notify_new_listings' => '0',
            'notify_price_drops' => '1',
            'notify_search_errors' => '0',
            'notify_profile_matches' => '0',
            'notify_shared_price_changes' => '1',
            'notification_frequency' => 'daily',
            'active' => '1',
        ])->assertRedirect(route('profiles.index'));

        $this->assertDatabaseHas('search_profiles', [
            'id' => $profile->id,
            'notify_new_listings' => false,
            'notify_price_drops' => true,
            'notify_search_errors' => false,
            'notify_profile_matches' => false,
            'notify_shared_price_changes' => true,
            'notification_frequency' => 'daily',
        ]);
        $this->get(route('profiles.edit', $profile))
            ->assertOk()
            ->assertSee('value="daily" selected', false)
            ->assertSee('Ежедневная сводка в '.substr($profile->schedule_time, 0, 5));
    }

    public function test_profile_rejects_an_unknown_notification_frequency(): void
    {
        $profile = SearchProfile::factory()->create();

        $this->from(route('profiles.edit', $profile))
            ->patch(route('profiles.update', $profile), [
                'name' => $profile->name,
                'query' => $profile->query,
                'notification_frequency' => 'every-minute',
                'active' => '1',
            ])
            ->assertRedirect(route('profiles.edit', $profile))
            ->assertSessionHasErrors('notification_frequency');

        $this->assertSame('immediate', $profile->fresh()->notification_frequency);
    }

    public function test_profile_can_be_updated_and_deactivated(): void
    {
        $this->fakeLaptopTaxonomy();
        $profile = SearchProfile::factory()->create();

        $response = $this->patch(route('profiles.update', $profile), [
            'name' => 'My ThinkPad',
            'query' => 'ThinkPad T14',
            'schedule_time' => '19:45',
            'schedule_interval_hours' => 6,
            'condition' => 'USED',
            'max_price' => 500,
            'price_alert_threshold' => 350.50,
            'ram_size' => '16 GB',
            'active' => '0',
        ]);

        $response->assertRedirect(route('profiles.index'));
        $this->assertDatabaseHas('search_profiles', [
            'id' => $profile->id,
            'name' => 'My ThinkPad',
            'query' => 'ThinkPad T14',
            'condition' => 'USED',
            'max_price' => 500,
            'price_alert_threshold' => 350.50,
            'ram_size' => '16 GB',
            'active' => false,
        ]);
        $this->assertSame('19:45', substr($profile->fresh()->schedule_time, 0, 5));
        $this->assertSame(6, $profile->fresh()->schedule_interval_hours);
        $this->assertDatabaseHas('search_profile_activities', [
            'search_profile_id' => $profile->id,
            'action' => 'updated',
            'profile_name' => 'My ThinkPad',
        ]);
        $activity = SearchProfileActivity::query()->where('action', 'updated')->firstOrFail();
        $this->assertContains('name', $activity->metadata['changed_fields']);
        $this->assertContains('query', $activity->metadata['changed_fields']);
    }

    public function test_changing_profile_price_threshold_allows_a_new_crossing_notification(): void
    {
        $profile = SearchProfile::factory()->create(['price_alert_threshold' => 500]);
        $listing = LaptopListing::factory()->create([
            'search_profile_id' => $profile->id,
            'price_threshold_notified_at' => now(),
        ]);

        $this->patch(route('profiles.update', $profile), [
            'name' => $profile->name,
            'query' => $profile->query,
            'price_alert_threshold' => 450,
            'active' => '1',
        ])->assertRedirect(route('profiles.index'));

        $this->assertDatabaseHas('search_profiles', [
            'id' => $profile->id,
            'price_alert_threshold' => '450.00',
        ]);
        $this->assertNull($listing->fresh()->price_threshold_notified_at);
    }

    public function test_profile_can_be_deleted_with_its_related_listings(): void
    {
        $profile = SearchProfile::factory()->create();
        $listing = LaptopListing::factory()->create(['search_profile_id' => $profile->id]);

        $this->get(route('profiles.index'))
            ->assertOk()
            ->assertSee(route('profiles.destroy', $profile))
            ->assertSee(route('profiles.parse', $profile))
            ->assertSee('Парсить ноутбуки')
            ->assertSee('id="parse-progress-dialog"', false)
            ->assertSee('Осталось обработать')
            ->assertSee('Остановить парсинг')
            ->assertSee('Удалить этот профиль и все связанные с ним лоты?');

        $this->delete(route('profiles.destroy', $profile))
            ->assertRedirect(route('profiles.index'))
            ->assertSessionHas('success', 'Профиль поиска и связанные с ним лоты удалены.');

        $this->assertDatabaseMissing('search_profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('laptop_listings', ['id' => $listing->id]);
        $this->assertDatabaseHas('search_profile_activities', [
            'search_profile_id' => null,
            'action' => 'deleted',
            'profile_name' => $profile->name,
        ]);
    }

    public function test_search_profile_history_displays_profile_activity_and_is_linked_from_navigation(): void
    {
        $profile = SearchProfile::factory()->create(['name' => 'ThinkPad']);
        $otherUser = User::factory()->create();
        $otherProfile = SearchProfile::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Private profile',
        ]);
        $actor = User::factory()->create(['name' => 'Activity actor']);
        SearchProfileActivity::query()->create([
            'search_profile_id' => $profile->id,
            'user_id' => $actor->id,
            'profile_owner_id' => $profile->user_id,
            'profile_name' => 'ThinkPad',
            'action' => 'created',
            'description' => 'Создан профиль поиска «ThinkPad».',
        ]);
        SearchProfileActivity::query()->create([
            'search_profile_id' => $otherProfile->id,
            'user_id' => $otherUser->id,
            'profile_owner_id' => $otherUser->id,
            'profile_name' => 'Private profile',
            'action' => 'created',
            'description' => 'Private activity.',
        ]);

        $this->get(route('profiles.history'))
            ->assertOk()
            ->assertSee('Главное меню')
            ->assertSee('История поиска')
            ->assertSee('data-table-key="profile-history"', false)
            ->assertSee('data-column-key="details"', false)
            ->assertSee('Создание')
            ->assertSee('ThinkPad')
            ->assertSee('Создан профиль поиска «ThinkPad».')
            ->assertSee('Activity actor')
            ->assertDontSee('Private profile')
            ->assertDontSee('Private activity.');

        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee(route('profiles.history'))
            ->assertSee('История поиска');
    }

    public function test_profile_rejects_a_maximum_price_below_the_minimum(): void
    {
        $profile = SearchProfile::factory()->create();

        $this->from(route('profiles.edit', $profile))
            ->patch(route('profiles.update', $profile), [
                'name' => $profile->name,
                'query' => $profile->query,
                'min_price' => 900,
                'max_price' => 300,
                'active' => '1',
            ])
            ->assertRedirect(route('profiles.edit', $profile))
            ->assertSessionHasErrors('max_price');

        $this->assertDatabaseHas('search_profiles', [
            'id' => $profile->id,
            'max_price' => null,
        ]);
    }

    public function test_invalid_profile_update_does_not_change_the_profile(): void
    {
        $profile = SearchProfile::factory()->create([
            'name' => 'Original profile',
            'query' => 'laptop',
        ]);

        $response = $this->from(route('profiles.edit', $profile))->patch(route('profiles.update', $profile), [
            'name' => '',
            'query' => 'laptop',
            'active' => '1',
        ]);

        $response->assertRedirect(route('profiles.edit', $profile));
        $response->assertSessionHasErrors('name');
        $this->assertDatabaseHas('search_profiles', [
            'id' => $profile->id,
            'name' => 'Original profile',
            'query' => 'laptop',
        ]);
    }

    private function fakeLaptopTaxonomy(): void
    {
        config([
            'services.ebay.client_id' => 'test-client',
            'services.ebay.client_secret' => 'test-secret',
        ]);

        Http::fake([
            '*identity/*' => Http::response(['access_token' => 'token']),
            '*get_default_category_tree_id*' => Http::response(['categoryTreeId' => '0']),
            '*get_category_subtree*' => Http::response(['categorySubtreeNode' => [
                'childCategoryTreeNodes' => [
                    ['category' => ['categoryId' => '177'], 'leafCategoryTreeNode' => true],
                    ['category' => ['categoryId' => '111422'], 'leafCategoryTreeNode' => true],
                ],
            ]]),
            '*get_item_aspects_for_category*' => function (Request $request) {
                $brand = str_contains($request->url(), 'category_id=111422') ? 'Apple' : 'Lenovo';

                return Http::response(['aspects' => [
                    ['localizedAspectName' => 'Brand', 'aspectValues' => [
                        ['localizedValue' => $brand],
                        ['localizedValue' => $brand === 'Lenovo' ? 'Apple' : 'Lenovo'],
                    ]],
                    ['localizedAspectName' => 'Processor', 'aspectValues' => [
                        ['localizedValue' => 'Intel Core i7'],
                        ['localizedValue' => 'Ryzen 7'],
                        ['localizedValue' => 'AMD Ryzen 7'],
                    ]],
                    ['localizedAspectName' => 'RAM Size', 'aspectValues' => [
                        ['localizedValue' => '8 GB'],
                        ['localizedValue' => '16 GB'],
                    ]],
                    ['localizedAspectName' => 'Storage Capacity', 'aspectValues' => [
                        ['localizedValue' => '512 GB SSD'],
                        ['localizedValue' => '1 TB SSD'],
                    ]],
                    ['localizedAspectName' => 'Screen Size', 'aspectValues' => [
                        ['localizedValue' => '14 in'],
                        ['localizedValue' => '15.6 in'],
                    ]],
                    ['localizedAspectName' => 'GPU', 'aspectValues' => [
                        ['localizedValue' => 'NVIDIA GeForce RTX 4050'],
                        ['localizedValue' => 'NVIDIA GeForce RTX 4060'],
                    ]],
                    ['localizedAspectName' => 'Operating System', 'aspectValues' => [['localizedValue' => 'Windows 11']]],
                ]]);
            },
        ]);
    }
}
