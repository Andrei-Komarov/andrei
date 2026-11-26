<?php

namespace Tests\Feature;

use App\Models\LaptopListing;
use App\Models\SearchProfile;
use App\Models\SearchProfileActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_summary_aggregates_latest_results_lists_and_overdue_profiles_for_the_current_user(): void
    {
        $this->withoutVite();
        $this->travelTo(now()->setTimezone('Asia/Bishkek')->setDate(2026, 10, 2)->setTime(13, 0));
        $user = User::factory()->create();
        $this->actingAs($user);

        $currentProfile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Актуальный профиль',
            'query' => 'ThinkPad',
            'last_synced_at' => now()->subHours(3),
            'schedule_interval_hours' => 4,
        ]);
        $overdueProfile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Просроченный профиль',
            'last_synced_at' => now()->subDays(2),
            'schedule_interval_hours' => 24,
        ]);
        $neverRunProfile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Новый профиль',
            'last_synced_at' => null,
        ]);
        $disabledProfile = SearchProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'Отключённый профиль',
            'active' => false,
            'last_synced_at' => now()->subDays(3),
        ]);
        $otherUser = User::factory()->create();
        $otherUserProfile = SearchProfile::factory()->create([
            'user_id' => $otherUser->id,
            'name' => 'Чужой профиль',
            'last_synced_at' => now()->subDays(3),
        ]);

        SearchProfileActivity::query()->create([
            'search_profile_id' => $currentProfile->id,
            'user_id' => $user->id,
            'profile_owner_id' => $user->id,
            'profile_name' => $currentProfile->name,
            'action' => 'parse_completed',
            'description' => 'Старый поиск',
            'metadata' => ['found' => 99, 'new_listings' => 9],
        ]);
        SearchProfileActivity::query()->create([
            'search_profile_id' => $currentProfile->id,
            'user_id' => $user->id,
            'profile_owner_id' => $user->id,
            'profile_name' => $currentProfile->name,
            'action' => 'parse_completed',
            'description' => 'Последний поиск',
            'metadata' => ['found' => 12, 'new_listings' => 3],
        ]);
        SearchProfileActivity::query()->create([
            'search_profile_id' => $overdueProfile->id,
            'user_id' => $user->id,
            'profile_owner_id' => $user->id,
            'profile_name' => $overdueProfile->name,
            'action' => 'parse_completed',
            'description' => 'Последний просроченный поиск',
            'metadata' => ['found' => 5, 'new_listings' => 1],
        ]);

        LaptopListing::factory()->create([
            'search_profile_id' => $currentProfile->id,
            'status' => 'watching',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $currentProfile->id,
            'status' => 'watching',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $currentProfile->id,
            'status' => 'buy',
        ]);
        LaptopListing::factory()->create([
            'search_profile_id' => $currentProfile->id,
            'status' => 'watching',
        ])->delete();
        LaptopListing::factory()->create([
            'search_profile_id' => $otherUserProfile->id,
            'status' => 'buy',
        ]);

        $this->get(route('search-summary'))
            ->assertOk()
            ->assertSee('Сводка по поиску')
            ->assertSee('data-table-key="search-summary"', false)
            ->assertSee('data-column-key="last-run"', false)
            ->assertSee('Найдено в последних запусках')
            ->assertSee('17')
            ->assertSee('Новых товаров в последних запусках')
            ->assertSee('4')
            ->assertSee('Всего товаров в базе')
            ->assertSee('3')
            ->assertSee('В списке «Наблюдение»')
            ->assertSee('2')
            ->assertSee('В списке «Купить»')
            ->assertSee('Просроченный профиль')
            ->assertSee('Новый профиль')
            ->assertSee('Отключённый профиль')
            ->assertDontSee('Чужой профиль');
    }

    public function test_search_summary_requires_an_authenticated_user(): void
    {
        $this->get(route('search-summary'))->assertRedirect(route('login'));
    }

    public function test_search_summary_sorts_profiles_by_latest_found_count(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $this->actingAs($user);
        $firstProfile = SearchProfile::factory()->create(['user_id' => $user->id, 'name' => 'Lower result']);
        $lastProfile = SearchProfile::factory()->create(['user_id' => $user->id, 'name' => 'Higher result']);
        foreach ([[$firstProfile, 5], [$lastProfile, 20]] as [$profile, $found]) {
            SearchProfileActivity::query()->create([
                'search_profile_id' => $profile->id,
                'user_id' => $user->id,
                'profile_owner_id' => $user->id,
                'profile_name' => $profile->name,
                'action' => 'parse_completed',
                'description' => 'Search completed.',
                'metadata' => ['found' => $found],
            ]);
        }

        $response = $this->get(route('search-summary', ['sort' => 'found_desc']))->assertOk();

        $this->assertSame(
            ['Higher result', 'Lower result'],
            $response->viewData('profiles')->pluck('name')->all(),
        );
    }
}
